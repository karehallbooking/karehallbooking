@extends('layouts.admin')

@section('content')
@php
    $view = request('view');
    $timeOptions = collect(range(0, 23))->map(function ($hour) {
        return str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
    });
    $todayDate = now()->format('Y-m-d');
@endphp

@if($view === 'create')
    <a class="back-link" href="{{ route('admin.events.index') }}">Back to Events</a>
    
    <style>
        .form-section {
            background: linear-gradient(135deg, #f8fbff 0%, #e8f2ff 100%);
            border: 1px solid #c5d9f0;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 6px rgba(11, 92, 200, 0.08);
        }
        .form-section h3 {
            margin: 0 0 12px 0;
            font-size: 16px;
            font-weight: 600;
            color: #0a4a8a;
            border-bottom: 2px solid #c5d9f0;
            padding-bottom: 6px;
        }
        .form-section label {
            display: flex;
            flex-direction: column;
            font-size: 13px;
            font-weight: 600;
            color: #1a3a5c;
            margin-bottom: 0;
            margin-top: 12px;
        }
        .form-section label:first-of-type {
            margin-top: 0;
        }
        .form-section .two-col label {
            margin-top: 12px;
        }
        .form-section input[type="text"],
        .form-section input[type="date"],
        .form-section input[type="time"],
        .form-section input[type="number"],
        .form-section select,
        .form-section textarea {
            width: 100%;
            padding: 9px 12px;
            border: 2px solid #b8d4f0;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
            color: #1a3a5c;
            transition: all 0.2s ease;
            box-sizing: border-box;
            margin-top: 6px;
            height: 40px;
        }
        .form-section textarea {
            height: auto;
            min-height: 70px;
        }
        .form-section input:focus,
        .form-section select:focus,
        .form-section textarea:focus {
            outline: none;
            border-color: #1E90FF;
            box-shadow: 0 0 0 3px rgba(30, 144, 255, 0.1);
        }
        .form-section textarea {
            min-height: 70px;
            resize: vertical;
            font-family: inherit;
        }
        .form-section .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }
        .form-section .two-col label {
            display: flex;
            flex-direction: column;
        }
        .form-section .file-upload-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .form-section input[type="file"] {
            padding: 8px;
            border: 2px dashed #b8d4f0;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 13px;
        }
        .form-section input[type="file"]:hover {
            border-color: #1E90FF;
            background: #f8fbff;
        }
        .form-section .icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-right: 6px;
            vertical-align: middle;
            opacity: 0.7;
        }
        .submit-btn {
            background: linear-gradient(135deg, #1E90FF 0%, #0A66C2 100%);
            color: #fff;
            border: none;
            padding: 12px 28px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(30, 144, 255, 0.3);
            transition: all 0.2s ease;
            margin-top: 4px;
        }
        .submit-btn:hover {
            background: linear-gradient(135deg, #0A66C2 0%, #0056b3 100%);
            box-shadow: 0 6px 16px rgba(30, 144, 255, 0.4);
            transform: translateY(-1px);
        }
        .submit-btn:active {
            transform: translateY(0);
        }
        .time-select-group {
            display: flex;
            flex-direction: column;
        }
        .time-select {
            width: 100%;
            border: 2px solid #76a8ff;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 15px;
            font-weight: 600;
            color: #0d2d5c;
            background: linear-gradient(180deg, #fbfdff 0%, #eaf2ff 100%);
            box-shadow: inset 0 1px 3px rgba(12, 69, 160, 0.15);
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill='%232a5cd4' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 12px 8px;
            height: 48px;
            overflow: hidden;
        }
        .time-select-expanded {
            height: auto;
            min-height: 210px;
            overflow-y: auto;
            background-image: none;
        }
        .time-select:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
        }
        .time-select option {
            font-weight: 500;
        }
        .time-select-hint {
            font-size: 12px;
            color: #4a5d78;
            margin-top: 4px;
        }
    </style>

    <div class="section-block" style="padding: 20px; border: none; box-shadow: none;">
        <h2 style="margin-bottom: 16px; color: #0a4a8a; font-size: 22px;">Create New Event</h2>
        <form method="POST" action="{{ route('admin.events.store') }}" enctype="multipart/form-data">
            @csrf
            
            <div class="form-section">
                <h3>Event Details</h3>
                <label>Event Club
                    <select name="event_club" id="event_club" required onchange="toggleOtherClubInput()">
                        <option value="">-- Select Club --</option>
                        <option value="Fine Arts" @selected(old('event_club') === 'Fine Arts')>Fine Arts</option>
                        <option value="Green Army" @selected(old('event_club') === 'Green Army')>Green Army</option>
                        <option value="Nature Club" @selected(old('event_club') === 'Nature Club')>Nature Club</option>
                        <option value="NCC" @selected(old('event_club') === 'NCC')>NCC</option>
                        <option value="NSS" @selected(old('event_club') === 'NSS')>NSS</option>
                        <option value="Photography Club" @selected(old('event_club') === 'Photography Club')>Photography Club</option>
                        <option value="Sherlock Holmes Club" @selected(old('event_club') === 'Sherlock Holmes Club')>Sherlock Holmes Club</option>
                        <option value="Sports" @selected(old('event_club') === 'Sports')>Sports</option>
                        <option value="Tamil Mandram" @selected(old('event_club') === 'Tamil Mandram')>Tamil Mandram</option>
                        <option value="Vishaka Club" @selected(old('event_club') === 'Vishaka Club')>Vishaka Club</option>
                        <option value="YRC" @selected(old('event_club') === 'YRC')>YRC</option>
                        <option value="YUVA Tourism" @selected(old('event_club') === 'YUVA Tourism')>YUVA Tourism</option>
                        <option value="Other" @selected(old('event_club') === 'Other')>Other</option>
                    </select>
                </label>
                <div id="other_club_wrapper" style="display: {{ old('event_club') === 'Other' ? 'block' : 'none' }};">
                    <label>Other club name
                        <input type="text" name="event_club_other" id="event_club_other" value="{{ old('event_club_other') }}" placeholder="Enter other club name">
                    </label>
                </div>
                <label>Event name
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="Enter event title">
                </label>
            </div>

            <div class="form-section">
                <h3>Schedule</h3>
                <div class="two-col">
                    <label>
                        <span class="icon">📅</span>From date
                        <input
                            type="text"
                            name="start_date"
                            id="event-start-date"
                            value="{{ old('start_date', $todayDate) }}"
                            class="date-picker date-upcoming-only"
                            placeholder="dd-mm-yyyy"
                            data-linked-end="#event-end-date"
                            autocomplete="off"
                            required
                        >
                    </label>
                    <label>
                        <span class="icon">📅</span>To date
                        <input
                            type="text"
                            name="end_date"
                            id="event-end-date"
                            value="{{ old('end_date', $todayDate) }}"
                            class="date-picker date-upcoming-only date-upcoming-end"
                            placeholder="dd-mm-yyyy"
                            data-linked-start="#event-start-date"
                            autocomplete="off"
                            required
                        >
                    </label>
                </div>
                <div class="two-col">
                    <label class="time-select-group">
                        <span class="icon">🕐</span>From time (24-hour format)
                        <select name="start_time" class="time-select" required>
                            <option value="" disabled {{ old('start_time') ? '' : 'selected' }}>Select start time</option>
                            @foreach($timeOptions as $time)
                                <option value="{{ $time }}" @selected(old('start_time') === $time)>{{ $time }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="time-select-group">
                        <span class="icon">🕐</span>To time (24-hour format)
                        <select name="end_time" class="time-select" required>
                            <option value="" disabled {{ old('end_time') ? '' : 'selected' }}>Select end time</option>
                            @foreach($timeOptions as $time)
                                <option value="{{ $time }}" @selected(old('end_time') === $time)>{{ $time }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h3>Capacity & Pricing</h3>
                <div class="two-col">
                    <label>How many seats available
                        <input type="number" name="capacity" min="1" value="{{ old('capacity') }}" required placeholder="Enter number of seats">
                    </label>
                </div>
                <div class="two-col">
                    <label>Paid or Free
                        <select name="pricing_type" id="pricingType" required>
                            <option value="free" @selected(old('pricing_type', 'free') === 'free')>Free</option>
                            <option value="paid" @selected(old('pricing_type') === 'paid')>Paid</option>
                        </select>
                    </label>
                    <div class="form-row" id="amountRow" style="{{ old('pricing_type', 'free') === 'paid' ? '' : 'display:none;' }}">
                        <label>Enter amount
                            <input type="number" name="amount" step="0.01" min="0" value="{{ old('amount') }}" placeholder="0.00">
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Attendance Sessions (Date-wise)</h3>
                <p style="font-size: 13px; color:#4a5d78; margin-top:0;">
                    After selecting <strong>From date</strong> and <strong>To date</strong>, date-wise session fields will appear here.
                    Please enter how many times attendance will be taken on each date.
                </p>
                <div id="attendance-sessions-container">
                    @if(old('attendance_sessions'))
                        @foreach(old('attendance_sessions') as $date => $count)
                            <div class="two-col" data-attendance-row="{{ $date }}" style="margin-bottom:8px;">
                                <label>Date
                                    <input type="text" value="{{ $date }}" readonly style="background:#f5f8ff;">
                                </label>
                                <label>Sessions on {{ $date }}
                                    <input type="number" name="attendance_sessions[{{ $date }}]" min="1" max="10" value="{{ $count }}" required>
                                </label>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="form-section">
                <h3>Description</h3>
                <label>Event description (optional)
                    <textarea name="description" placeholder="Enter event description...">{{ old('description') }}</textarea>
                </label>
            </div>

            <div class="form-section">
                <h3>Venue</h3>
                <label>Venue
                    <input type="text" name="venue" value="{{ old('venue') }}" required placeholder="Enter venue location">
                </label>
            </div>

            <div class="form-section">
                <h3>Coordinator Details</h3>
                <div class="two-col">
                    <label>Faculty Coordinator Name
                        <input type="text" name="faculty_coordinator_name" value="{{ old('faculty_coordinator_name') }}" required placeholder="Enter faculty coordinator name">
                    </label>
                    <label>Faculty Coordinator Contact
                        <input type="text" name="faculty_coordinator_contact" value="{{ old('faculty_coordinator_contact') }}" required placeholder="Enter contact number">
                    </label>
                </div>
                <div class="two-col">
                    <label>Student Coordinator Name
                        <input type="text" name="student_coordinator_name" value="{{ old('student_coordinator_name') }}" required placeholder="Enter student coordinator name">
                    </label>
                    <label>Student Coordinator Contact
                        <input type="text" name="student_coordinator_contact" value="{{ old('student_coordinator_contact') }}" required placeholder="Enter contact number">
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h3>File Uploads</h3>
                <div class="two-col">
                    <label>
                        <span class="icon">📄</span>Event Approval Letter (PDF, Max 10MB)
                        <div class="file-upload-wrapper">
                            <input type="file" name="brochure_pdf" accept="application/pdf" id="brochure_pdf" onchange="checkFileSize(this, 10)">
                        </div>
                        <small style="color: #666; font-size: 12px; display: block; margin-top: 4px;">Upload the event approval letter PDF (Maximum file size: 10MB).</small>
                    </label>
                    <label>
                        <span class="icon">📎</span>Event Brochure (PDF, Max 10MB)
                        <div class="file-upload-wrapper">
                            <input type="file" name="attachment_pdf" accept="application/pdf" id="attachment_pdf" onchange="checkFileSize(this, 10)">
                        </div>
                        <small style="color: #666; font-size: 12px; display: block; margin-top: 4px;">Upload the event brochure PDF (Maximum file size: 10MB).</small>
                    </label>
                </div>
            </div>

            <button type="submit" class="submit-btn">Submit Event</button>
        </form>
    </div>
    <script>
        (function() {
            var typeSelect = document.getElementById('pricingType');
            var amountRow = document.getElementById('amountRow');
            if (typeSelect) {
                typeSelect.addEventListener('change', function () {
                    amountRow.style.display = this.value === 'paid' ? '' : 'none';
                });
            }
        })();
        
        function toggleOtherClubInput() {
            var clubSelect = document.getElementById('event_club');
            var otherWrapper = document.getElementById('other_club_wrapper');
            var otherInput = document.getElementById('event_club_other');
            
            if (clubSelect && otherWrapper) {
                if (clubSelect.value === 'Other') {
                    otherWrapper.style.display = 'block';
                    if (otherInput) {
                        otherInput.setAttribute('required', 'required');
                    }
                } else {
                    otherWrapper.style.display = 'none';
                    if (otherInput) {
                        otherInput.removeAttribute('required');
                        otherInput.value = '';
                    }
                }
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleOtherClubInput();

            const timeSelects = document.querySelectorAll('.time-select');
            timeSelects.forEach(function(select) {
                const expand = function() {
                    select.classList.add('time-select-expanded');
                    select.setAttribute('size', 7);
                    select.size = 7;
                    select.style.height = 'auto';
                };
                const collapse = function() {
                    select.classList.remove('time-select-expanded');
                    select.removeAttribute('size');
                    select.size = 1;
                    select.style.height = '48px';
                };

                select.addEventListener('focus', expand);
                select.addEventListener('click', expand);
                select.addEventListener('blur', collapse);
                select.addEventListener('change', function() {
                    collapse();
                    setTimeout(function() {
                        select.blur();
                    }, 0);
                });
                select.addEventListener('mouseleave', function() {
                    if (!select.matches(':focus')) {
                        collapse();
                    }
                });
            });
        });
        
        function checkFileSize(input, maxSizeMB) {
            if (input.files && input.files[0]) {
                var fileSize = input.files[0].size / 1024 / 1024; // Size in MB
                if (fileSize > maxSizeMB) {
                    alert('File size (' + fileSize.toFixed(2) + 'MB) exceeds the maximum allowed size of ' + maxSizeMB + 'MB. Please choose a smaller file.');
                    input.value = '';
                    return false;
                }
            }
            return true;
        }

        // -------- Date-wise attendance sessions ----------
        function generateAttendanceSessions() {
            const startInput = document.getElementById('event-start-date');
            const endInput = document.getElementById('event-end-date');
            const container = document.getElementById('attendance-sessions-container');
            if (!startInput || !endInput || !container || !startInput.value || !endInput.value) {
                return;
            }

            const startDate = new Date(startInput.value);
            const endDate = new Date(endInput.value);
            if (isNaN(startDate.getTime()) || isNaN(endDate.getTime()) || endDate < startDate) {
                return;
            }

            // Preserve existing values if possible
            const existing = {};
            container.querySelectorAll('div[data-attendance-row]').forEach(function(row) {
                const dateKey = row.getAttribute('data-attendance-row');
                const input = row.querySelector('input[type=\"number\"]');
                if (dateKey && input) {
                    existing[dateKey] = input.value;
                }
            });

            container.innerHTML = '';

            let current = new Date(startDate);
            while (current <= endDate) {
                const iso = current.toISOString().split('T')[0];
                const display = iso;
                const value = existing[iso] || 1;

                const row = document.createElement('div');
                row.className = 'two-col';
                row.setAttribute('data-attendance-row', iso);
                row.style.marginBottom = '8px';
                row.innerHTML =
                    '<label>Date' +
                        '<input type=\"text\" value=\"' + display + '\" readonly style=\"background:#f5f8ff;\">' +
                    '</label>' +
                    '<label>Sessions on ' + display +
                        '<input type=\"number\" name=\"attendance_sessions[' + iso + ']\" min=\"1\" max=\"10\" value=\"' + value + '\" required>' +
                    '</label>';
                container.appendChild(row);

                current.setDate(current.getDate() + 1);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const start = document.getElementById('event-start-date');
            const end = document.getElementById('event-end-date');
            if (start) {
                start.addEventListener('change', generateAttendanceSessions);
            }
            if (end) {
                end.addEventListener('change', generateAttendanceSessions);
            }

            // Initial generation if values already present (e.g., edit/validation error)
            generateAttendanceSessions();
        });
    </script>
@elseif($view === 'list')
    <a class="back-link" href="{{ route('admin.events.index') }}">Back to Events</a>
    <div class="section-block">
        <h2>Available Events</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Date Range</th>
                    <th>Venue</th>
                    <th>Capacity</th>
                    <th>Registrations</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                    <tr>
                        <td>{{ $event->id }}</td>
                        <td>{{ $event->title }}</td>
                        <td>
                            {{ optional($event->start_date)->format('Y-m-d') }}
                            -
                            {{ optional($event->end_date)->format('Y-m-d') }}
                        </td>
                        <td>{{ $event->venue }}</td>
                        <td>{{ $event->capacity }}</td>
                        <td>{{ $event->registrations_count ?? $event->registrations()->where('payment_status', 'paid')->count() }}</td>
                        <td>{{ ucfirst($event->status) }}</td>
                        <td class="actions">
                            <a href="{{ route('admin.events.edit', $event->id) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form method="POST"
                                  action="{{ route('admin.events.destroy', $event->id) }}"
                                  class="delete-event-form"
                                  data-event-title="{{ $event->title }}"
                                  style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                            @if($event->status !== 'completed')
                                <button type="button"
                                        class="btn btn-primary btn-sm"
                                        onclick="openCompleteEventModal({{ $event->id }}, '{{ addslashes($event->title) }}')">
                                    Mark Event Complete
                                </button>
                            @endif
                            @php
                                $isPast = $event->end_date && $event->end_date->lt(\Carbon\Carbon::today());
                            @endphp
                            @if($isPast && $event->status !== 'completed')
                                <div style="margin-top:6px; font-size:11px; color:#b45309; font-weight:600;">
                                    This event has ended. Please upload required files and mark as complete.
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No events found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $events->links('pagination::simple-default') }}
    </div>
    
    {{-- Delete Event Modal --}}
    <div id="delete-event-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#ffffff; border-radius:14px; max-width:420px; width:92%; padding:24px 22px 18px; box-shadow:0 18px 45px rgba(15, 82, 186, 0.4); position:relative;">
            <h3 style="margin:0 0 10px; font-size:20px; color:#0a4a8a;">Delete Event</h3>
            <p id="delete-event-modal-body" style="margin:0 0 18px; font-size:14px; color:#1a2740; font-weight:600;">
                Are you sure you want to delete this event?
            </p>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:4px;">
                <button type="button"
                        id="delete-event-cancel"
                        style="padding:8px 18px; border-radius:999px; border:1px solid #c5d9f0; background:#f5f8ff; color:#0a4a8a; font-weight:600; cursor:pointer;">
                    Cancel
                </button>
                <button type="button"
                        id="delete-event-confirm"
                        style="padding:9px 22px; border-radius:999px; border:none; background:#0b63ce; color:#ffffff; font-weight:700; cursor:pointer; box-shadow:0 4px 10px rgba(11, 99, 206, 0.35);">
                    Delete
                </button>
            </div>
        </div>
    </div>
    
    {{-- Complete Event Modal --}}
    <div id="complete-event-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; max-width:720px; width:95%; padding:24px; box-shadow:0 12px 40px rgba(15, 82, 186, 0.35); position:relative;">
            <h3 style="margin-top:0; margin-bottom:8px; font-size:20px; color:#0a4a8a;">Mark Event Complete</h3>
            <p id="complete-event-modal-subtitle" style="margin-top:0; margin-bottom:16px; font-size:13px; color:#445668;">
                Upload all mandatory post-event documents to close this event.
            </p>
            <form id="complete-event-form" method="POST" action="" enctype="multipart/form-data">
                @csrf
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <label style="font-size:13px; font-weight:600; color:#1a3a5c;">
                        Event Form (required)
                        <input type="file" name="completion_event_form" required style="margin-top:6px;">
                    </label>
                    <label style="font-size:13px; font-weight:600; color:#1a3a5c;">
                        Circular (required)
                        <input type="file" name="completion_circular" required style="margin-top:6px;">
                    </label>
                    <label style="font-size:13px; font-weight:600; color:#1a3a5c;">
                        Event Brochure (required)
                        <input type="file" name="completion_brochure" required style="margin-top:6px;">
                    </label>
                    <label style="font-size:13px; font-weight:600; color:#1a3a5c;">
                        Report (required)
                        <input type="file" name="completion_report" required style="margin-top:6px;">
                    </label>
                    <label style="font-size:13px; font-weight:600; color:#1a3a5c;">
                        Attendance (required)
                        <input type="file" name="completion_attendance" required style="margin-top:6px;">
                    </label>
                    <label style="font-size:13px; font-weight:600; color:#1a3a5c;">
                        Feedback (required)
                        <input type="file" name="completion_feedback" required style="margin-top:6px;">
                    </label>
                    <label style="font-size:13px; font-weight:600; color:#1a3a5c;">
                        Sample Certificate (required)
                        <input type="file" name="completion_sample_certificate" required style="margin-top:6px;">
                    </label>
                    <label style="font-size:13px; font-weight:600; color:#1a3a5c;">
                        Event Images / Combined File (required)
                        <input type="file" name="completion_images[]" multiple required style="margin-top:6px;">
                        <small style="font-size:11px; color:#5b6a7d; display:block; margin-top:4px;">
                            You can upload individual images (jpg, jpeg, png) or a single combined file (pdf, doc, docx) that contains all images.
                        </small>
                    </label>
                </div>
                <div style="margin-top:18px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="closeCompleteEventModal()" style="padding:8px 16px; border-radius:6px; border:1px solid #c5d9f0; background:#f5f8ff; cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="padding:9px 20px; border-radius:6px; border:none; background:#0b63ce; color:#fff; font-weight:600; cursor:pointer;">
                        Upload & Close Event
                    </button>
                </div>
            </form>
        </div>
    </div>
@else
    @if(!$view)
        <div style="margin-bottom: 16px;">
            <a class="back-to-dashboard" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
        </div>
        <div class="card-grid">
            <div class="card">
                <h3>Create New Event</h3>
                <p>Launch a fresh event with full details.</p>
                <a href="{{ route('admin.events.index', ['view' => 'create']) }}">Open Create Form</a>
            </div>
            <div class="card">
                <h3>Available Events</h3>
                <p>Browse, edit, or delete existing events.</p>
                <a href="{{ route('admin.events.index', ['view' => 'list']) }}">View Events Table</a>
            </div>
        </div>
    @else
        <p>Invalid view selection.</p>
    @endif
@endif
@endsection

@push('scripts')
<script>
    // ---------- Complete Event Modal ----------
    function openCompleteEventModal(eventId, title) {
        var modal = document.getElementById('complete-event-modal');
        var form = document.getElementById('complete-event-form');
        var subtitle = document.getElementById('complete-event-modal-subtitle');
        if (modal && form) {
            form.action = "{{ url('events') }}/" + eventId + "/complete";
            if (subtitle) {
                subtitle.textContent = 'Upload all mandatory documents to close "' + title + '" as completed.';
            }
            modal.style.display = 'flex';
        }
    }

    function closeCompleteEventModal() {
        var modal = document.getElementById('complete-event-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // ---------- Delete Event Modal ----------
    (function () {
        var activeDeleteForm = null;
        var modal = document.getElementById('delete-event-modal');
        var bodyEl = document.getElementById('delete-event-modal-body');
        var btnCancel = document.getElementById('delete-event-cancel');
        var btnConfirm = document.getElementById('delete-event-confirm');

        function openDeleteModal(form) {
            activeDeleteForm = form;
            if (bodyEl && form && form.dataset.eventTitle) {
                bodyEl.textContent =
                    'Are you sure you want to delete the event \"' + form.dataset.eventTitle + '\"?';
            }
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        function closeDeleteModal() {
            activeDeleteForm = null;
            if (modal) {
                modal.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            var deleteForms = document.querySelectorAll('.delete-event-form');
            deleteForms.forEach(function (form) {
                var btn = form.querySelector('button[type=\"button\"]');
                if (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        openDeleteModal(form);
                    });
                }
            });

            if (btnCancel) {
                btnCancel.addEventListener('click', function () {
                    closeDeleteModal();
                });
            }

            if (btnConfirm) {
                btnConfirm.addEventListener('click', function () {
                    if (activeDeleteForm) {
                        activeDeleteForm.submit();
                    }
                    closeDeleteModal();
                });
            }

            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) {
                        closeDeleteModal();
                    }
                });
            }
        });
    })();
</script>
@endpush