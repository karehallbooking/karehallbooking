@extends("kare.layout")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Book a Hall</h4>
  <a href="{{ route('kare.dashboard') }}" class="btn btn-outline-secondary">&larr; Back to Dashboard</a>
  </div>
@if($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
<form method="post" action="{{ route('kare.book.store') }}" enctype="multipart/form-data" class="row g-3">
  @csrf
  @if(!empty($selectedHall))
    <input type="hidden" name="hall_id" value="{{ $selectedHall->id }}" />
    <input type="hidden" name="hall_name" value="{{ $selectedHall->name }}" />
    <div class="col-md-6">
      <label class="form-label">Selected Hall</label>
      <input class="form-control" value="{{ $selectedHall->name }}" readonly>
    </div>
  @else
    <div class="col-md-6">
    <label class="form-label">Hall</label>
      <select name="hall_id" class="form-select">
        @foreach($halls as $id=>$name) <option value="{{ $id }}">{{ $name }}</option> @endforeach
    </select>
    </div>
  @endif

  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Check-in Date <span class="text-danger">*</span></label>
      <input type="date" name="event_date" class="form-control" placeholder="dd-mm-yyyy" required>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Check-out Date <span class="text-danger">*</span></label>
      <input type="date" name="event_date_checkout" class="form-control" placeholder="dd-mm-yyyy" required>
      <div class="form-text">For single-day events, select the same date.</div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">Time From <span class="text-danger">*</span></label>
      @php
        $times = [];
        for ($h = 0; $h < 24; $h++) {
          $times[] = sprintf('%02d:00', $h); // hourly steps
        }
      @endphp
      <select name="time_from" class="form-select" required onfocus="this.size=5;" onblur="this.size=1;" onchange="this.size=1; this.blur();">
        <option value="">Select time</option>
        @foreach($times as $t)
          <option value="{{ $t }}">{{ $t }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label">Time To <span class="text-danger">*</span></label>
      <select name="time_to" class="form-select" required onfocus="this.size=5;" onblur="this.size=1;" onchange="this.size=1; this.blur();">
        <option value="">Select time</option>
        @foreach($times as $t)
          <option value="{{ $t }}">{{ $t }}</option>
        @endforeach
      </select>
      <div class="form-text">Example: morning 06:00–12:00, afternoon 12:00–18:00</div>
    </div>
  </div>
  <div class="col-md-6"><label class="form-label">Organizer Name</label><input name="organizer_name" class="form-control" required></div>
  <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="organizer_email" class="form-control" required></div>
  <div class="col-md-3"><label class="form-label">Phone</label><input name="organizer_phone" class="form-control" required></div>

  <div class="col-md-6"><label class="form-label">Department</label><input name="organizer_department" class="form-control" required></div>
  <div class="col-md-6"><label class="form-label">Designation</label><input name="organizer_designation" class="form-control"></div>
  <div class="col-md-12"><label class="form-label">Purpose</label><textarea name="purpose" class="form-control" rows="2" required></textarea></div>
  <div class="col-md-6">
    <label class="form-label">Seating Capacity</label>
    @php($maxCap = !empty($selectedHall?->capacity) ? (int)$selectedHall->capacity : null)
    @if($maxCap)
      <div class="form-text mb-1">Hall capacity: {{ $maxCap }}</div>
      <input type="number" name="seating_capacity" class="form-control" min="1" max="{{ $maxCap }}" placeholder="Up to {{ $maxCap }}" required>
    @else
      <input type="number" name="seating_capacity" class="form-control" min="1" placeholder="Enter required seats" required>
    @endif
  </div>

  <!-- Facilities checkboxes -->
  <div class="col-md-12">
    <label class="form-label">Required Facilities</label>
    @if(!empty($availableFacilities) && count($availableFacilities) > 0)
      <div>
        @foreach($availableFacilities as $facility)
          <div class="form-check form-check-inline mb-1">
            <input class="form-check-input" type="checkbox" name="facilities_required[]" id="fac-{{ $loop->index }}" value="{{ $facility }}" {{ (is_array(old('facilities_required')) && in_array($facility, old('facilities_required'))) ? 'checked' : '' }}>
            <label class="form-check-label small" for="fac-{{ $loop->index }}">{{ $facility }}</label>
          </div>
        @endforeach
      </div>
    @else
      <div class="alert alert-info small mb-0">
        <i class="fa fa-info-circle me-1"></i> No facilities available for this hall. Please contact admin to add facilities.
      </div>
    @endif
  </div>

  <!-- PDF Uploads -->
  <div class="col-md-6">
    <label class="form-label">Event Brochure (PDF)</label>
    <input type="file" name="event_brochure" class="form-control" accept="application/pdf">
  </div>
  <div class="col-md-6">
    <label class="form-label">Approval Letter (PDF)</label>
    <input type="file" name="approval_letter" class="form-control" accept="application/pdf">
  </div>

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary">Submit Booking</button>
    <a href="{{ route('kare.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
@endsection
