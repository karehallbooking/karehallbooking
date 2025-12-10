<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\Event;
use App\Services\BillDeskService;
use App\Helpers\QRHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $billdeskService;

    public function __construct(BillDeskService $billdeskService)
    {
        $this->billdeskService = $billdeskService;
    }

    /**
     * BillDesk callback (success / failure)
     */
    public function billdeskCallback(Request $request)
    {
        $payload = $request->all();
        $orderId = $payload['orderid'] ?? $payload['ORDER_ID'] ?? null;
        $statusRaw = $payload['status'] ?? $payload['STATUS'] ?? null;
        $transactionId = $payload['transactionid'] ?? $payload['TRANSACTION_ID'] ?? null;
        $checksum = $payload['checksum'] ?? $payload['CHECKSUM'] ?? null;

        if (!$orderId) {
            Log::error('BillDesk callback missing order id', ['payload' => $payload]);
            return redirect()->route('payment.failure')->with('error', 'Payment reference missing.');
        }

        if ($checksum && !$this->billdeskService->verifyChecksum($payload, $checksum)) {
            Log::warning('BillDesk checksum verification failed', ['order_id' => $orderId]);
            return redirect()->route('payment.failure')->with('error', 'Payment verification failed.');
        }

        try {
            return DB::transaction(function () use ($orderId, $statusRaw, $transactionId, $payload) {
                $payment = Payment::where('razorpay_order_id', $orderId)->lockForUpdate()->first();

                if (!$payment) {
                    Log::error('Payment not found for BillDesk callback', ['order_id' => $orderId]);
                    return redirect()->route('payment.failure')->with('error', 'Payment not found.');
                }

                $status = $this->billdeskService->normalizeStatus($statusRaw);

                // Idempotent check
                if ($payment->status === 'success') {
                    $ticket = optional($payment->registration)->ticket;
                    if ($ticket) {
                        return redirect()->route('payment.success', ['ticket' => $ticket->ticket_code]);
                    }
                }

                if ($status === 'success') {
                    $payment->update([
                        'status' => 'success',
                        'transaction_id' => $transactionId ?? $payment->transaction_id,
                        'payment_method' => 'billdesk',
                        'paid_at' => now(),
                        'meta' => $payload,
                    ]);

                    $registration = $payment->registration;
                    $registration->update(['payment_status' => 'paid']);

                    $ticket = $this->generateTicket($registration);

                    return redirect()->route('payment.success', ['ticket' => $ticket->ticket_code]);
                }

                // Failure / pending
                $payment->update([
                    'status' => $status === 'pending' ? 'pending' : 'failed',
                    'transaction_id' => $transactionId ?? $payment->transaction_id,
                    'payment_method' => 'billdesk',
                    'meta' => $payload,
                ]);

                return redirect()->route('payment.failure');
            });
        } catch (\Exception $e) {
            Log::error('BillDesk callback processing failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('payment.failure')->with('error', 'Payment processing failed.');
        }
    }

    /**
     * Show payment success page
     */
    public function successPage(Request $request)
    {
        $ticketCode = $request->query('ticket');

        if (!$ticketCode) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Invalid ticket code.');
        }

        $ticket = Ticket::where('ticket_code', $ticketCode)->first();

        if (!$ticket) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Ticket not found.');
        }

        $registration = $ticket->registration;
        $event = $registration->event;

        return view('payment.success', compact('ticket', 'registration', 'event'));
    }

    /**
     * Show payment failure page
     */
    public function failurePage()
    {
        return view('payment.failure');
    }

    /**
     * Generate ticket for registration (only for paid registrations)
     */
    protected function generateTicket(Registration $registration): Ticket
    {
        // Ensure registration is paid
        if ($registration->payment_status !== 'paid') {
            throw new \Exception('Cannot generate ticket for unpaid registration.');
        }

        // Check if ticket already exists
        $existingTicket = $registration->ticket;
        if ($existingTicket) {
            return $existingTicket;
        }

        return DB::transaction(function () use ($registration) {
            // Generate unique ticket code
            $ticketCode = 'EVT' . $registration->event_id . '-U' . $registration->id . '-' . Str::random(8);

            // Generate QR code if not exists
            if (!$registration->qr_code) {
                $qrCode = QRHelper::generate(
                    $registration->id,
                    $registration->event_id,
                    $registration->student_email
                );
                $registration->update(['qr_code' => $qrCode]);
            } else {
                $qrCode = $registration->qr_code;
            }

            // Generate QR image (SVG)
            $qrSvg = QRHelper::renderSvg($qrCode, 300);

            // Save QR image as SVG
            $qrPath = 'tickets/' . $ticketCode . '.svg';
            $qrDir = storage_path('app/tickets');
            if (!is_dir($qrDir)) {
                mkdir($qrDir, 0755, true);
            }

            // Save SVG file
            file_put_contents(storage_path('app/' . $qrPath), $qrSvg);

            // Create ticket
            $ticket = Ticket::create([
                'registration_id' => $registration->id,
                'ticket_code' => $ticketCode,
                'qr_path' => $qrPath,
                'generated_at' => now(),
            ]);

            Log::info('Ticket generated after payment success', [
                'ticket_code' => $ticketCode,
                'registration_id' => $registration->id,
                'payment_status' => $registration->payment_status,
            ]);

            return $ticket;
        });
    }
}

