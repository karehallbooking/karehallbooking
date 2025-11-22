@extends('layouts.admin')

@section('content')
<div style="margin-bottom: 16px;">
    <a class="back-to-dashboard" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
</div>

@php($viewEventId = request('view_event_id'))

@if(!$viewEventId)
    <div class="section-block">
        <h2>All Bookings</h2>
        @if($events->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Event ID</th>
                        <th>Title</th>
                        <th>Organizer</th>
                        <th>Department</th>
                        <th>Date Range</th>
                        <th>Time</th>
                        <th>Venue</th>
                        <th>Capacity</th>
                        <th>Registrations</th>
                        <th>Seats Available</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($events as $event)
                        @php($remaining = max(0, $event->capacity - ($event->registrations_count ?? 0)))
                        <tr>
                            <td>#{{ $event->id }}</td>
                            <td><strong>{{ $event->title }}</strong></td>
                            <td>{{ $event->organizer }}</td>
                            <td>{{ $event->department }}</td>
                            <td>
                                {{ optional($event->start_date)->format('M d, Y') }}
                                @if($event->end_date && $event->end_date != $event->start_date)
                                    - {{ optional($event->end_date)->format('M d, Y') }}
                                @endif
                            </td>
                            <td>
                                {{ $event->start_time }}
                                @if($event->end_time)
                                    - {{ $event->end_time }}
                                @endif
                            </td>
                            <td>{{ $event->venue }}</td>
                            <td>{{ $event->capacity }}</td>
                            <td>{{ $event->registrations_count ?? 0 }}</td>
                            <td>
                                {{ $remaining }}
                                @if($remaining <= 0)
                                    <span style="color:#c62828;font-weight:600;">All Booked</span>
                                @endif
                            </td>
                            <td>{{ ucfirst($event->status) }}</td>
                            <td>
                                <form method="GET" action="{{ route('admin.registrations.index') }}" style="display: inline;">
                                    <input type="hidden" name="view_event_id" value="{{ $event->id }}">
                                    <button type="submit">View Registration Details</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="card" style="max-width: 600px; margin: 20px auto;">
                <h3>No Bookings Available</h3>
                <p>There are no events with registrations at this time.</p>
            </div>
        @endif
    </div>
@else
    @if($selectedEvent)
        <a class="back-link" href="{{ route('admin.registrations.index') }}">Back to All Bookings</a>
        
        <div class="card-grid" style="margin-bottom: 24px;">
            <div class="card">
                <h3>Event</h3>
                <p><strong>{{ $selectedEvent->title }}</strong></p>
            </div>
            <div class="card">
                <h3>Capacity</h3>
                <p>{{ $selectedEvent->capacity }}</p>
            </div>
            <div class="card">
                <h3>Registrations</h3>
                <p>{{ $selectedEvent->registrations_count ?? 0 }}</p>
            </div>
            <div class="card">
                <h3>Seats Available</h3>
                <p>
                    @php($remaining = max(0, $selectedEvent->capacity - ($selectedEvent->registrations_count ?? 0)))
                    {{ $remaining }}
                    @if($remaining <= 0)
                        <span style="color:#c62828;font-weight:600;">All Booked</span>
                    @endif
                </p>
            </div>
            <div class="card">
                <h3>Date</h3>
                <p>
                    {{ optional($selectedEvent->start_date)->format('M d, Y') }}
                    @if($selectedEvent->end_date && $selectedEvent->end_date != $selectedEvent->start_date)
                        - {{ optional($selectedEvent->end_date)->format('M d, Y') }}
                    @endif
                </p>
            </div>
            <div class="card">
                <h3>Venue</h3>
                <p>{{ $selectedEvent->venue }}</p>
            </div>
        </div>

        <div class="section-block">
            <h2>Registration Details</h2>
            <p>All registrations for <strong>{{ $selectedEvent->title }}</strong>.</p>
            @if($registrations && $registrations->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Reg ID</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Roll No</th>
                            <th>Registered At</th>
                            <th>Payment</th>
                            <th>Attendance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registrations as $registration)
                            <tr>
                                <td>#{{ $registration->id }}</td>
                                <td>{{ $registration->student_name }}</td>
                                <td>{{ $registration->student_email }}</td>
                                <td>{{ $registration->student_id ?? '—' }}</td>
                                <td>{{ optional($registration->registered_at)->format('Y-m-d H:i') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.registrations.payment-status', $registration->id) }}">
                                        @csrf
                                        <select name="payment_status" style="min-width:120px;">
                                            @foreach(['paid' => 'Paid', 'pending' => 'Pending', 'not_required' => 'Not Required', 'refunded' => 'Refunded'] as $value => $label)
                                                <option value="{{ $value }}" @selected($registration->payment_status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit">Update</button>
                                    </form>
                                </td>
                                <td>{{ ucfirst($registration->attendance_status) }}</td>
                                <td class="actions">
                                    <a href="{{ route('admin.registrations.edit', $registration->id) }}">View details</a>
                                    @if($registration->qr_code)
                                        <a href="{{ route('admin.registrations.qr.download', $registration->id) }}">Download QR</a>
                                    @else
                                        <form method="POST" action="{{ route('admin.registrations.qr.regenerate', $registration->id) }}">
                                            @csrf
                                            <button type="submit">Generate QR</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($registrations instanceof \Illuminate\Contracts\Pagination\Paginator)
                    {{ $registrations->links('pagination::simple-default') }}
                @endif
            @else
                <p>No registrations found for this event.</p>
            @endif
        </div>
    @else
        <div class="card" style="max-width: 600px; margin: 20px auto;">
            <h3>Event Not Found</h3>
            <p>The selected event could not be found.</p>
            <a href="{{ route('admin.registrations.index') }}">Back to All Bookings</a>
        </div>
    @endif
@endif
@endsection

