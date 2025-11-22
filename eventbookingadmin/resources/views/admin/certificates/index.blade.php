@extends('layouts.admin')

@section('content')
<div style="margin-bottom: 16px;">
    <a class="back-to-dashboard" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
</div>
<div class="section-block">
    <h2>Certificate Studio</h2>
    <p>Select an event to upload a template, generate certificates for all present attendees, and manage downloads.</p>

    <form method="GET" action="{{ route('admin.certificates.index') }}" class="form-row" style="max-width: 520px;">
        <label style="flex:1;">
            Event
            <select name="event_id" onchange="this.form.submit()">
                <option value="">Select event</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected($selectedEventId == $event->id)>
                        {{ $event->title }} ({{ optional($event->start_date)->format('M d, Y') }})
                    </option>
                @endforeach
            </select>
        </label>
        <div style="align-self:flex-end;">
            <button type="submit">Load</button>
        </div>
    </form>
</div>

@if(!$selectedEvent)
    <div class="card" style="max-width: 520px;">
        <h3>No event selected</h3>
        <p>Pick an event above to view eligible students and template tools.</p>
    </div>
@else
    <div class="card-grid" style="margin-bottom:24px;">
        <div class="card">
            <h3>Event</h3>
            <p>{{ $selectedEvent->title }}</p>
        </div>
        <div class="card">
            <h3>Present attendees</h3>
            <p>{{ $registrations instanceof \Illuminate\Pagination\LengthAwarePaginator ? $registrations->total() : $registrations->count() }}</p>
        </div>
        <div class="card">
            <h3>Template status</h3>
            <p>{{ $templateExists ? 'Uploaded' : 'Missing' }}</p>
        </div>
        <div class="card">
            <h3>Bulk download</h3>
            <p>Grab all generated PDFs.</p>
            <a href="{{ route('admin.certificates.download-all', $selectedEvent->id) }}">Download ZIP</a>
        </div>
    </div>

    <div class="section-block">
        <h3>Step 2: Upload template (PDF)</h3>
        <form method="POST" action="{{ route('admin.certificates.upload-template', $selectedEvent->id) }}" enctype="multipart/form-data" class="form-row">
            @csrf
            <label style="flex:1;">
                PDF with placeholders {STUDENT_NAME}, {EVENT_NAME}, {EVENT_DATE}
                <input type="file" name="template_pdf" accept="application/pdf" required>
            </label>
            <div style="align-self:flex-end;">
                <button type="submit">Upload Template</button>
            </div>
        </form>
        @if($templateExists)
            <p style="margin-top:8px;">Current template: {{ $selectedEvent->certificate_template_path }}</p>
        @endif
    </div>

    <div class="section-block">
        <h3>Step 3: Generate certificates</h3>
        <form method="POST" action="{{ route('admin.certificates.generate.event', $selectedEvent->id) }}">
            @csrf
            <p>Creates a PDF for every attendee with attendance = present.</p>
            <button type="submit" {{ $templateExists ? '' : 'disabled' }}>Generate for {{ $selectedEvent->title }}</button>
        </form>
    </div>

    <div class="section-block">
        <h3>Eligible attendees</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Attendance</th>
                    <th>Certificate</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registrations as $registration)
                    <tr>
                        <td>{{ $registration->student_name }}</td>
                        <td>{{ $registration->student_email }}</td>
                        <td>{{ ucfirst($registration->attendance_status) }}</td>
                        <td>
                            @if($registration->certificate && !$registration->certificate->is_revoked)
                                Issued {{ optional($registration->certificate_issued_at)->format('Y-m-d') }}
                            @else
                                Not issued
                            @endif
                        </td>
                        <td class="actions">
                            @if($registration->certificate && !$registration->certificate->is_revoked)
                                <a href="{{ route('admin.certificates.download', $registration->certificate->id) }}">Download</a>
                                <form method="POST" action="{{ route('admin.certificates.revoke', $registration->certificate->id) }}">
                                    @csrf
                                    <button type="submit">Revoke</button>
                                </form>
                            @else
                                <span style="color:#888;">Generate via Step 3</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No attendees marked present yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($registrations instanceof \Illuminate\Contracts\Pagination\Paginator)
            {{ $registrations->links('pagination::simple-default') }}
        @endif
    </div>
@endif
@endsection

