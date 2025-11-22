@extends('layouts.admin')

@section('content')
<style>
    .pdf-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.85);
        overflow: auto;
    }
    .pdf-modal.active {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .pdf-modal-header {
        width: 100%;
        max-width: 95%;
        background: #fff;
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 8px 8px 0 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    .pdf-modal-header h3 {
        margin: 0;
        color: #0a4a8a;
        font-size: 18px;
    }
    .pdf-modal-close {
        background: linear-gradient(135deg, #d8435e 0%, #c62828 100%);
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        font-size: 14px;
    }
    .pdf-modal-close:hover {
        background: linear-gradient(135deg, #c62828 0%, #b71c1c 100%);
    }
    .pdf-modal-body {
        width: 100%;
        max-width: 95%;
        height: calc(100% - 60px);
        background: #525252;
        border-radius: 0 0 8px 8px;
        overflow: hidden;
    }
    .pdf-modal-body iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
</style>

<div style="margin-bottom: 16px;">
    <a class="back-to-dashboard" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
</div>

<!-- PDF Modal -->
<div id="pdfModal" class="pdf-modal">
    <div class="pdf-modal-header">
        <h3 id="pdfModalTitle">PDF Viewer</h3>
        <button class="pdf-modal-close" onclick="closePdfModal()">Close</button>
    </div>
    <div class="pdf-modal-body">
        <iframe id="pdfFrame" src=""></iframe>
    </div>
</div>

@if(!$selectedEvent)
    <div class="section-block">
        <h2>All Events</h2>
        <p>Select an event to scan QR codes and view attendance.</p>
    </div>

    @if($events->count() > 0)
        <div class="card-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
            @foreach($events as $event)
                <div class="card" style="cursor: pointer;" onclick="window.location='{{ route('admin.scanner.index', ['event_id' => $event->id]) }}'">
                    <h3>{{ $event->title }}</h3>
                    <p style="margin: 8px 0;"><strong>Organizer:</strong> {{ $event->organizer }}</p>
                    <p style="margin: 8px 0;"><strong>Department:</strong> {{ $event->department }}</p>
                    <p style="margin: 8px 0;"><strong>Date:</strong> 
                        {{ optional($event->start_date)->format('M d, Y') }}
                        @if($event->end_date && $event->end_date != $event->start_date)
                            - {{ optional($event->end_date)->format('M d, Y') }}
                        @endif
                    </p>
                    <p style="margin: 8px 0;"><strong>Time:</strong> 
                        {{ $event->start_time }}
                        @if($event->end_time)
                            - {{ $event->end_time }}
                        @endif
                    </p>
                    <p style="margin: 8px 0;"><strong>Venue:</strong> {{ $event->venue }}</p>
                    <p style="margin: 8px 0;"><strong>Capacity:</strong> {{ $event->capacity }} | <strong>Registered:</strong> {{ $event->registrations_count ?? 0 }}</p>
                    <p style="margin: 8px 0;"><strong>Status:</strong> {{ ucfirst($event->status) }}</p>
                    @if($event->brochure_path)
                        <p style="margin: 8px 0;">
                            <strong>Brochure:</strong> 
                            <a href="#" onclick="event.stopPropagation(); openPdfModal('{{ route('admin.events.brochure.download', $event->id) }}', 'Event Brochure'); return false;" style="display: inline-block; padding: 6px 12px; background: linear-gradient(135deg, #1E90FF 0%, #0A66C2 100%); color: #ffffff; font-weight: bold; text-decoration: none; border-radius: 6px; font-size: 12px; margin-left: 8px; cursor: pointer;">View PDF</a>
                        </p>
                    @endif
                    @if($event->attachment_path)
                        <p style="margin: 8px 0;">
                            <strong>Attachment:</strong> 
                            <a href="#" onclick="event.stopPropagation(); openPdfModal('{{ route('admin.events.attachment.download', $event->id) }}', 'Event Attachment'); return false;" style="display: inline-block; padding: 6px 12px; background: linear-gradient(135deg, #1E90FF 0%, #0A66C2 100%); color: #ffffff; font-weight: bold; text-decoration: none; border-radius: 6px; font-size: 12px; margin-left: 8px; cursor: pointer;">View PDF</a>
                        </p>
                    @endif
                    <div style="margin-top: 12px;">
                        <a href="{{ route('admin.scanner.index', ['event_id' => $event->id]) }}" style="display: inline-block; padding: 8px 16px; background: #0c5fd1; color: #fff; text-decoration: none; border-radius: 6px; font-size: 13px;">Scan QR for This Event</a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card" style="max-width: 600px; margin: 20px auto;">
            <h3>No Events Available</h3>
            <p>There are no events to scan QR codes for.</p>
        </div>
    @endif
@else
    <a class="back-link" href="{{ route('admin.scanner.index') }}">Back to All Events</a>

    <div class="card-grid" style="margin-bottom: 24px;">
        <div class="card">
            <h3>Event</h3>
            <p><strong>{{ $selectedEvent->title }}</strong></p>
        </div>
        <div class="card">
            <h3>Date</h3>
            <p>
                {{ optional($selectedEvent->start_date)->format('M d, Y') }}
                @if($selectedEvent->end_date && $selectedEvent->end_date != $selectedEvent->start_date)
                    - {{ optional($selectedEvent->end_date)->format('M d, Y') }}
                @endif
            </p>
        </div>
        <div class="card">
            <h3>Venue</h3>
            <p>{{ $selectedEvent->venue }}</p>
        </div>
        <div class="card">
            <h3>Registrations</h3>
            <p>{{ $selectedEvent->registrations_count ?? 0 }} / {{ $selectedEvent->capacity }}</p>
        </div>
    </div>

    @if($result)
        <div class="alert {{ $result['status'] === 'error' ? 'alert-error' : ($result['status'] === 'confirm' ? 'alert-info' : 'alert-success') }}" style="margin-bottom: 20px;">
            {{ $result['message'] }}
            @if($result['status'] === 'confirm' && !empty($result['student']))
                <div style="margin-top: 12px; padding: 12px; background: #f0f8ff; border-radius: 6px; border: 1px solid #b8d4f0;">
                    <h4 style="margin: 0 0 8px; color: #0a4a8a;">Student Details:</h4>
                    <p style="margin: 4px 0;"><strong>Name:</strong> {{ $result['student'] }}</p>
                    <p style="margin: 4px 0;"><strong>Email:</strong> {{ $result['student_email'] ?? '—' }}</p>
                    <p style="margin: 4px 0;"><strong>Roll No:</strong> {{ $result['student_id'] ?? '—' }}</p>
                    <p style="margin: 4px 0;"><strong>Registration ID:</strong> #{{ $result['reg_id'] }}</p>
                </div>
                <form method="POST" action="{{ route('admin.scanner.confirm') }}" style="margin-top: 12px;">
                    @csrf
                    <button type="submit" style="padding: 10px 20px; background: linear-gradient(135deg, #23a96a 0%, #1e7e4a 100%); color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; margin-right: 8px;">Confirm & Mark Present</button>
                    <a href="{{ route('admin.scanner.index', ['event_id' => $selectedEvent->id]) }}" style="padding: 10px 20px; background: #999; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block;">Cancel</a>
                </form>
            @elseif(!empty($result['student']) && $result['status'] === 'success')
                <div style="margin-top: 8px;"><strong>Student:</strong> {{ $result['student'] }} (Reg #{{ $result['reg_id'] }})</div>
                <div><strong>Time:</strong> {{ $result['time'] ?? '—' }}</div>
                @if(!empty($result['log_id']))
                    <form method="POST" action="{{ route('admin.scanner.revoke') }}" style="margin-top: 10px;">
                        @csrf
                        <input type="hidden" name="log_id" value="{{ $result['log_id'] }}">
                        <button type="submit" style="padding: 6px 12px; background: #d8435e; color: #fff; border: none; border-radius: 4px; cursor: pointer;">Undo / Revoke</button>
                    </form>
                @endif
            @endif
        </div>
    @endif

    <!-- QR Scanner Section - Two Columns -->
    <div class="section-block">
        <h2>QR Scanner</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 16px;">
            <!-- Left: Upload QR Code -->
            <div style="border: 2px solid #b8d4f0; border-radius: 8px; padding: 16px; background: #fff;">
                <h3 style="margin: 0 0 12px; color: #0a4a8a; font-size: 16px;">Upload QR Code</h3>
                <div id="upload-scanner">
                    <div id="qr-file-reader" style="display: none;"></div>
                    <form method="POST" action="{{ route('admin.scanner.scan') }}" id="qr-upload-form">
                        @csrf
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="event_id" value="{{ $selectedEvent->id }}">
                        <input type="hidden" name="qr_value" id="qr-value-input">
                        <label style="display: block; margin-bottom: 12px; font-size: 13px; color: #666;">
                            <strong style="display: block; margin-bottom: 6px; color: #333;">Select QR Image</strong>
                            <input type="file" id="qr-file-input" accept="image/*" style="display: block; padding: 8px; border: 2px solid #b8d4f0; border-radius: 6px; width: 100%; font-size: 13px;" required>
                        </label>
                        <button type="button" id="scan-file-btn" style="padding: 10px 20px; background: linear-gradient(135deg, #1E90FF 0%, #0A66C2 100%); color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; font-size: 13px;">Upload & Scan</button>
                    </form>
                </div>
            </div>

            <!-- Right: Scan QR Code (Camera) -->
            <div style="border: 2px solid #b8d4f0; border-radius: 8px; padding: 16px; background: #fff;">
                <h3 style="margin: 0 0 12px; color: #0a4a8a; font-size: 16px;">Scan QR Code</h3>
                <div id="camera-scanner">
                    <div id="camera-container" style="margin-bottom: 12px;">
                        <div id="qr-reader" style="width: 100%; max-width: 100%; margin: 0 auto; min-height: 200px; max-height: 250px;"></div>
                    </div>
                    <form method="POST" action="{{ route('admin.scanner.scan') }}" id="camera-form">
                        @csrf
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="event_id" value="{{ $selectedEvent->id }}">
                        <input type="hidden" name="qr_value" id="camera-qr-value">
                        <button type="submit" id="camera-submit-btn" style="display: none; padding: 10px 20px; background: linear-gradient(135deg, #1E90FF 0%, #0A66C2 100%); color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; font-size: 13px; margin-bottom: 8px;">Verify & Show Details</button>
                    </form>
                    <button type="button" id="start-camera-btn" style="padding: 10px 20px; background: linear-gradient(135deg, #23a96a 0%, #1e7e4a 100%); color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; font-size: 13px; margin-bottom: 8px;">Start Camera</button>
                    <button type="button" id="stop-camera-btn" style="display: none; padding: 10px 20px; background: linear-gradient(135deg, #d8435e 0%, #c62828 100%); color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; font-size: 13px;">Stop Camera</button>
                </div>
            </div>
        </div>
    </div>

    <div class="section-block">
        <h2>Students List - {{ $selectedEvent->title }}</h2>
        <p>Present and absent students for this event.</p>
        
        @php
            $presentCount = $students->where('attendance_status', 'present')->count();
            $absentCount = $students->where('attendance_status', 'absent')->count();
            $pendingCount = $students->where('attendance_status', 'pending')->count();
            $isEventComplete = $selectedEvent->end_date && \Carbon\Carbon::parse($selectedEvent->end_date)->isPast();
        @endphp

        <div class="card-grid" style="margin-bottom: 20px; grid-template-columns: repeat(3, 1fr);">
            <div class="card">
                <h3>Present</h3>
                <p style="font-size: 24px; font-weight: bold; color: #23a96a;">{{ $presentCount }}</p>
            </div>
            <div class="card">
                <h3>Absent</h3>
                <p style="font-size: 24px; font-weight: bold; color: #d8435e;">{{ $absentCount }}</p>
            </div>
            <div class="card">
                <h3>Pending</h3>
                <p style="font-size: 24px; font-weight: bold; color: #666;">{{ $pendingCount }}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Reg ID</th>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Roll No</th>
                    <th>Registered At</th>
                    <th>Attendance Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    <tr style="background-color: {{ $student->attendance_status === 'present' ? '#e3f7ef' : ($student->attendance_status === 'absent' ? '#fdecef' : '#f5f5f5') }};">
                        <td>#{{ $student->id }}</td>
                        <td><strong>{{ $student->student_name }}</strong></td>
                        <td>{{ $student->student_email }}</td>
                        <td>{{ $student->student_id ?? '—' }}</td>
                        <td>{{ optional($student->registered_at)->format('Y-m-d H:i') }}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="padding: 4px 8px; border-radius: 4px; font-weight: 600; 
                                    background-color: {{ $student->attendance_status === 'present' ? '#23a96a' : ($student->attendance_status === 'absent' ? '#d8435e' : '#999') }};
                                    color: #fff;
                                    font-size: 12px;">
                                    {{ ucfirst($student->attendance_status) }}
                                </span>
                                @if($student->attendance_status === 'pending' && $isEventComplete)
                                    <form method="POST" action="{{ route('admin.scanner.mark-absent') }}" style="display: inline; margin: 0;">
                                        @csrf
                                        <input type="hidden" name="registration_id" value="{{ $student->id }}">
                                        <button type="submit" style="padding: 4px 12px; background: #d8435e; color: #fff; border: none; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer;">Mark as Absent</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No registrations found for this event.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    // Get CSRF token from meta tag or form
    function getCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            return metaTag.getAttribute('content');
        }
        const csrfInput = document.querySelector('input[name="_token"]');
        if (csrfInput) {
            return csrfInput.value;
        }
        return null;
    }

    function openPdfModal(pdfUrl, title) {
        document.getElementById('pdfModalTitle').textContent = title;
        document.getElementById('pdfFrame').src = pdfUrl;
        document.getElementById('pdfModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closePdfModal() {
        document.getElementById('pdfModal').classList.remove('active');
        document.getElementById('pdfFrame').src = '';
        document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside the PDF viewer
    document.getElementById('pdfModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePdfModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePdfModal();
        }
    });

    // QR Code Scanner Implementation
    let html5QrCode = null;

    // Initialize file upload scanner
    document.addEventListener('DOMContentLoaded', function() {
        initFileScanner();
        initCameraControls();
    });

    // File upload handler
    function initFileScanner() {
        const fileInput = document.getElementById('qr-file-input');
        const qrValueInput = document.getElementById('qr-value-input');
        const form = document.getElementById('qr-upload-form');
        const scanBtn = document.getElementById('scan-file-btn');
        const qrReaderDiv = document.getElementById('qr-file-reader');

        if (!fileInput || !qrValueInput || !form || !scanBtn || !qrReaderDiv) return;

        // Initialize Html5Qrcode with a hidden div
        const fileQrCode = new Html5Qrcode("qr-file-reader");

        scanBtn.addEventListener('click', function() {
            const file = fileInput.files[0];
            if (!file) {
                alert('Please select a QR code image first.');
                return;
            }

            // Show loading state
            scanBtn.disabled = true;
            scanBtn.textContent = 'Scanning QR Code...';

            // Use html5-qrcode to decode from file
            fileQrCode.scanFile(file, true)
                .then(decodedText => {
                    console.log('QR Code decoded:', decodedText);
                    qrValueInput.value = decodedText;
                    
                    // Ensure CSRF token is present and valid before submitting
                    let csrfInput = form.querySelector('input[name="_token"]');
                    const token = getCsrfToken();
                    
                    if (!token) {
                        alert('Session expired. Please refresh the page and try again.');
                        location.reload();
                        return;
                    }
                    
                    // Update or add CSRF token
                    if (!csrfInput) {
                        csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        form.appendChild(csrfInput);
                    }
                    csrfInput.value = token; // Always update with latest token
                    
                    scanBtn.disabled = false;
                    scanBtn.textContent = 'Upload & Scan QR';
                    // Submit form to verify and fetch details
                    form.submit();
                })
                .catch(err => {
                    alert('Failed to decode QR code from image.\n\nPlease ensure:\n- The image contains a valid QR code\n- The image is clear and not blurry\n- Try using the camera scanner instead\n\nError: ' + (err.message || 'Unknown error'));
                    console.error('QR Scan Error:', err);
                    scanBtn.disabled = false;
                    scanBtn.textContent = 'Upload & Scan QR';
                    fileInput.value = '';
                });
        });
    }

    // Camera scanner controls
    function initCameraControls() {
        const startBtn = document.getElementById('start-camera-btn');
        const stopBtn = document.getElementById('stop-camera-btn');
        const qrReaderDiv = document.getElementById('qr-reader');
        const qrValueInput = document.getElementById('camera-qr-value');
        const submitBtn = document.getElementById('camera-submit-btn');

        if (!startBtn || !stopBtn || !qrReaderDiv) return;

        startBtn.addEventListener('click', function() {
            startCameraScanner();
        });

        stopBtn.addEventListener('click', function() {
            stopCameraScanner();
        });

        function startCameraScanner() {
            if (html5QrCode) {
                stopCameraScanner();
            }

            html5QrCode = new Html5Qrcode("qr-reader");
            
            startBtn.style.display = 'none';
            stopBtn.style.display = 'block';
            submitBtn.style.display = 'none';
            qrValueInput.value = '';

            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 200, height: 200 }
                },
                (decodedText, decodedResult) => {
                    console.log('QR Code scanned:', decodedText);
                    if (qrValueInput) {
                        qrValueInput.value = decodedText;
                    }
                    if (submitBtn) {
                        submitBtn.style.display = 'block';
                        submitBtn.textContent = 'Verify & Show Details';
                    }
                    // Auto-stop after successful scan
                    stopCameraScanner();
                },
                (errorMessage) => {
                    // Ignore scanning errors (continuous scanning)
                }
            ).catch(err => {
                console.error("Unable to start camera:", err);
                alert('Camera access denied or not available.\n\nPlease:\n- Allow camera permissions\n- Use a device with a camera\n- Try the Upload QR option instead');
                startBtn.style.display = 'block';
                stopBtn.style.display = 'none';
            });
        }

        function stopCameraScanner() {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    html5QrCode.clear();
                    html5QrCode = null;
                }).catch(err => {
                    console.error("Error stopping camera:", err);
                    html5QrCode = null;
                });
            }
            startBtn.style.display = 'block';
            stopBtn.style.display = 'none';
        }
    }
</script>
@endsection
