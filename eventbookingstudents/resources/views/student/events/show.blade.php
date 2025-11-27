@extends('layouts.student')

@section('content')

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 20px;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error" style="margin-bottom: 20px;">{{ session('error') }}</div>
@endif

<div style="margin-bottom: 16px;">
    <a href="{{ route('student.dashboard', ['section' => 'available']) }}" class="back-link">← Back to Available Events</a>
</div>

<!-- Event Details Section -->
<div class="section-block" style="margin-bottom: 24px;">
    <h2 style="margin: 0 0 16px; color: #0a2f6c; font-size: 28px;">{{ $event->title }}</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <div class="detail-item">
            <strong>Organizer:</strong> {{ $event->organizer }}
        </div>
        <div class="detail-item">
            <strong>Date:</strong> 
            {{ $event->start_date->format('d M Y') }}
            @if($event->end_date && $event->end_date != $event->start_date)
                - {{ $event->end_date->format('d M Y') }}
            @endif
        </div>
        <div class="detail-item">
            <strong>Time:</strong> 
            {{ date('H:i', strtotime($event->start_time)) }}
            @if($event->end_time)
                - {{ date('H:i', strtotime($event->end_time)) }}
            @endif
        </div>
        <div class="detail-item">
            <strong>Venue:</strong> {{ $event->venue }}
        </div>
        <div class="detail-item">
            <strong>Seats:</strong> 
            {{ $event->capacity }} / {{ $event->registrations_count }} / 
            @if($event->seats_remaining > 0)
                <span style="color: #2e7d32; font-weight: 600;">{{ $event->seats_remaining }} remaining</span>
            @else
                <span class="tag tag-booked">All Booked</span>
            @endif
        </div>
        @if($event->faculty_coordinator_name || $event->faculty_coordinator_contact)
            <div class="detail-item">
                <strong>Faculty Coordinator:</strong>
                <div>{{ $event->faculty_coordinator_name ?? 'NA' }}</div>
                @if($event->faculty_coordinator_contact)
                    <div>Contact: {{ $event->faculty_coordinator_contact }}</div>
                @endif
            </div>
        @endif
        @if($event->student_coordinator_name || $event->student_coordinator_contact)
            <div class="detail-item">
                <strong>Student Coordinator:</strong>
                <div>{{ $event->student_coordinator_name ?? 'NA' }}</div>
                @if($event->student_coordinator_contact)
                    <div>Contact: {{ $event->student_coordinator_contact }}</div>
                @endif
            </div>
        @endif
    </div>

    <div style="margin-bottom: 16px;">
        @if($event->is_paid)
            <span class="tag tag-paid">Paid - ₹{{ number_format($event->amount, 2) }}</span>
        @else
            <span class="tag tag-free">Free</span>
        @endif
    </div>

    @if($event->description)
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
            <strong style="color: #0a2f6c; display: block; margin-bottom: 8px;">Description:</strong>
            <p style="color: #555; line-height: 1.6;">{{ $event->description }}</p>
        </div>
    @endif

    @if($pdfCount > 0)
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
            <strong style="color: #008B8B; display: block; margin-bottom: 12px;">📄 PDFs Available ({{ $pdfCount }}):</strong>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                @if($event->brochure_path)
                    <a href="#" onclick="openPdfModal('{{ route('student.events.brochure', $event->id) }}', 'Event Brochure'); return false;" class="pdf-link-simple">📑 View Brochure</a>
                @endif
                @if($event->attachment_path)
                    <a href="#" onclick="openPdfModal('{{ route('student.events.attachment', $event->id) }}', 'Event Attachment'); return false;" class="pdf-link-simple">📑 View Attachment</a>
                @endif
            </div>
        </div>
    @endif
</div>

<!-- Registration Section -->
@if($existingRegistration)
    @if($existingRegistration->payment_status === 'paid' && $existingRegistration->ticket)
        <div class="section-block" style="background: #e8f5e9; border-color: #4caf50;">
            <h3 style="color: #2e7d32; margin: 0 0 12px;">✓ You are registered for this event</h3>
            <p style="margin: 0; color: #555;">
                <strong>Registration Date:</strong> {{ $existingRegistration->registered_at->format('d M Y, h:i A') }}<br>
                <strong>Ticket Code:</strong> {{ $existingRegistration->ticket->ticket_code }}
            </p>
            <div style="margin-top: 12px;">
                <a href="#" onclick="openTicketModal('{{ route('student.ticket.show', $existingRegistration->id) }}'); return false;" class="btn btn-primary">View Ticket</a>
            </div>
        </div>
    @elseif($existingRegistration->payment_status === 'pending' && $event->is_paid)
        <div class="section-block" style="background: #fff3cd; border-color: #ffc107;">
            <h3 style="color: #856404; margin: 0 0 12px;">⚠ Payment Required</h3>
            <p style="margin: 0; color: #555; margin-bottom: 16px;">Your registration is incomplete. Please complete the payment to receive your ticket.</p>
            <div style="margin-top: 12px;">
                <a href="{{ route('events.register', $event->id) }}" class="btn btn-primary">Complete Payment</a>
            </div>
        </div>
    @else
        <div class="section-block" style="background: #e8f5e9; border-color: #4caf50;">
            <h3 style="color: #2e7d32; margin: 0 0 12px;">✓ You are registered for this event</h3>
            <p style="margin: 0; color: #555;">
                <strong>Registration Date:</strong> {{ $existingRegistration->registered_at->format('d M Y, h:i A') }}
            </p>
            @if($existingRegistration->qr_code)
                <div style="margin-top: 12px;">
                    <a href="#" onclick="openTicketModal('{{ route('student.ticket.show', $existingRegistration->id) }}'); return false;" class="btn btn-primary">View Ticket</a>
                </div>
            @endif
        </div>
    @endif
@elseif($event->seats_remaining <= 0)
    <div class="section-block" style="background: #ffebee; border-color: #ef5350;">
        <h3 style="color: #c62828; margin: 0;">All seats are booked for this event</h3>
    </div>
@else
    <div class="section-block">
        <h3 style="margin: 0 0 20px; color: #0a2f6c; font-size: 20px; border-bottom: 2px solid #008B8B; padding-bottom: 10px;">Register for this Event</h3>
        @if($event->is_paid && $event->amount > 0)
            <p style="margin-bottom: 16px; color: #555;">This is a paid event. Please fill in your details below and click the payment button to continue.</p>
        @endif
        <form method="POST" action="{{ route('student.events.register', $event->id) }}" id="registration-form">
            @csrf
            <div class="form-container-row">
                <div class="form-group-box">
                    <label class="form-label-bold">Full Name *</label>
                    <small class="form-hint">(This name will appear on your certificate)</small>
                    <input type="text" name="student_name" value="{{ old('student_name') }}" required class="form-input-box">
                </div>
                
                <div class="form-group-box">
                    <label class="form-label-bold">Email *</label>
                    <input type="email" name="student_email" value="{{ old('student_email') }}" required class="form-input-box">
                </div>
                
                <div class="form-group-box">
                    <label class="form-label-bold">Roll Number / Student ID *</label>
                    <input type="text" name="student_roll" value="{{ old('student_roll') }}" required class="form-input-box">
                </div>
                
                <div class="form-group-box">
                    <label class="form-label-bold">Phone (Optional)</label>
                    <input type="text" name="student_phone" value="{{ old('student_phone') }}" class="form-input-box">
                </div>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                @if($event->is_paid && $event->amount > 0)
                    <button type="submit" class="btn btn-primary" style="padding: 12px 32px; font-size: 15px; font-weight: 600;">Pay ₹{{ number_format($event->amount, 2) }} and Register</button>
                @else
                    <button type="submit" class="btn btn-primary" style="padding: 12px 32px; font-size: 15px; font-weight: 600;">Confirm Registration</button>
                @endif
            </div>
        </form>
    </div>
@endif

@endsection

