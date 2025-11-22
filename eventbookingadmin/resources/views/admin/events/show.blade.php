@extends('layouts.admin')

@section('content')
<a class="back-link" href="{{ route('admin.events.index') }}">Back to Events</a>
<div class="card-grid">
    <div class="card">
        <h3>Event Overview</h3>
        <p>{{ $event->title }}</p>
    </div>
    <div class="card">
        <h3>Registrations</h3>
        <p>{{ $event->registrations->count() }} total</p>
    </div>
    <div class="card">
        <h3>Attendance Logs</h3>
        <p>{{ $event->attendanceLogs->count() }} scans</p>
    </div>
</div>

<div class="section-block">
    <h2>Event Details</h2>
    <p><strong>Organized by:</strong> {{ $event->organizer }} ({{ $event->department }})</p>
    <p><strong>Venue:</strong> {{ $event->venue }}</p>
    <p><strong>Dates:</strong> {{ optional($event->start_date)->format('Y-m-d') }} to {{ optional($event->end_date)->format('Y-m-d') }}</p>
    <p><strong>Time:</strong> {{ $event->start_time }} - {{ $event->end_time }}</p>
    <p><strong>Status:</strong> {{ ucfirst($event->status) }}</p>
    <p><strong>Capacity:</strong> {{ $event->capacity }}</p>
    <p><strong>Pricing:</strong> {{ $event->is_paid ? 'Paid (Rs. ' . number_format($event->amount, 2) . ')' : 'Free' }}</p>
    <p><strong>Description:</strong> {{ $event->description }}</p>
    @if($event->brochure_path)
        <p><strong>Brochure:</strong> {{ $event->brochure_path }}</p>
    @endif
    @if($event->attachment_path)
        <p><strong>Attachment:</strong> {{ $event->attachment_path }}</p>
    @endif
</div>

<div class="section-block">
    <h2>Registrations</h2>
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Email</th>
                <th>Attendance</th>
                <th>Payment</th>
            </tr>
        </thead>
        <tbody>
            @forelse($event->registrations as $registration)
                <tr>
                    <td>{{ $registration->student_name }}</td>
                    <td>{{ $registration->student_email }}</td>
                    <td>{{ $registration->attendance_status }}</td>
                    <td>{{ $registration->payment_status }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No registrations.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection


