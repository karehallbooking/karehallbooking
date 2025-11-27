<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;

class RazorpayService
{
    protected $api;
    protected $keyId;
    protected $keySecret;
    protected $currency;

    public function __construct()
    {
        $this->keyId = env('RAZORPAY_KEY_ID');
        $this->keySecret = env('RAZORPAY_KEY_SECRET');
        $this->currency = env('RAZORPAY_CURRENCY', 'INR');

        if (empty($this->keyId) || empty($this->keySecret)) {
            throw new \Exception('Razorpay credentials are not configured. Please set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in .env file.');
        }

        $this->api = new Api($this->keyId, $this->keySecret);
    }

    /**
     * Create a Razorpay order for event registration
     *
     * @param string $studentEmail
     * @param Event $event
     * @param Registration $registration
     * @return array
     * @throws \Exception
     */
    public function createOrder(string $studentEmail, Event $event, Registration $registration): array
    {
        // Convert amount to paise (smallest currency unit)
        $amount = (int) ($event->amount * 100);

        if ($amount <= 0) {
            throw new \Exception('Invalid event amount. Amount must be greater than 0.');
        }

        // Create unique receipt ID
        $receipt = "event_{$event->id}_reg_{$registration->id}_" . time();

        // Prepare order data
        $orderData = [
            'amount' => $amount,
            'currency' => $this->currency,
            'receipt' => $receipt,
            'notes' => [
                'student_email' => $studentEmail,
                'event_id' => $event->id,
                'registration_id' => $registration->id,
                'event_title' => $event->title,
            ],
        ];

        try {
            Log::info('Creating Razorpay order', [
                'event_id' => $event->id,
                'registration_id' => $registration->id,
                'amount' => $amount,
                'currency' => $this->currency,
            ]);

            $order = $this->api->order->create($orderData);

            Log::info('Razorpay order created successfully', [
                'order_id' => $order['id'],
                'amount' => $order['amount'],
                'status' => $order['status'] ?? 'unknown',
            ]);

            return [
                'id' => $order['id'],
                'amount' => $order['amount'],
                'currency' => $order['currency'],
                'receipt' => $order['receipt'],
                'status' => $order['status'] ?? 'created',
                'notes' => $order['notes'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create Razorpay order', [
                'event_id' => $event->id,
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to create payment order: ' . $e->getMessage());
        }
    }

    /**
     * Verify payment signature
     *
     * @param array $attributes
     * @return bool
     */
    public function verifyPaymentSignature(array $attributes): bool
    {
        try {
            $this->api->utility->verifyPaymentSignature($attributes);
            return true;
        } catch (\Exception $e) {
            Log::warning('Payment signature verification failed', [
                'error' => $e->getMessage(),
                'attributes' => $attributes,
            ]);
            return false;
        }
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');

        if (empty($webhookSecret)) {
            Log::warning('RAZORPAY_WEBHOOK_SECRET is not set. Webhook verification skipped.');
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }
}

