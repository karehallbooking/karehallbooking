@extends('layouts.student')

@section('content')

@if(!$selectedSection)
    <!-- Main Dashboard - Show Section Cards -->
    <div class="section-block" style="margin-bottom: 24px;">
        <h2 style="margin: 0 0 8px; color: #0a2f6c;">Student Dashboard</h2>
        <p style="margin: 0; color: #4b5d77;">Select a section to view your events, registrations, and certificates.</p>
    </div>

    <div class="section-grid">
        <a href="{{ route('student.dashboard', ['section' => 'available']) }}" class="section-card">
            <div>
                <h3>Available Events</h3>
                <p>Browse and register for upcoming events.</p>
            </div>
            <span>View Available Events →</span>
        </a>

        <a href="{{ route('student.dashboard', ['section' => 'upcoming']) }}" class="section-card">
            <div>
                <h3>Upcoming Events</h3>
                <p>View your registered upcoming events and download QR codes.</p>
            </div>
            <span>View Upcoming →</span>
        </a>

        <a href="{{ route('student.dashboard', ['section' => 'history']) }}" class="section-card">
            <div>
                <h3>History</h3>
                <p>Review your past event registrations and attendance.</p>
            </div>
            <span>View History →</span>
        </a>

        <a href="{{ route('student.dashboard', ['section' => 'certificates']) }}" class="section-card">
            <div>
                <h3>Certificates</h3>
                <p>Download your event participation certificates.</p>
            </div>
            <span>View Certificates →</span>
        </a>
    </div>

@else
    <!-- Section Content - Show when section is selected -->
    <div style="margin-bottom: 16px;">
        <a href="{{ route('student.dashboard') }}" class="back-link">← Back to Dashboard</a>
    </div>

    @if($selectedSection === 'available')
        <!-- Available Events Section -->
        <div class="section-block">
            <h2 class="section-title" style="margin-top: 0;">Available Events</h2>
        </div>
        @if($availableEvents->count() > 0)
            <div class="event-grid">
                @foreach($availableEvents as $event)
                    <div class="event-card" onclick="window.location='{{ route('student.events.show', $event->id) }}'" style="cursor: pointer;">
                        <h3>{{ $event->title }}</h3>
                        <p><strong>Organizer:</strong> {{ $event->organizer }}</p>
                        <p><strong>Department:</strong> {{ $event->department }}</p>
                        <p><strong>Date:</strong> 
                            {{ $event->start_date->format('d M Y') }}
                            @if($event->end_date && $event->end_date != $event->start_date)
                                - {{ $event->end_date->format('d M Y') }}
                            @endif
                        </p>
                        <p><strong>Time:</strong> 
                            {{ date('H:i', strtotime($event->start_time)) }}
                            @if($event->end_time)
                                - {{ date('H:i', strtotime($event->end_time)) }}
                            @endif
                        </p>
                        <p><strong>Venue:</strong> {{ $event->venue }}</p>
                        <p><strong>Seats:</strong> 
                            {{ $event->capacity }} / {{ $event->registrations_count }} / 
                            @if($event->seats_remaining > 0)
                                <span style="color: #2e7d32;">{{ $event->seats_remaining }} remaining</span>
                            @else
                                <span class="tag tag-booked">All Booked</span>
                            @endif
                        </p>
                        <p>
                            @if($event->is_paid)
                                <span class="tag tag-paid">Paid - ₹{{ number_format($event->amount, 2) }}</span>
                            @else
                                <span class="tag tag-free">Free</span>
                            @endif
                        </p>
                        @php
                            $pdfCount = 0;
                            if($event->brochure_path) $pdfCount++;
                            if($event->attachment_path) $pdfCount++;
                        @endphp
                        @if($pdfCount > 0)
                            <p style="margin-top: 12px; margin-bottom: 8px;">
                                <strong style="color: #008B8B;">📄 PDFs Available ({{ $pdfCount }}):</strong>
                            </p>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                @if($event->brochure_path)
                                    <a href="#" onclick="event.stopPropagation(); openPdfModal('{{ route('student.events.brochure', $event->id) }}', 'Event Brochure'); return false;" class="pdf-link-simple">📑 View Brochure</a>
                                @endif
                                @if($event->attachment_path)
                                    <a href="#" onclick="event.stopPropagation(); openPdfModal('{{ route('student.events.attachment', $event->id) }}', 'Event Attachment'); return false;" class="pdf-link-simple">📑 View Attachment</a>
                                @endif
                            </div>
                        @endif
                        
                        <button 
                            type="button" 
                            class="btn btn-primary" 
                            style="width: 100%; margin-top: 12px;"
                            onclick="event.stopPropagation(); window.location='{{ route('student.events.show', $event->id) }}'"
                            @if($event->seats_remaining <= 0) disabled @endif
                        >
                            {{ $event->seats_remaining > 0 ? 'View Details & Register' : 'All Booked' }}
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="section-block">
                <div class="empty-state">
                    <p>No events available at the moment.</p>
                </div>
            </div>
        @endif

    @elseif($selectedSection === 'upcoming')
        <!-- Upcoming Events Section -->
        <div class="section-block">
            <h2 class="section-title" style="margin-top: 0;">Upcoming Events</h2>
        </div>
        @if($upcomingRegistrations->count() > 0)
            <div class="section-block">
                @foreach($upcomingRegistrations as $registration)
                    <div class="list-item">
                        <div class="list-item-info">
                            <strong>{{ $registration->event->title }}</strong>
                            <span>
                                Date: {{ $registration->event->start_date->format('d M Y') }}
                                @if($registration->event->end_date && $registration->event->end_date != $registration->event->start_date)
                                    - {{ $registration->event->end_date->format('d M Y') }}
                                @endif
                            </span>
                            <span style="display: block; margin-top: 4px;">
                                Status: 
                                <span style="color: {{ $registration->payment_status === 'paid' || $registration->payment_status === 'free' ? '#2e7d32' : '#e65100' }};">
                                    {{ ucfirst($registration->payment_status) }}
                                </span>
                            </span>
                        </div>
                        <div>
                            @if($registration->qr_code)
                                <a href="#" onclick="openTicketModal('{{ route('student.ticket.show', $registration->id) }}'); return false;" class="btn btn-link">View Ticket</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="section-block">
                <div class="empty-state">
                    <p>You have no upcoming registered events.</p>
                </div>
            </div>
        @endif

    @elseif($selectedSection === 'history')
        <!-- History Section -->
        <div class="section-block">
            <h2 class="section-title" style="margin-top: 0;">History</h2>
        </div>
        @if($historyRegistrations->count() > 0)
            <div class="section-block">
                @foreach($historyRegistrations as $registration)
                    <div class="list-item">
                        <div class="list-item-info">
                            <strong>{{ $registration->event->title }}</strong>
                            <span>
                                Registered: {{ $registration->registered_at->format('d M Y, h:i A') }}
                            </span>
                            <span style="display: block; margin-top: 4px;">
                                Attendance: 
                                <span style="color: {{ $registration->attendance_status === 'present' ? '#2e7d32' : ($registration->attendance_status === 'absent' ? '#c62828' : '#666') }};">
                                    {{ ucfirst($registration->attendance_status) }}
                                </span>
                            </span>
                        </div>
                        <div>
                            @if($registration->certificate_issued && $registration->certificate)
                                <a href="{{ route('student.certificates.download', $registration->certificate->id) }}" target="_blank" class="btn btn-link">Download Certificate</a>
                            @else
                                <span style="color: #999; font-size: 13px;">Certificate not issued</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="section-block">
                <div class="empty-state">
                    <p>No past event registrations found.</p>
                </div>
            </div>
        @endif

    @elseif($selectedSection === 'certificates')
        <!-- Certificates Section -->
        <div class="section-block">
            <h2 class="section-title" style="margin-top: 0;">Certificates</h2>
        </div>
        @if($certificates->count() > 0)
            <div class="section-block">
                @foreach($certificates as $certificate)
                    <div class="list-item">
                        <div class="list-item-info">
                            <strong>{{ $certificate->event->title }}</strong>
                            <span>
                                Issued for: {{ $certificate->registration->student_name }}
                            </span>
                            <span style="display: block; margin-top: 4px;">
                                Event Date: {{ $certificate->event->start_date->format('d M Y') }}
                            </span>
                        </div>
                        <div>
                            <a href="{{ route('student.certificates.download', $certificate->id) }}" target="_blank" class="btn btn-link">Download Certificate</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="section-block">
                <div class="empty-state">
                    <p>No certificates available.</p>
                </div>
            </div>
        @endif
    @endif
@endif

<script>
    function toggleForm(eventId) {
        const form = document.getElementById('form-' + eventId);
        if (form) {
            form.classList.toggle('active');
        }
    }

</script>

@endsection
