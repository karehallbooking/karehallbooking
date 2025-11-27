<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <h1>Event Summary</h1>
    <table>
        <thead>
            <tr>
                <th>Event</th>
                <th>Date Range</th>
                <th>Registrations</th>
            </tr>
        </thead>
        <tbody>
            @foreach($events as $event)
                <tr>
                    <td>{{ $event->title }}</td>
                    <td>{{ optional($event->start_date)->format('Y-m-d') }} - {{ optional($event->end_date)->format('Y-m-d') }}</td>
                    <td>{{ $event->registrations_count ?? $event->registrations()->where('payment_status', 'paid')->count() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>


