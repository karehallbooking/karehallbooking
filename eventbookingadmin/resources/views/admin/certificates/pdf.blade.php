<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #000; }
        .wrapper { border: 2px solid #000; padding: 30px; }
        h1 { text-align: center; text-transform: uppercase; }
        p { line-height: 1.5; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h1>Certificate of Participation</h1>
        <p>{!! nl2br(e($body)) !!}</p>
        <p><strong>Student:</strong> {{ $registration->student_name }}</p>
        <p><strong>Event:</strong> {{ $event->title }}</p>
        <p><strong>Dates:</strong> {{ optional($event->start_date)->format('Y-m-d') }} to {{ optional($event->end_date)->format('Y-m-d') }}</p>
    </div>
</body>
</html>


