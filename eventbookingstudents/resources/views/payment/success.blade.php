@extends('layouts.student')

@section('content')

<div style="margin-bottom: 16px;">
    <a href="{{ route('student.dashboard') }}" class="back-link">← Back to Dashboard</a>
</div>

<div class="section-block" style="background: #e8f5e9; border-color: #4caf50; text-align: center; max-width: 600px; margin: 0 auto;">
    <div style="font-size: 48px; margin-bottom: 16px;">✓</div>
    <h2 style="color: #2e7d32; margin: 0 0 16px;">Payment Successful!</h2>
    <p style="color: #555; margin-bottom: 24px;">Your registration has been confirmed.</p>

    <div style="background: white; padding: 24px; border-radius: 8px; margin-bottom: 24px; text-align: left;">
        <h3 style="color: #0a2f6c; margin: 0 0 16px; border-bottom: 2px solid #eee; padding-bottom: 8px;">Event Details</h3>
        <p style="margin: 8px 0;"><strong>Event:</strong> {{ $event->title }}</p>
        <p style="margin: 8px 0;"><strong>Organizer:</strong> {{ $event->organizer }}</p>
        <p style="margin: 8px 0;"><strong>Date:</strong> {{ $event->start_date->format('d M Y') }}</p>
        <p style="margin: 8px 0;"><strong>Venue:</strong> {{ $event->venue }}</p>
    </div>

    <div style="background: white; padding: 24px; border-radius: 8px; margin-bottom: 24px; text-align: left;">
        <h3 style="color: #0a2f6c; margin: 0 0 16px; border-bottom: 2px solid #eee; padding-bottom: 8px;">Registration Details</h3>
        <p style="margin: 8px 0;"><strong>Name:</strong> {{ $registration->student_name }}</p>
        <p style="margin: 8px 0;"><strong>Email:</strong> {{ $registration->student_email }}</p>
        <p style="margin: 8px 0;"><strong>Ticket Code:</strong> <strong style="color: #0b5ed7;">{{ $ticket->ticket_code }}</strong></p>
    </div>

    @if($ticket->qr_path)
        <div style="background: white; padding: 24px; border-radius: 8px; margin-bottom: 24px;">
            <h3 style="color: #0a2f6c; margin: 0 0 16px; text-align: center;">Your Ticket QR Code</h3>
            <div style="text-align: center;">
                @php
                    $qrBase64 = null;
                    $qrMime = 'image/svg+xml';
                    if ($ticket->qr_path) {
                        $qrPath = storage_path('app/' . $ticket->qr_path);
                        if (file_exists($qrPath)) {
                            $qrContent = file_get_contents($qrPath);
                            $qrBase64 = base64_encode($qrContent);
                            if (str_ends_with($ticket->qr_path, '.png')) {
                                $qrMime = 'image/png';
                            }
                        }
                    }
                    if (!$qrBase64 && $registration->qr_code) {
                        // Generate QR on-the-fly if file doesn't exist
                        $qrSvg = \App\Helpers\QRHelper::renderSvg($registration->qr_code, 300);
                        $qrBase64 = base64_encode($qrSvg);
                    }
                @endphp
                @if($qrBase64)
                    <img src="data:{{ $qrMime }};base64,{{ $qrBase64 }}" alt="Ticket QR Code" style="max-width: 300px; height: auto; border: 2px solid #ddd; padding: 10px; background: white;">
                @else
                    <p style="color: #999;">QR code will be available shortly.</p>
                @endif
            </div>
        </div>
    @endif

    <div style="margin-top: 24px;">
        <a href="{{ route('student.dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
        <a href="{{ route('student.ticket.show', $registration->id) }}" class="btn" style="background: #6c757d; color: white; margin-left: 12px;">View Full Ticket</a>
    </div>
</div>

@endsection

