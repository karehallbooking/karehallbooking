<?php

return [
    // Environment: 'prod' or 'test'
    'env' => env('BILLDESK_ENV', 'test'),

    // Core credentials (populate in .env)
    'merchant_id'   => env('BILLDESK_MERCHANT_ID'),
    'client_id'     => env('BILLDESK_CLIENT_ID'),
    'secret_key'    => env('BILLDESK_SECRET_KEY'),
    'aggregator_id' => env('BILLDESK_AGGREGATOR_ID'), // optional

    // Endpoints (override in .env if needed)
    'pay_endpoint'     => env('BILLDESK_PAY_ENDPOINT', 'https://pguat.billdesk.com/pay'),
    'status_endpoint'  => env('BILLDESK_STATUS_ENDPOINT', 'https://pguat.billdesk.com/status'),

    // Callback URL (set in .env; leave null to fall back to app route)
    'callback_url' => env('BILLDESK_CALLBACK_URL'),
];

