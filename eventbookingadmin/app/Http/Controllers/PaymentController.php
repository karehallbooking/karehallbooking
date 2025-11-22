<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::orderBy('start_date')->orderBy('title')->get();
        $filters = [
            'event_id' => $request->get('event_id'),
            'status' => $request->get('status'),
            'from' => $request->get('from'),
            'to' => $request->get('to'),
        ];

        $baseQuery = $this->filteredPaymentsQuery($filters);
        $payments = (clone $baseQuery)->orderByDesc('created_at')->paginate(25)->withQueryString();

        $summary = [
            'total_amount' => (clone $baseQuery)->sum('amount'),
            'total_paid' => (clone $baseQuery)->where('status', 'paid')->sum('amount'),
            'total_pending' => (clone $baseQuery)->where('status', 'pending')->sum('amount'),
        ];

        return view('admin.payments.index', compact('payments', 'summary', 'events', 'filters'));
    }

    public function markPaid(Payment $payment)
    {
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $payment->registration->update(['payment_status' => 'paid']);

        return back()->with('success', 'Payment marked as paid.');
    }

    public function refund(Payment $payment)
    {
        $payment->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);

        $payment->registration->update(['payment_status' => 'refunded']);

        return back()->with('success', 'Payment refunded.');
    }

    public function exportCsv(Request $request)
    {
        $filters = [
            'event_id' => $request->get('event_id'),
            'status' => $request->get('status'),
            'from' => $request->get('from'),
            'to' => $request->get('to'),
        ];

        $fileName = 'payments_' . now()->format('Ymd_His') . '.csv';
        $payments = $this->filteredPaymentsQuery($filters)
            ->orderByDesc('created_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Event',
                'Student',
                'Amount',
                'Status',
                'Method',
                'Transaction ID',
                'Paid At',
                'Refunded At',
            ]);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $payment->id,
                    optional($payment->event)->title,
                    optional($payment->registration)->student_name,
                    $payment->amount,
                    $payment->status,
                    $payment->payment_method,
                    $payment->transaction_id,
                    $payment->paid_at,
                    $payment->refunded_at,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function filteredPaymentsQuery(array $filters)
    {
        $query = Payment::with(['registration', 'event']);

        if (!empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query;
    }
}


