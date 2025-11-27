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
                        <option value="Technical Club" @selected(old('event_club') === 'Technical Club')>Technical Club</option>
                        <option value="Cultural Club" @selected(old('event_club') === 'Cultural Club')>Cultural Club</option>
                        <option value="Sports Club" @selected(old('event_club') === 'Sports Club')>Sports Club</option>
                        <option value="Literary Club" @selected(old('event_club') === 'Literary Club')>Literary Club</option>
                        <option value="Music Club" @selected(old('event_club') === 'Music Club')>Music Club</option>
                        <option value="Dance Club" @selected(old('event_club') === 'Dance Club')>Dance Club</option>
                        <option value="Photography Club" @selected(old('event_club') === 'Photography Club')>Photography Club</option>
                        <option value="NSS Club" @selected(old('event_club') === 'NSS Club')>NSS Club</option>
                        <option value="NCC Club" @selected(old('event_club') === 'NCC Club')>NCC Club</option>
                        <option value="Robotics Club" @selected(old('event_club') === 'Robotics Club')>Robotics Club</option>
                        <option value="Eco Club" @selected(old('event_club') === 'Eco Club')>Eco Club</option>
                        <option value="Entrepreneurship Club" @selected(old('event_club') === 'Entrepreneurship Club')>Entrepreneurship Club</option>
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
                        <span class="time-select-hint">Scroll to see all 24 hours</span>
                    </label>
                    <label class="time-select-group">
                        <span class="icon">🕐</span>To time (24-hour format)
                        <select name="end_time" class="time-select" required>
                            <option value="" disabled {{ old('end_time') ? '' : 'selected' }}>Select end time</option>
                            @foreach($timeOptions as $time)
                                <option value="{{ $time }}" @selected(old('end_time') === $time)>{{ $time }}</option>
                            @endforeach
                        </select>
                        <span class="time-select-hint">View five slots at a time and scroll</span>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h3>Capacity & Pricing</h3>
                <div class="two-col">
                    <label>How many seats available
                        <input type="number" name="capacity" min="1" value="{{ old('capacity') }}" required placeholder="Enter number of seats">
                    </label>
                    <label>Paid or Free
                        <select name="pricing_type" id="pricingType" required>
                            <option value="free" @selected(old('pricing_type', 'free') === 'free')>Free</option>
                            <option value="paid" @selected(old('pricing_type') === 'paid')>Paid</option>
                        </select>
                    </label>
                </div>
                <div class="form-row" id="amountRow" style="{{ old('pricing_type', 'free') === 'paid' ? '' : 'display:none;' }}">
                    <label>Enter amount
                        <input type="number" name="amount" step="0.01" min="0" value="{{ old('amount') }}" placeholder="0.00">
                    </label>
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
                            <a href="{{ route('admin.events.edit', $event->id) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.events.destroy', $event->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this event?')">Delete</button>
                            </form>
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