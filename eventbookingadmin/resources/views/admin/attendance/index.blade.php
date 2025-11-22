@extends('layouts.admin')

@section('content')
@php($view = request('view'))

@if(!$view)
    <div style="margin-bottom: 16px;">
        <a class="back-to-dashboard" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
    </div>
    <div class="card-grid">
        <div class="card">
            <h3>Complete History</h3>
            <p>Browse every scan log.</p>
            <a href="{{ route('admin.attendance.index', ['view' => 'logs']) }}">View Logs</a>
        </div>
        <div class="card">
            <h3>Filter by Event</h3>
            <p>Limit logs for one event.</p>
            <a href="{{ route('admin.attendance.index', ['view' => 'filters']) }}">Apply Filter</a>
        </div>
        <div class="card">
            <h3>Filter by Date Range</h3>
            <p>Select specific dates.</p>
            <a href="{{ route('admin.attendance.index', ['view' => 'filters']) }}">Choose Dates</a>
        </div>
        <div class="card">
            <h3>View Log Details</h3>
            <p>Inspect each entry.</p>
            <a href="{{ route('admin.attendance.index', ['view' => 'logs']) }}">Open</a>
        </div>
    </div>
@elseif($view === 'filters')
    <a class="back-link" href="{{ route('admin.attendance.index') }}">Back to Attendance</a>
    <div class="section-block">
        <h2>Filter Attendance Logs</h2>
        <form method="GET" action="{{ route('admin.attendance.index') }}">
            <input type="hidden" name="view" value="filters">
            <select name="event_id">
                <option value="">All Events</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected(request('event_id') == $event->id)>{{ $event->title }}</option>
                @endforeach
            </select>
            <div class="actions">
                <input type="date" name="from_date" value="{{ request('from_date') }}">
                <input type="date" name="to_date" value="{{ request('to_date') }}">
            </div>
            <button type="submit">Apply Filters</button>
        </form>
    </div>
@elseif($view === 'logs')
    <a class="back-link" href="{{ route('admin.attendance.index') }}">Back to Attendance</a>
    <div class="section-block">
        <h2>Attendance Logs</h2>
        <p><em>Attendance changes happen in QR Scanner; this section is read-only.</em></p>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Event</th>
                    <th>Scanned At</th>
                    <th>Scanner IP</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ optional($log->registration)->student_name }}</td>
                        <td>{{ optional($log->event)->title }}</td>
                        <td>{{ $log->scanned_at }}</td>
                        <td>{{ $log->scanner_ip }}</td>
                        <td>{{ $log->notes }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $logs->links('pagination::simple-default') }}
    </div>
@endif
@endsection

