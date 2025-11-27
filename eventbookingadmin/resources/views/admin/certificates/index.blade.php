@extends('layouts.admin')

@section('content')
<div style="margin-bottom: 16px;">
    <a class="back-to-dashboard" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
</div>
<div class="section-block">
    <h2>Certificate Studio</h2>
    <p>Select an event to upload a template, generate certificates for all present attendees, and manage downloads.</p>

    <form method="GET" action="{{ route('admin.certificates.index') }}" class="form-row" style="max-width: 520px;">
        <label style="flex:1;">
            Event
            <select name="event_id" onchange="this.form.submit()">
                <option value="">Select event</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected($selectedEventId == $event->id)>
                        {{ $event->title }} ({{ optional($event->start_date)->format('M d, Y') }})
                    </option>
                @endforeach
            </select>
        </label>
        <div style="align-self:flex-end;">
            <button type="submit">Load</button>
        </div>
    </form>
</div>

@if(!$selectedEvent)
    <div class="card" style="max-width: 520px;">
        <h3>No event selected</h3>
        <p>Pick an event above to view eligible students and template tools.</p>
    </div>
@else
    <div class="card-grid" style="margin-bottom:24px;">
        <div class="card">
            <h3>Event</h3>
            <p>{{ $selectedEvent->title }}</p>
        </div>
        <div class="card">
            <h3>Present attendees</h3>
            <p>{{ $registrations instanceof \Illuminate\Pagination\LengthAwarePaginator ? $registrations->total() : $registrations->count() }}</p>
        </div>
        <div class="card">
            <h3>Template status</h3>
            <p>{{ $templateExists ? 'Uploaded' : 'Missing' }}</p>
        </div>
        <div class="card">
            <h3>Bulk download</h3>
            <p>Grab all generated PDFs.</p>
            <a href="{{ route('admin.certificates.download-all', $selectedEvent->id) }}">Download ZIP</a>
        </div>
    </div>

    <div class="section-block">
        <h3>Step 2: Upload Background Image & Configure Text</h3>
        <form method="POST" action="{{ route('admin.certificates.upload-template', $selectedEvent->id) }}" enctype="multipart/form-data" onsubmit="return validateCertificateForm(this);">
            @csrf
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                    Background Template (PDF Only)
                </label>
                <input type="file" name="template_file" accept=".pdf" style="padding: 8px; border: 2px solid #ddd; border-radius: 4px; width: 100%; max-width: 500px;" required>
                <p style="margin-top: 4px; font-size: 13px; color: #666;">
                    Upload a PDF certificate background template. Recommended size: 1080×720px.
                    @if(!isset($imagickAvailable) || !$imagickAvailable)
                        <span style="color: #d32f2f; font-weight: 600; display: block; margin-top: 8px;">⚠ PDF uploads require Imagick extension. See installation instructions below.</span>
                    @else
                        <span style="color: #2e7d32; font-weight: 600; display: block; margin-top: 8px;">✓ PDF support enabled (Imagick detected)</span>
                    @endif
                </p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                    Text Before Student Name
                </label>
                <input type="text" name="certificate_text_prefix" value="{{ old('certificate_text_prefix', $selectedEvent->certificate_text_prefix) }}" placeholder="e.g., This is to certify that Mr./Ms." style="padding: 10px; border: 2px solid #ddd; border-radius: 4px; width: 100%; max-width: 500px; font-size: 14px;">
                <p style="margin-top: 4px; font-size: 13px; color: #666;">This text will appear before the student's name</p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                    Text Before Date
                </label>
                <input type="text" name="certificate_text_before_date" value="{{ old('certificate_text_before_date', $selectedEvent->certificate_text_before_date) }}" placeholder="e.g., has participated in" style="padding: 10px; border: 2px solid #ddd; border-radius: 4px; width: 100%; max-width: 500px; font-size: 14px;">
                <p style="margin-top: 4px; font-size: 13px; color: #666;">This text will appear before the event date</p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                    Text After Date
                </label>
                <input type="text" name="certificate_text_after_date" value="{{ old('certificate_text_after_date', $selectedEvent->certificate_text_after_date) }}" placeholder="e.g., organized by KARE" style="padding: 10px; border: 2px solid #ddd; border-radius: 4px; width: 100%; max-width: 500px; font-size: 14px;">
                <p style="margin-top: 4px; font-size: 13px; color: #666;">This text will appear after the event date</p>
            </div>
            
            <button type="submit" style="padding: 12px 24px; background: linear-gradient(135deg, #1E90FF 0%, #0A66C2 100%); color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Save Template & Text</button>
        </form>
        @if($templateExists && $selectedEvent->certificate_template_path)
            <p style="margin-top:12px; padding: 8px; background: #e8f5e9; border-radius: 4px; color: #2e7d32;">
                ✓ Template uploaded: {{ basename($selectedEvent->certificate_template_path) }}
            </p>
        @endif
    </div>

    <div class="section-block">
        <h3>Step 3: Generate Certificates</h3>
        @php
            $templateExtension = $selectedEvent->certificate_template_path ? strtolower(pathinfo($selectedEvent->certificate_template_path, PATHINFO_EXTENSION)) : null;
            $isPdfTemplate = $templateExtension === 'pdf';
            $needsImagick = $isPdfTemplate && (!isset($imagickAvailable) || !$imagickAvailable);
        @endphp
        
        @if($needsImagick)
            <div style="margin-bottom: 16px; padding: 12px; background: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107;">
                <p style="margin: 0 0 8px; font-weight: 600; color: #856404;">⚠ PDF Template Detected - Imagick Required</p>
                <p style="margin: 4px 0; font-size: 14px; color: #856404;">
                    Your template is a PDF file, but Imagick extension is not installed. You have two options:
                </p>
                <ul style="margin: 8px 0; padding-left: 20px; color: #856404;">
                    <li><strong>Option 1:</strong> Install Imagick extension (see <code>ENABLE_IMAGICK.md</code> for instructions)</li>
                    <li><strong>Option 2:</strong> Convert your PDF to PNG/JPG and upload the image instead</li>
                </ul>
                <p style="margin: 8px 0 0; font-size: 13px; color: #856404;">
                    <strong>Quick Fix:</strong> Use an online converter (like ilovepdf.com) to convert your PDF to PNG, then upload the PNG file.
                </p>
            </div>
        @endif
        
        @if($templateExists)
            <div style="margin-bottom: 16px; padding: 12px; background: #e3f2fd; border-radius: 6px; border-left: 4px solid #2196F3;">
                <p style="margin: 0 0 8px; font-weight: 600; color: #1976D2;">Certificate Preview:</p>
                <p style="margin: 4px 0; font-size: 14px;">
                    <strong>Name Line:</strong> 
                    <span style="color: #666;">{{ $selectedEvent->certificate_text_prefix ?: 'This is to certify that Mr./Ms.' }} <span style="color: #2196F3;">[Student Name]</span></span>
                </p>
                <p style="margin: 4px 0; font-size: 14px;">
                    <strong>Date Line:</strong> 
                    <span style="color: #666;">{{ $selectedEvent->certificate_text_before_date ?: 'has participated in' }} <span style="color: #2196F3;">[Event Date]</span> {{ $selectedEvent->certificate_text_after_date ?: 'organized by KARE' }}</span>
                </p>
            </div>
        @endif
        <form method="POST" action="{{ route('admin.certificates.generate.event', $selectedEvent->id) }}">
            @csrf
            <p style="margin-bottom: 12px;">Creates a PDF certificate for every attendee with attendance status = <strong>present</strong>.</p>
            <button type="submit" {{ ($templateExists && !$needsImagick) ? '' : 'disabled' }} style="padding: 12px 24px; background: linear-gradient(135deg, #23a96a 0%, #1e7e4a 100%); color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; {{ (!$templateExists || $needsImagick) ? 'opacity: 0.5; cursor: not-allowed;' : '' }}">
                Generate Certificates for {{ $selectedEvent->title }}
            </button>
            @if(!$templateExists)
                <p style="margin-top: 8px; color: #d32f2f; font-size: 13px;">⚠ Please upload a background template first (Step 2)</p>
            @elseif($needsImagick)
                <p style="margin-top: 8px; color: #d32f2f; font-size: 13px;">⚠ Cannot generate: PDF template requires Imagick extension. Please install Imagick or convert PDF to image.</p>
            @endif
        </form>
    </div>

<script>
function validateCertificateForm(form) {
    const fileInput = form.querySelector('input[type="file"]');
    const textPrefix = form.querySelector('input[name="certificate_text_prefix"]').value.trim();
    const textBeforeDate = form.querySelector('input[name="certificate_text_before_date"]').value.trim();
    const textAfterDate = form.querySelector('input[name="certificate_text_after_date"]').value.trim();
    
    // Check if at least one field has a value
    const hasFile = fileInput.files.length > 0;
    const hasText = textPrefix || textBeforeDate || textAfterDate;
    
    if (!hasFile && !hasText) {
        alert('Please upload a template file or enter at least one text field.');
        return false;
    }
    
    return true;
}
</script>

    <div class="section-block">
        <h3>Eligible attendees</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Attendance</th>
                    <th>Certificate</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registrations as $registration)
                    <tr>
                        <td>{{ $registration->student_name }}</td>
                        <td>{{ $registration->student_email }}</td>
                        <td>{{ ucfirst($registration->attendance_status) }}</td>
                        <td>
                            @if($registration->certificate && !$registration->certificate->is_revoked)
                                Issued {{ optional($registration->certificate_issued_at)->format('Y-m-d') }}
                            @else
                                Not issued
                            @endif
                        </td>
                        <td class="actions">
                            @if($registration->certificate && !$registration->certificate->is_revoked)
                                <a href="{{ route('admin.certificates.download', $registration->certificate->id) }}">Download</a>
                                <form method="POST" action="{{ route('admin.certificates.revoke', $registration->certificate->id) }}">
                                    @csrf
                                    <button type="submit">Revoke</button>
                                </form>
                            @else
                                <span style="color:#888;">Generate via Step 3</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No attendees marked present yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($registrations instanceof \Illuminate\Contracts\Pagination\Paginator)
            {{ $registrations->links('pagination::simple-default') }}
        @endif
    </div>
@endif
@endsection

