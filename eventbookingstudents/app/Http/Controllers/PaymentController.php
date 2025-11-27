<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\Event;
use App\Services\RazorpayService;
use App\Helpers\QRHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }

    /**
     * Handle payment success callback from Razorpay
     */
    public function handlePaymentSuccess(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        try {
            // Verify payment signature
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ];

            if (!$this->razorpayService->verifyPaymentSignature($attributes)) {
                Log::warning('Payment signature verification failed', [
                    'order_id' => $request->razorpay_order_id,
                    'payment_id' => $request->razorpay_payment_id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed. Please contact support.',
                    'redirect_url' => route('payment.failure'),
                ], 400);
            }

            return DB::transaction(function () use ($request, $attributes) {
                // Find payment by order ID (lock for update to prevent race conditions)
                $payment = Payment::where('razorpay_order_id', $request->razorpay_order_id)
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    Log::error('Payment not found for order', [
                        'order_id' => $request->razorpay_order_id,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Payment record not found.',
                        'redirect_url' => route('payment.failure'),
                    ], 404);
                }

                // Idempotent check: if already processed, return existing ticket
                if ($payment->status === 'success') {
                    $registration = $payment->registration;
                    $ticket = $registration->ticket;

                    if ($ticket) {
                        Log::info('Payment already processed, returning existing ticket', [
                            'payment_id' => $payment->id,
                            'ticket_code' => $ticket->ticket_code,
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Payment already processed.',
                            'redirect_url' => route('payment.success', ['ticket' => $ticket->ticket_code]),
                        ]);
                    }
                }

                // Update payment status
                $payment->update([
                    'status' => 'success',
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature' => $request->razorpay_signature,
                    'paid_at' => now(),
                    'payment_method' => 'razorpay',
                    'transaction_id' => $request->razorpay_payment_id,
                ]);

                // Update registration status
                $registration = $payment->registration;
                $registration->update([
                    'payment_status' => 'paid',
                ]);

                // Generate ticket (this will also generate QR code if not exists)
                $ticket = $this->generateTicket($registration);

                Log::info('Payment processed successfully', [
                    'payment_id' => $payment->id,
                    'registration_id' => $registration->id,
                    'ticket_code' => $ticket->ticket_code,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful!',
                    'redirect_url' => route('payment.success', ['ticket' => $ticket->ticket_code]),
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'order_id' => $request->razorpay_order_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
                'redirect_url' => route('payment.failure'),
            ], 500);
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
     * Handle payment failure
     */
    public function handlePaymentFailure(Request $request)
    {
        $orderId = $request->input('razorpay_order_id');

        if ($orderId) {
            $payment = Payment::where('razorpay_order_id', $orderId)->first();

            if ($payment && $payment->status === 'pending') {
                $payment->update([
                    'status' => 'failed',
                ]);

                Log::info('Payment marked as failed', [
                    'payment_id' => $payment->id,
                    'order_id' => $orderId,
                ]);
            }
        }

        return redirect()->route('payment.failure');
    }

    /**
     * Show payment failure page
     */
    public function failurePage()
    {
        return view('payment.failure');
    }

    /**
     * Handle Razorpay webhook
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        if (!$signature) {
            Log::warning('Razorpay webhook received without signature');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        // Verify webhook signature
        if (!$this->razorpayService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Razorpay webhook signature verification failed', [
                'signature' => $signature,
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $data = json_decode($payload, true);
        $eventType = $data['event'] ?? null;

        Log::info('Razorpay webhook received', [
            'event' => $eventType,
            'payload' => $data,
        ]);

        // Handle payment.captured event
        if ($eventType === 'payment.captured') {
            $paymentData = $data['payload']['payment']['entity'] ?? null;
            $orderId = $paymentData['order_id'] ?? null;
            $paymentId = $paymentData['id'] ?? null;

            if (!$orderId || !$paymentId) {
                Log::error('Invalid webhook payload for payment.captured', [
                    'payload' => $data,
                ]);
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            try {
                return DB::transaction(function () use ($orderId, $paymentId, $paymentData) {
                    $payment = Payment::where('razorpay_order_id', $orderId)
                        ->lockForUpdate()
                        ->first();

                    if (!$payment) {
                        Log::error('Payment not found for webhook', [
                            'order_id' => $orderId,
                        ]);
                        return response()->json(['error' => 'Payment not found'], 404);
                    }

                    // Idempotent: if already success, return OK
                    if ($payment->status === 'success') {
                        Log::info('Payment already processed via webhook', [
                            'payment_id' => $payment->id,
                        ]);
                        return response()->json(['status' => 'ok']);
                    }

                    // Update payment
                    $payment->update([
                        'status' => 'success',
                        'razorpay_payment_id' => $paymentId,
                        'razorpay_signature' => $paymentData['signature'] ?? null,
                        'paid_at' => now(),
                        'payment_method' => 'razorpay',
                        'transaction_id' => $paymentId,
                    ]);

                    // Update registration
                    $registration = $payment->registration;
                    $registration->update([
                        'payment_status' => 'paid',
                    ]);

                    // Generate ticket
                    $this->generateTicket($registration);

                    Log::info('Webhook payment processed successfully', [
                        'payment_id' => $payment->id,
                        'registration_id' => $registration->id,
                    ]);

                    return response()->json(['status' => 'ok']);
                });
            } catch (\Exception $e) {
                Log::error('Webhook processing failed', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json(['error' => 'Processing failed'], 500);
            }
        }

        // For other events, just acknowledge
        return response()->json(['status' => 'ok']);
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

