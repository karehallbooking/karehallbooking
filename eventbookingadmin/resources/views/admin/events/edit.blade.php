@extends('layouts.admin')

@section('content')
@php
    $timeOptions = collect(range(0, 23))->map(function ($hour) {
        return str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
    });
    $todayDate = now()->format('Y-m-d');
    $selectedStartTime = old('start_time', $event->start_time ? \Carbon\Carbon::parse($event->start_time)->format('H:i') : null);
    $selectedEndTime = old('end_time', $event->end_time ? \Carbon\Carbon::parse($event->end_time)->format('H:i') : null);
@endphp
<a class="back-link" href="{{ route('admin.events.index') }}">Back to Events</a>
<div class="card-grid">
    <div class="card">
        <h3>Edit Event</h3>
        <p>Update schedule or venue.</p>
    </div>
    <div class="card">
        <h3>Delete Event</h3>
        <p>Remove this event if needed.</p>
        <form method="POST" action="{{ route('admin.events.destroy', $event->id) }}">
            @csrf
            @method('DELETE')
            <button type="submit" onclick="return confirm('Delete event?')">Delete</button>
        </form>
    </div>
    <div class="card">
        <h3>Back to List</h3>
        <p>Return to overview.</p>
        <a href="{{ route('admin.events.index') }}">Back</a>
    </div>
</div>

<style>
    .time-select-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 16px;
    }
    .time-select {
        border: 2px solid #0c5fd1;
        border-radius: 8px;
        padding: 6px;
        font-size: 14px;
        height: 165px;
        overflow-y: auto;
        font-weight: 600;
        background: #fff;
    }
    .time-select option {
        padding: 6px 4px;
    }
    .time-select:focus {
        outline: none;
        border-color: #0a4a8a;
        box-shadow: 0 0 0 3px rgba(10, 74, 138, 0.12);
    }
    .time-select-hint {
        font-size: 12px;
        color: #4a5d78;
        margin-top: 4px;
    }
</style>

<div class="section-block">
    <h2>Edit Event</h2>
    <form method="POST" action="{{ route('admin.events.update', $event->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-row">
            <label>Event Club
                <select name="event_club" id="event_club_edit" required onchange="toggleOtherClubInputEdit()">
                    <option value="">-- Select Club --</option>
                    <option value="Technical Club" @selected(old('event_club', $event->event_club) === 'Technical Club')>Technical Club</option>
                    <option value="Cultural Club" @selected(old('event_club', $event->event_club) === 'Cultural Club')>Cultural Club</option>
                    <option value="Sports Club" @selected(old('event_club', $event->event_club) === 'Sports Club')>Sports Club</option>
                    <option value="Literary Club" @selected(old('event_club', $event->event_club) === 'Literary Club')>Literary Club</option>
                    <option value="Music Club" @selected(old('event_club', $event->event_club) === 'Music Club')>Music Club</option>
                    <option value="Dance Club" @selected(old('event_club', $event->event_club) === 'Dance Club')>Dance Club</option>
                    <option value="Photography Club" @selected(old('event_club', $event->event_club) === 'Photography Club')>Photography Club</option>
                    <option value="NSS Club" @selected(old('event_club', $event->event_club) === 'NSS Club')>NSS Club</option>
                    <option value="NCC Club" @selected(old('event_club', $event->event_club) === 'NCC Club')>NCC Club</option>
                    <option value="Robotics Club" @selected(old('event_club', $event->event_club) === 'Robotics Club')>Robotics Club</option>
                    <option value="Eco Club" @selected(old('event_club', $event->event_club) === 'Eco Club')>Eco Club</option>
                    <option value="Entrepreneurship Club" @selected(old('event_club', $event->event_club) === 'Entrepreneurship Club')>Entrepreneurship Club</option>
                    <option value="Other" @selected(old('event_club', $event->event_club) === 'Other')>Other</option>
                </select>
            </label>
        </div>
        <div class="form-row" id="other_club_wrapper_edit" style="display: {{ old('event_club', $event->event_club) === 'Other' ? 'block' : 'none' }};">
            <label>Other club name
                <input type="text" name="event_club_other" id="event_club_other_edit" value="{{ old('event_club_other', $event->event_club_other) }}" placeholder="Enter other club name">
            </label>
        </div>
        <div class="form-row">
            <label>Event name
                <input type="text" name="title" value="{{ old('title', $event->title) }}" required>
            </label>
        </div>
        <div class="form-row two-col">
            <label>From date
                <input
                    type="text"
                    name="start_date"
                    id="event-edit-start-date"
                    value="{{ old('start_date', optional($event->start_date)->format('Y-m-d')) }}"
                    class="date-picker date-upcoming-only"
                    placeholder="dd-mm-yyyy"
                    data-linked-end="#event-edit-end-date"
                    autocomplete="off"
                    required
                >
            </label>
            <label>To date
                <input
                    type="text"
                    name="end_date"
                    id="event-edit-end-date"
                    value="{{ old('end_date', optional($event->end_date)->format('Y-m-d')) }}"
                    class="date-picker date-upcoming-only date-upcoming-end"
                    placeholder="dd-mm-yyyy"
                    data-linked-start="#event-edit-start-date"
                    autocomplete="off"
                    required
                >
            </label>
        </div>
        <div class="form-row two-col">
            <label class="time-select-group">From time (24-hour format)
                <select name="start_time" class="time-select" size="5" required>
                    <option value="" disabled {{ $selectedStartTime ? '' : 'selected' }}>Select start time</option>
                    @foreach($timeOptions as $time)
                        <option value="{{ $time }}" @selected($selectedStartTime === $time)>{{ $time }}</option>
                    @endforeach
                </select>
                <span class="time-select-hint">Scroll to see all hours</span>
            </label>
            <label class="time-select-group">To time (24-hour format)
                <select name="end_time" class="time-select" size="5" required>
                    <option value="" disabled {{ $selectedEndTime ? '' : 'selected' }}>Select end time</option>
                    @foreach($timeOptions as $time)
                        <option value="{{ $time }}" @selected($selectedEndTime === $time)>{{ $time }}</option>
                    @endforeach
                </select>
                <span class="time-select-hint">Five slots visible, scroll for more</span>
            </label>
        </div>
        <div class="form-row two-col">
            <label>How many seats available
                <input type="number" name="capacity" min="1" value="{{ old('capacity', $event->capacity) }}" required>
            </label>
            @php($pricing = old('pricing_type', $event->is_paid ? 'paid' : 'free'))
            <label>Paid or Free
                <select name="pricing_type" id="pricingTypeEdit" required>
                    <option value="free" @selected($pricing === 'free')>Free</option>
                    <option value="paid" @selected($pricing === 'paid')>Paid</option>
                </select>
            </label>
        </div>
        <div class="form-row" id="amountRowEdit" style="{{ $pricing === 'paid' ? '' : 'display:none;' }}">
            <label>Enter amount
                <input type="number" name="amount" min="0" step="0.01" value="{{ old('amount', $event->amount) }}">
            </label>
        </div>
        <label>Event description (optional)
            <textarea name="description">{{ old('description', $event->description) }}</textarea>
        </label>
        <label>Venue
            <input type="text" name="venue" value="{{ old('venue', $event->venue) }}" required>
        </label>
        <div class="form-row two-col">
            <label>Faculty Coordinator Name
                <input type="text" name="faculty_coordinator_name" value="{{ old('faculty_coordinator_name', $event->faculty_coordinator_name) }}" required placeholder="Enter faculty coordinator name">
            </label>
            <label>Faculty Coordinator Contact
                <input type="text" name="faculty_coordinator_contact" value="{{ old('faculty_coordinator_contact', $event->faculty_coordinator_contact) }}" required placeholder="Enter contact number">
            </label>
        </div>
        <div class="form-row two-col">
            <label>Student Coordinator Name
                <input type="text" name="student_coordinator_name" value="{{ old('student_coordinator_name', $event->student_coordinator_name) }}" required placeholder="Enter student coordinator name">
            </label>
            <label>Student Coordinator Contact
                <input type="text" name="student_coordinator_contact" value="{{ old('student_coordinator_contact', $event->student_coordinator_contact) }}" required placeholder="Enter contact number">
            </label>
        </div>
        <label>Event Approval Letter (PDF, Max 10MB)
            <input type="file" name="brochure_pdf" accept="application/pdf">
            <small style="color: #666; font-size: 12px; display: block; margin-top: 4px;">Upload the event approval letter PDF (Maximum file size: 10MB).</small>
        </label>
        @if($event->brochure_path)
            <p>Current approval letter stored at: {{ $event->brochure_path }}</p>
        @endif
        <label>Event Brochure (PDF, Max 10MB)
            <input type="file" name="attachment_pdf" accept="application/pdf">
            <small style="color: #666; font-size: 12px; display: block; margin-top: 4px;">Upload the event brochure PDF (Maximum file size: 10MB).</small>
        </label>
        @if($event->attachment_path)
            <p>Current brochure stored at: {{ $event->attachment_path }}</p>
        @endif
        <select name="status" required>
            @foreach(['upcoming','ongoing','completed','cancelled'] as $status)
                <option value="{{ $status }}" @selected($event->status === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit">Save Changes</button>
    </form>
</div>
<script>
    (function() {
        var typeSelect = document.getElementById('pricingTypeEdit');
        var amountRow = document.getElementById('amountRowEdit');
        if (typeSelect) {
            typeSelect.addEventListener('change', function () {
                amountRow.style.display = this.value === 'paid' ? '' : 'none';
            });
        }
    })();
    
    function toggleOtherClubInputEdit() {
        var clubSelect = document.getElementById('event_club_edit');
        var otherWrapper = document.getElementById('other_club_wrapper_edit');
        var otherInput = document.getElementById('event_club_other_edit');
        
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
                }
            }
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleOtherClubInputEdit();
    });
</script>
@endsection


