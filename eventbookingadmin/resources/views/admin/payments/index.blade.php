@extends('layouts.admin')

@section('content')
<div style="margin-bottom: 16px;">
    <a class="back-to-dashboard" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
</div>
<div class="section-block">
    <h2>Payments Console</h2>
    <p>Filter by event and date range to review totals and reconcile transactions.</p>

    <form method="GET" action="{{ route('admin.payments.index') }}" class="form-row">
        <label style="flex:1;">
            Event
            <select name="event_id" onchange="this.form.submit()">
                <option value="">All Events</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected($filters['event_id'] == $event->id)>{{ $event->title }}</option>
                @endforeach
            </select>
        </label>
        <label style="flex:1;">
            Status
            <select name="status">
                <option value="all" @selected($filters['status'] === 'all' || empty($filters['status']))>All</option>
                @foreach(['paid' => 'Paid', 'pending' => 'Pending', 'refunded' => 'Refunded'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>
            From
            <input type="date" name="from" value="{{ $filters['from'] }}">
        </label>
        <label>
            To
            <input type="date" name="to" value="{{ $filters['to'] }}">
        </label>
        <div style="align-self:flex-end; display:flex; gap:8px;">
            <button type="submit">Apply</button>
            <a class="card a" href="{{ route('admin.payments.export.csv', array_filter($filters)) }}" style="padding:8px 12px;border:1px solid #0b5cc8;border-radius:6px;background:#0b5cc8;color:#fff;text-decoration:none;">Export CSV</a>
        </div>
    </form>
</div>

<div class="card-grid" style="margin-bottom:24px;">
    <div class="card">
        <h3>Total Amount</h3>
        <p>₹{{ number_format($summary['total_amount'], 2) }}</p>
    </div>
    <div class="card">
        <h3>Paid</h3>
        <p>₹{{ number_format($summary['total_paid'], 2) }}</p>
    </div>
    <div class="card">
        <h3>Pending</h3>
        <p>₹{{ number_format($summary['total_pending'], 2) }}</p>
    </div>
</div>

<div class="section-block" id="payments">
    <h2>Payment Records</h2>
    <table>
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Registration</th>
                <th>Student</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Transaction ID</th>
                <th>Status</th>
                <th>Paid At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td>#{{ $payment->id }}</td>
                    <td>{{ $payment->registration_id }}</td>
                    <td>{{ optional($payment->registration)->student_name ?? '—' }}</td>
                    <td>₹{{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $payment->payment_method ?? '—' }}</td>
                    <td>{{ $payment->transaction_id ?? '—' }}</td>
                    <td>{{ ucfirst($payment->status) }}</td>
                    <td>{{ optional($payment->paid_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="actions">
                        <form method="POST" action="{{ route('admin.payments.mark-paid', $payment->id) }}">
                            @csrf
                            <button type="submit">Mark Paid</button>
                        </form>
                        <form method="POST" action="{{ route('admin.payments.refund', $payment->id) }}">
                            @csrf
                            <button type="submit">Mark Refunded</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No payments match current filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $payments->links('pagination::simple-default') }}
</div>
@endsection

