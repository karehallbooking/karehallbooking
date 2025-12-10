Add these to your `.env` (replace with real BillDesk credentials):

```
BILLDESK_ENV=test
BILLDESK_MERCHANT_ID=your_merchant_id
BILLDESK_CLIENT_ID=your_client_id
BILLDESK_SECRET_KEY=your_secret_key
BILLDESK_AGGREGATOR_ID=optional_if_applicable
BILLDESK_PAY_ENDPOINT=https://pguat.billdesk.com/pay
BILLDESK_STATUS_ENDPOINT=https://pguat.billdesk.com/status
BILLDESK_CALLBACK_URL=    # leave blank to use the app route /payment/billdesk/callback
```

