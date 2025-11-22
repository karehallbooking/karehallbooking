@extends('layouts.admin')

@section('content')
@php($view = request('view'))

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
@elseif($view === 'create')
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
    </style>

    <div class="section-block" style="padding: 20px; border: none; box-shadow: none;">
        <h2 style="margin-bottom: 16px; color: #0a4a8a; font-size: 22px;">Create New Event</h2>
        <form method="POST" action="{{ route('admin.events.store') }}" enctype="multipart/form-data">
            @csrf
            
            <div class="form-section">
                <h3>Event Details</h3>
                <label>Event organized by (club)
                    <input type="text" name="organizer" value="{{ old('organizer') }}" required placeholder="Enter organizer or club name">
                </label>
                <div class="two-col">
                    <label>Event name
                        <input type="text" name="title" value="{{ old('title') }}" required placeholder="Enter event title">
                    </label>
                    <label>Department
                        <input type="text" name="department" value="{{ old('department') }}" required placeholder="Enter department name">
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h3>Schedule</h3>
                <div class="two-col">
                    <label>
                        <span class="icon">📅</span>From date
                        <input type="date" name="start_date" value="{{ old('start_date') }}" required>
                    </label>
                    <label>
                        <span class="icon">📅</span>To date
                        <input type="date" name="end_date" value="{{ old('end_date') }}" required>
                    </label>
                </div>
                <div class="two-col">
                    <label>
                        <span class="icon">🕐</span>From time (24-hour format)
                        <input type="time" name="start_time" value="{{ old('start_time') }}" required>
                    </label>
                    <label>
                        <span class="icon">🕐</span>To time (24-hour format)
                        <input type="time" name="end_time" value="{{ old('end_time') }}" required>
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
                <h3>File Uploads</h3>
                <div class="two-col">
                    <label>
                        <span class="icon">📄</span>Event brochure PDF upload (Max 10MB)
                        <div class="file-upload-wrapper">
                            <input type="file" name="brochure_pdf" accept="application/pdf" id="brochure_pdf" onchange="checkFileSize(this, 10)">
                        </div>
                        <small style="color: #666; font-size: 12px; display: block; margin-top: 4px;">Maximum file size: 10MB</small>
                    </label>
                    <label>
                        <span class="icon">📎</span>Any other PDF upload (Max 10MB)
                        <div class="file-upload-wrapper">
                            <input type="file" name="attachment_pdf" accept="application/pdf" id="attachment_pdf" onchange="checkFileSize(this, 10)">
                        </div>
                        <small style="color: #666; font-size: 12px; display: block; margin-top: 4px;">Maximum file size: 10MB</small>
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
                        <td>{{ $event->registrations_count }}</td>
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
@endif
@endsection