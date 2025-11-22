@extends('layouts.admin')

@section('content')
<a class="back-link" href="{{ route('admin.registrations.index') }}">Back to Registrations</a>
<div class="card-grid">
    <div class="card">
        <h3>Edit Registration</h3>
        <p>Update student details.</p>
    </div>
    <div class="card">
        <h3>Download QR</h3>
        <p>Get the latest QR.</p>
        @if($registration->qr_code)
            <a href="{{ route('admin.registrations.qr.download', $registration->id) }}">Download</a>
        @endif
    </div>
    <div class="card">
        <h3>Back to List</h3>
        <p>Return to overview.</p>
        <a href="{{ route('admin.registrations.index') }}">Back</a>
    </div>
</div>

<div class="section-block">
    <h2>Edit Registration</h2>
    <form method="POST" action="{{ route('admin.registrations.update', $registration->id) }}">
        @csrf
        @method('PUT')
        <select name="event_id" required>
            @foreach($events as $event)
                <option value="{{ $event->id }}" @selected($registration->event_id == $event->id)>{{ $event->title }}</option>
            @endforeach
        </select>
        <input type="text" name="student_name" value="{{ old('student_name', $registration->student_name) }}" required>
        <input type="email" name="student_email" value="{{ old('student_email', $registration->student_email) }}" required>
        <input type="text" name="student_phone" value="{{ old('student_phone', $registration->student_phone) }}">
        <input type="text" name="student_id" value="{{ old('student_id', $registration->student_id) }}">
        <select name="payment_status">
            @foreach(['pending','paid','refunded'] as $status)
                <option value="{{ $status }}" @selected($registration->payment_status === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="attendance_status">
            @foreach(['absent','present'] as $status)
                <option value="{{ $status }}" @selected($registration->attendance_status === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit">Save</button>
    </form>
</div>
@endsection


