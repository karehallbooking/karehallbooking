@extends("kare.layout")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="h3 fw-bold mb-0">Book a Hall</h2>
  <a href="{{ route('kare.dashboard') }}" class="btn btn-outline-secondary">&larr; Back to Dashboard</a>
  </div>
<p class="text-muted mb-4">Select a hall to make your booking request</p>

<div class="row g-4">
  @forelse($halls as $hall)
  <div class="col-md-6">
    <div class="card shadow-sm h-100">
      <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center">
            <div class="me-3 rounded bg-primary bg-opacity-10 p-3">
              <span class="fs-4">🏢</span>
            </div>
            <h5 class="mb-0 fw-bold">{{ $hall->name }}</h5>
          </div>
          <span class="text-success fw-semibold">Available</span>
        </div>

        <div class="text-muted mb-3">
          <div class="mb-2">👥 Capacity: {{ $hall->capacity }} people</div>
          @php($facilities = $hall->facilities_list ?? [])
          @if(!empty($facilities))
            <div class="small">Facilities:</div>
            <div class="mt-1">
              @foreach($facilities as $facility)
                <span class="badge text-bg-light border me-1 mb-1">{{ $facility }}</span>
              @endforeach
            </div>
          @else
            <div class="small text-muted">No facilities listed</div>
          @endif
        </div>

        <div class="mt-auto">
          <a href="{{ route('kare.book.withHall', ['hallId' => $hall->id]) }}" class="btn btn-primary">Select</a>
        </div>
      </div>
    </div>
  </div>
  @empty
  <div class="col-12"><div class="alert alert-info">No halls found.</div></div>
  @endforelse
</div>
@endsection
