@extends("kare.layout")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">My Cancel Requests</h4>
  <a href="{{ route('kare.dashboard') }}" class="btn btn-outline-secondary">&larr; Back to Dashboard</a>
  </div>

@forelse($requests as $r)
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-bold">{{ $r->hall_name }}</div>
          <div class="text-muted small">Event on {{ $r->event_date }} | Submitted {{ $r->created_at }}</div>
        </div>
        <span class="badge {{ $r->status === 'approved' ? 'bg-success' : ($r->status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') }} text-uppercase">{{ $r->status }}</span>
      </div>
      @if($r->reason)
        <div class="mt-2"><strong>Reason:</strong> {{ $r->reason }}</div>
      @endif
      @if($r->status === 'rejected' && !empty($r->admin_response))
        <div class="alert alert-warning mt-2 p-2"><strong>Admin Response:</strong> {{ $r->admin_response }}</div>
      @endif
    </div>
  </div>
@empty
  <div class="alert alert-info">You have no cancel requests yet.</div>
@endforelse
@endsection


