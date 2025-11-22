@extends('layouts.admin')

@section('content')
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

<div class="section-block">
    <h2>Edit Event</h2>
    <form method="POST" action="{{ route('admin.events.update', $event->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-row">
            <label>Event organized by (club)
                <input type="text" name="organizer" value="{{ old('organizer', $event->organizer) }}" required>
            </label>
        </div>
        <div class="form-row two-col">
            <label>Event name
                <input type="text" name="title" value="{{ old('title', $event->title) }}" required>
            </label>
            <label>Department
                <input type="text" name="department" value="{{ old('department', $event->department) }}" required>
            </label>
        </div>
        <div class="form-row two-col">
            <label>From date
                <input type="date" name="start_date" value="{{ old('start_date', optional($event->start_date)->format('Y-m-d')) }}" required>
            </label>
            <label>To date
                <input type="date" name="end_date" value="{{ old('end_date', optional($event->end_date)->format('Y-m-d')) }}" required>
            </label>
        </div>
        <div class="form-row two-col">
            <label>From time (24-hour format)
                <input type="time" name="start_time" value="{{ old('start_time', $event->start_time) }}" required>
            </label>
            <label>To time (24-hour format)
                <input type="time" name="end_time" value="{{ old('end_time', $event->end_time) }}" required>
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
        <label>Event brochure PDF upload
            <input type="file" name="brochure_pdf" accept="application/pdf">
        </label>
        @if($event->brochure_path)
            <p>Current brochure stored at: {{ $event->brochure_path }}</p>
        @endif
        <label>Any other PDF upload
            <input type="file" name="attachment_pdf" accept="application/pdf">
        </label>
        @if($event->attachment_path)
            <p>Current attachment stored at: {{ $event->attachment_path }}</p>
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
</script>
@endsection


