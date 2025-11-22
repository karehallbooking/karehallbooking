@extends('layouts.admin')

@section('content')
<a class="back-link" href="{{ route('admin.registrations.index') }}">Back to Registrations</a>
<div class="card-grid">
    <div class="card">
        <h3>Registrations for {{ $event->title }}</h3>
        <p>Total {{ $registrations->total() }}</p>
    </div>
    <div class="card">
        <h3>Back</h3>
        <p>Return to all registrations.</p>
        <a href="{{ route('admin.registrations.index') }}">Back</a>
    </div>
</div>

<div class="section-block">
    <h2>{{ $event->title }} Registrations</h2>
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
            @foreach($registrations as $registration)
                <tr>
                    <td>{{ $registration->student_name }}</td>
                    <td>{{ $registration->student_email }}</td>
                    <td>{{ $registration->attendance_status }}</td>
                    <td>{{ $registration->payment_status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $registrations->links('pagination::simple-default') }}
</div>
@endsection

