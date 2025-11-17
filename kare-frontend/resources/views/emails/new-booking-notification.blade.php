<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            margin: -30px -30px 30px -30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .info-section {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        .info-section h2 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #667eea;
            font-size: 18px;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
        .facilities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .facility-badge {
            background-color: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #777;
            font-size: 12px;
        }
        .action-button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>📅 New Hall Booking Request</h1>
        </div>

        <div class="info-section">
            <h2>Booking Details</h2>
            <div class="info-row">
                <span class="info-label">Hall Name:</span>
                <span class="info-value">{{ $booking['hall_name'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Event Date:</span>
                <span class="info-value">{{ $booking['event_date'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Time:</span>
                <span class="info-value">{{ $booking['time_from'] }} - {{ $booking['time_to'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Purpose:</span>
                <span class="info-value">{{ $booking['purpose'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Seating Capacity:</span>
                <span class="info-value">{{ $booking['seating_capacity'] }} people</span>
            </div>
            @if(!empty($booking['facilities_required']))
            <div class="info-row">
                <span class="info-label">Facilities Required:</span>
                <div class="info-value">
                    <div class="facilities-list">
                        @foreach($booking['facilities_required'] as $facility)
                            <span class="facility-badge">{{ $facility }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="info-section">
            <h2>Organizer Information</h2>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value">{{ $organizer['name'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $organizer['email'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value">{{ $organizer['phone'] }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Department:</span>
                <span class="info-value">{{ $organizer['department'] }}</span>
            </div>
            @if(!empty($organizer['designation']))
            <div class="info-row">
                <span class="info-label">Designation:</span>
                <span class="info-value">{{ $organizer['designation'] }}</span>
            </div>
            @endif
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <p style="color: #666; margin-bottom: 15px;">Please review and approve/reject this booking request.</p>
            <a href="{{ url('/events') }}" class="action-button">View Booking Details</a>
        </div>

        <div class="footer">
            <p>This is an automated notification from KARE Hall Booking System.</p>
            <p>Booking ID: #{{ $event->id }}</p>
        </div>
    </div>
</body>
</html>

