@extends('layouts.admin')

@section('content')
<div style="margin-bottom: 16px;">
    <a class="back-to-dashboard" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
</div>
<div class="card-grid">
    <div class="card">
        <h3>Export Registrations CSV</h3>
        <p>Download registration data.</p>
        <a href="{{ route('admin.reports.registrations.csv') }}">Download</a>
    </div>
    <div class="card">
        <h3>Export Attendance CSV</h3>
        <p>All attendance logs.</p>
        <a href="{{ route('admin.reports.attendance.csv') }}">Download</a>
    </div>
    <div class="card">
        <h3>Export Payments CSV</h3>
        <p>All payment records.</p>
        <a href="{{ route('admin.reports.payments.csv') }}">Download</a>
    </div>
    <div class="card">
        <h3>Download Monthly Report</h3>
        <p>PDF breakdown by month.</p>
        <a href="{{ route('admin.reports.monthly') }}">Monthly PDF</a>
    </div>
    <div class="card">
        <h3>Download Event Report</h3>
        <p>Choose event details.</p>
        @if($recentEvents->count())
            <a href="{{ route('admin.reports.event', $recentEvents->first()->id) }}">Latest Event</a>
        @endif
    </div>
    <div class="card">
        <h3>Print Summary</h3>
        <p>Plain summary page.</p>
        <a href="{{ route('admin.reports.summary') }}" target="_blank">Open Summary</a>
    </div>
</div>

<div class="section-block">
    <h2>Export Full Event & Registration Report</h2>
    <form method="GET" action="{{ route('admin.reports.export.full') }}" class="form-row" style="flex-wrap:wrap;">
        <label style="flex:1 1 220px;">
            Event
            <select name="event_id">
                <option value="">All Events</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}">{{ $event->title }}</option>
                @endforeach
            </select>
        </label>
        <label style="flex:1 1 160px;">
            From date
            <input
                type="text"
                name="from"
                id="report-from-date"
                class="date-picker date-upcoming-only"
                placeholder="dd-mm-yyyy"
                data-linked-end="#report-to-date"
                autocomplete="off"
            >
        </label>
        <label style="flex:1 1 160px;">
            To date
            <input
                type="text"
                name="to"
                id="report-to-date"
                class="date-picker date-upcoming-only date-upcoming-end"
                placeholder="dd-mm-yyyy"
                data-linked-start="#report-from-date"
                autocomplete="off"
            >
        </label>
        <div style="align-self:flex-end;">
            <button type="submit">Export CSV</button>
        </div>
    </form>
    <p style="margin-top:8px;">Columns included: event_id, title, date, registration_id, student info, payment/attendance status, certificate flag.</p>
</div>

<div class="section-block">
    <h2>Key Stats</h2>
    <p>Total Events: {{ $stats['events'] }}</p>
    <p>Total Registrations: {{ $stats['registrations'] }}</p>
    <p>Attendance Today: {{ $stats['attendance_today'] }}</p>
    <p>Paid Amount: {{ number_format($stats['payments_total'], 2) }}</p>
</div>

<div class="section-block">
    <h2>Recent Events</h2>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Date Range</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentEvents as $event)
                <tr>
                    <td>{{ $event->title }}</td>
                    <td>{{ optional($event->start_date)->format('Y-m-d') }} to {{ optional($event->end_date)->format('Y-m-d') }}</td>
                    <td><a href="{{ route('admin.reports.event', $event->id) }}">Download Report</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection


