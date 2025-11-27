<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Payment;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventRegistrationController extends Controller
{
    protected $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }

    /**
     * Show event registration page
     */
    public function showRegisterPage(Event $event, Request $request)
    {
        // Get student info from session
        $studentEmail = $request->session()->get('student_email');
        $studentName = $request->session()->get('student_name');
        $studentPhone = $request->session()->get('student_phone');
        $studentRoll = $request->session()->get('student_roll');

        // Check if event is open for registration
        if (!in_array($event->status, ['published', 'upcoming'])) {
            return redirect()->route('student.dashboard')
                ->with('error', 'This event is not available for registration.');
        }

        // Check if event date is still valid
        if ($event->end_date < now()->toDateString()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Registration for this event has closed.');
        }

        // Check if registration already exists
        $registration = null;
        if ($studentEmail) {
            $registration = Registration::with('ticket')
                ->where('event_id', $event->id)
                ->where('student_email', $studentEmail)
                ->first();
        }

        // Check if already paid and has ticket
        $hasPaidPayment = false;
        $ticketCode = null;
        if ($registration) {
            // Only consider it "paid" if payment is paid AND ticket exists
            $hasPaidPayment = $registration->payment_status === 'paid' && $registration->ticket;
            if ($hasPaidPayment) {
                $ticketCode = $registration->ticket->ticket_code;
            }
        }

        return view('events.register', compact(
            'event',
            'registration',
            'studentEmail',
            'studentName',
            'studentPhone',
            'studentRoll',
            'hasPaidPayment',
            'ticketCode'
        ));
    }

    /**
     * Create Razorpay order for event registration
     */
    public function createOrderForEvent(Request $request, Event $event)
    {
        try {
            // Get student info from session or request
            $studentEmail = $request->input('student_email') ?? $request->session()->get('student_email');
            $studentName = $request->input('student_name') ?? $request->session()->get('student_name');
            $studentPhone = $request->input('student_phone') ?? $request->session()->get('student_phone');
            $studentRoll = $request->input('student_roll') ?? $request->session()->get('student_roll');

            if (!$studentEmail || !$studentName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student information is required. Please provide your name and email.',
                ], 400);
            }

            // Validate event
            if (!$event->is_paid || $event->amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This event does not require payment or has invalid amount.',
                ], 400);
            }

            return DB::transaction(function () use ($event, $studentEmail, $studentName, $studentPhone, $studentRoll, $request) {
                // Find or create registration
                $registration = Registration::firstOrCreate(
                    [
                        'event_id' => $event->id,
                        'student_email' => $studentEmail,
                    ],
                    [
                        'student_name' => $studentName,
                        'student_phone' => $studentPhone,
                        'student_id' => $studentRoll,
                        'payment_status' => 'pending',
                        'attendance_status' => 'absent',
                        'registered_at' => now(),
                    ]
                );

                // Check if already has a paid payment (idempotent check)
                $existingPayment = Payment::where('registration_id', $registration->id)
                    ->where('status', 'success')
                    ->first();

                if ($existingPayment && $registration->ticket) {
                    Log::info('Payment already exists for registration', [
                        'registration_id' => $registration->id,
                        'payment_id' => $existingPayment->id,
                        'ticket_code' => $registration->ticket->ticket_code,
                    ]);

                    return response()->json([
                        'success' => true,
                        'already_paid' => true,
                        'message' => 'Payment already completed.',
                        'ticket_url' => route('payment.success', ['ticket' => $registration->ticket->ticket_code]),
                    ]);
                }

                // Create Razorpay order
                $order = $this->razorpayService->createOrder($studentEmail, $event, $registration);

                // Create or update payment record
                $payment = Payment::updateOrCreate(
                    [
                        'registration_id' => $registration->id,
                        'razorpay_order_id' => $order['id'],
                    ],
                    [
                        'event_id' => $event->id,
                        'gateway' => 'razorpay',
                        'amount' => $order['amount'] / 100, // Convert from paise to rupees
                        'currency' => $order['currency'],
                        'status' => 'pending',
                        'meta' => $order,
                    ]
                );

                Log::info('Razorpay order created for registration', [
                    'registration_id' => $registration->id,
                    'payment_id' => $payment->id,
                    'order_id' => $order['id'],
                    'amount' => $order['amount'],
                ]);

                // Return response with Razorpay checkout data
                return response()->json([
                    'success' => true,
                    'razorpay_key' => env('RAZORPAY_KEY_ID'),
                    'order_id' => $order['id'],
                    'amount' => $order['amount'],
                    'currency' => $order['currency'],
                    'student_name' => $studentName,
                    'student_email' => $studentEmail,
                    'student_phone' => $studentPhone ?? '',
                    'event_name' => $event->title,
                    'registration_id' => $registration->id,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to create Razorpay order', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment order: ' . $e->getMessage(),
            ], 500);
        }
    }
}

