<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { border-bottom: 1px solid #000; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <h1>Event Report: {{ $event->title }}</h1>
    <p><strong>Organized by:</strong> {{ $event->organizer }} ({{ $event->department }})</p>
    <p><strong>Dates:</strong> {{ optional($event->start_date)->format('Y-m-d') }} to {{ optional($event->end_date)->format('Y-m-d') }}</p>
    <p><strong>Time:</strong> {{ $event->start_time }} - {{ $event->end_time }}</p>
    <p><strong>Venue:</strong> {{ $event->venue }}</p>
    <p><strong>Status:</strong> {{ ucfirst($event->status) }}</p>
    <p><strong>Capacity:</strong> {{ $event->capacity }} | <strong>Registrations:</strong> {{ $event->registrations->count() }}</p>
    <p><strong>Pricing:</strong> {{ $event->is_paid ? 'Paid (Rs. ' . number_format($event->amount, 2) . ')' : 'Free' }}</p>

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
            @foreach($event->registrations as $registration)
                <tr>
                    <td>{{ $registration->student_name }}</td>
                    <td>{{ $registration->student_email }}</td>
                    <td>{{ $registration->attendance_status }}</td>
                    <td>{{ $registration->payment_status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>


