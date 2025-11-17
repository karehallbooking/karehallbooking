@extends("kare.layout")
@section("content")
<div class="mb-4">
  <h2 class="h3 fw-bold">User Dashboard</h2>
  <p class="text-muted">Manage your hall bookings and events</p>
  <div class="row g-3 mt-2">
    <div class="col-md-3">
      <div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Total Bookings</div><div class="fs-4 fw-bold">{{ $stats['total'] }}</div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Pending</div><div class="fs-4 fw-bold">{{ $stats['pending'] }}</div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Approved</div><div class="fs-4 fw-bold">{{ $stats['approved'] }}</div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Upcoming</div><div class="fs-4 fw-bold">{{ $stats['upcoming'] }}</div></div></div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-md-4">
    <a href="{{ route('kare.halls') }}" class="text-decoration-none">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold mb-2">Book New Hall</h5>
          <div class="text-muted">Make a new hall booking request</div>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="{{ route('kare.bookings.pending') }}" class="text-decoration-none">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold mb-2">Pending Requests</h5>
          <div class="text-muted">View your pending booking requests</div>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="{{ route('kare.bookings.approved') }}" class="text-decoration-none">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold mb-2">Approved Events</h5>
          <div class="text-muted">View your approved events</div>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="{{ route('kare.cancelRequests') }}" class="text-decoration-none">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold mb-2">Cancel Requests</h5>
          <div class="text-muted">View your submitted cancellation requests</div>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="{{ route('kare.bookings.rejected') }}" class="text-decoration-none">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold mb-2">Rejected Events</h5>
          <div class="text-muted">View your rejected events</div>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="{{ route('kare.myBookings') }}" class="text-decoration-none">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold mb-2">All Bookings</h5>
          <div class="text-muted">View all your booking history</div>
        </div>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="{{ route('kare.bookings.upcoming') }}" class="text-decoration-none">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="fw-bold mb-2">Upcoming Events</h5>
          <div class="text-muted">View your upcoming events</div>
        </div>
      </div>
    </a>
  </div>
</div>
@endsection
