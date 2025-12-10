<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BillDeskService
{
    protected string $merchantId;
    protected ?string $clientId;
    protected ?string $aggregatorId;
    protected string $secretKey;
    protected string $env;
    protected string $payEndpoint;
    protected string $statusEndpoint;
    protected ?string $callbackUrl;

    public function __construct()
    {
        $this->merchantId     = config('billdesk.merchant_id', '');
        $this->clientId       = config('billdesk.client_id');
        $this->aggregatorId   = config('billdesk.aggregator_id');
        $this->secretKey      = config('billdesk.secret_key', '');
        $this->env            = config('billdesk.env', 'test');
        $this->payEndpoint    = config('billdesk.pay_endpoint', 'https://pguat.billdesk.com/pay');
        $this->statusEndpoint = config('billdesk.status_endpoint', 'https://pguat.billdesk.com/status');
        $this->callbackUrl    = config('billdesk.callback_url');

        if (empty($this->merchantId) || empty($this->secretKey)) {
            throw new \RuntimeException('BillDesk credentials are not configured. Please set BILLDESK_MERCHANT_ID and BILLDESK_SECRET_KEY in .env.');
        }
    }

    /**
    * Build a BillDesk payment request payload.
    * Returns array with order_id and form data for POSTing to BillDesk.
    */
    public function initiatePayment(Event $event, Registration $registration, string $studentEmail, ?string $studentPhone = null): array
    {
        $amount = number_format((float) $event->amount, 2, '.', '');
        if ($amount <= 0) {
            throw new \RuntimeException('Invalid event amount. Amount must be greater than 0.');
        }

        $orderId = $this->generateOrderId($event, $registration);
        $callback = $this->callbackUrl ?? route('payment.billdesk.callback');

        $payload = [
            'mercid'   => $this->merchantId,
            'orderid'  => $orderId,
            'amount'   => $amount,
            'currency' => 'INR',
            'ru'       => $callback,
            'email'    => $studentEmail,
            'mobile'   => $studentPhone ?? '',
            'custid'   => $registration->id,
            'eventid'  => $event->id,
            'itemcode' => 'DIRECT',
            'paymode'  => 'ALL',
            'txntype'  => 'SALE',
        ];

        $checksum = $this->sign($payload);

        Log::info('BillDesk payment initiated', [
            'order_id' => $orderId,
            'event_id' => $event->id,
            'registration_id' => $registration->id,
            'amount' => $amount,
        ]);

        return [
            'order_id' => $orderId,
            'endpoint' => $this->payEndpoint,
            'fields' => array_merge($payload, [
                'checksum' => $checksum,
                'clientid' => $this->clientId,
                'aggregator_id' => $this->aggregatorId,
                'env' => $this->env,
            ]),
        ];
    }

    /**
     * Verify checksum for callback/status payload.
     */
    public function verifyChecksum(array $data, string $signature): bool
    {
        $expected = $this->sign($data);
        return hash_equals($expected, $signature);
    }

    /**
     * Normalize BillDesk status to local status.
     */
    public function normalizeStatus(?string $billdeskStatus): string
    {
        $status = strtoupper((string) $billdeskStatus);
        return match ($status) {
            'SUCCESS', 'S', 'OK' => 'success',
            'FAIL', 'FAILED', 'F' => 'failed',
            default => 'pending',
        };
    }

    protected function generateOrderId(Event $event, Registration $registration): string
    {
        return 'EVT' . $event->id . '-REG' . $registration->id . '-' . Str::upper(Str::random(6));
    }

    protected function sign(array $data): string
    {
        ksort($data);
        $query = urldecode(http_build_query($data));
        return hash_hmac('sha256', $query, $this->secretKey);
    }
}

