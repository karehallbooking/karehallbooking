<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Event Ticket - {{ $event->title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 0;
            margin: 0;
            background: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .ticket {
            width: 260px;
            max-width: 260px;
            border: 2px solid #000;
            border-radius: 8px;
            padding: 20px;
            background: #fff;
            position: relative;
            box-sizing: border-box;
            margin: 0 auto;
        }
        .ticket-header {
            text-align: center;
            padding-bottom: 16px;
            margin-bottom: 16px;
            border-bottom: 2px solid #000;
        }
        .ticket-header h1 {
            font-size: 18px;
            font-weight: bold;
            color: #000;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }
        .ticket-header .subtitle {
            font-size: 13px;
            color: #333;
            font-weight: 600;
        }
        .ticket-content {
            display: flex;
            flex-direction: column;
            gap: 14px;
            width: 100%;
            box-sizing: border-box;
        }
        .ticket-info {
            display: flex;
            flex-direction: column;
            width: 100%;
            box-sizing: border-box;
        }
        .ticket-info label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            font-weight: 600;
            width: 100%;
            box-sizing: border-box;
        }
        .ticket-info .value {
            font-size: 13px;
            font-weight: bold;
            color: #000;
            line-height: 1.4;
            width: 100%;
            box-sizing: border-box;
            word-wrap: break-word;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 16px;
            width: 100%;
            box-sizing: border-box;
        }
        .qr-section {
            text-align: center;
            margin: 16px 0;
            padding: 0;
            background: #fff;
            width: 100%;
            box-sizing: border-box;
        }
        .qr-section img {
            max-width: 180px;
            width: 180px;
            height: 180px;
            display: block;
            margin: 0 auto;
            box-sizing: border-box;
        }
        .ticket-footer {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
        }
        .ticket-id {
            font-size: 10px;
            color: #666;
            text-align: center;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="ticket-header">
            <h1>EVENT TICKET</h1>
            <div class="subtitle">{{ $event->title }}</div>
        </div>
        
        <div class="ticket-content">
            <div class="ticket-info">
                <label>Student Name</label>
                <div class="value">{{ $registration->student_name }}</div>
            </div>
            
            <div class="info-grid">
                <div class="ticket-info">
                    <label>Date</label>
                    <div class="value">
                        @php
                            $startDate = is_string($event->start_date) ? \Carbon\Carbon::parse($event->start_date) : $event->start_date;
                            $endDate = $event->end_date ? (is_string($event->end_date) ? \Carbon\Carbon::parse($event->end_date) : $event->end_date) : null;
                        @endphp
                        {{ $startDate->format('d M Y') }}
                        @if($endDate && $endDate->format('Y-m-d') != $startDate->format('Y-m-d'))
                            - {{ $endDate->format('d M Y') }}
                        @endif
                    </div>
                </div>
                
                <div class="ticket-info">
                    <label>Time</label>
                    <div class="value">
                        @if($event->start_time)
                            {{ date('H:i', strtotime($event->start_time)) }}
                            @if($event->end_time)
                                - {{ date('H:i', strtotime($event->end_time)) }}
                            @endif
                        @else
                            TBA
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="ticket-info">
                <label>Venue</label>
                <div class="value">{{ $event->venue ?? 'TBA' }}</div>
            </div>
            
            <div class="qr-section">
                @if(isset($qrSvgBase64))
                    <img src="{{ $qrSvgBase64 }}" alt="QR Code" />
                @else
                    {!! $qrSvg !!}
                @endif
            </div>
            
            <div class="ticket-id">
                Registration ID: #{{ $registration->id }}
            </div>
            
            <div class="ticket-footer">
                Present this QR code at the event entrance
            </div>
        </div>
    </div>
</body>
</html>

