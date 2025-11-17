@extends("kare.layout")
@section("content")
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">My Bookings</h4>
  <a href="{{ route('kare.dashboard') }}" class="btn btn-outline-secondary">&larr; Back to Dashboard</a>
</div>
<table class="table table-striped">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>Hall</th>
      <th>Date</th>
      <th>From</th>
      <th>To</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    @foreach($events as $e)
      <tr>
        <td>{{ $e->id }}</td>
        <td>{{ $e->hall_name }}</td>
        <td>{{ \Carbon\Carbon::parse($e->event_date)->format('Y-m-d') }}</td>
        <td>
          {{ \Carbon\Carbon::parse($e->event_date)->format('M d, Y') }} {{ substr($e->time_from, 0, 5) }}
        </td>
        <td>
          {{ $e->event_date_checkout ? \Carbon\Carbon::parse($e->event_date_checkout)->format('M d, Y') : \Carbon\Carbon::parse($e->event_date)->format('M d, Y') }} {{ substr($e->time_to, 0, 5) }}
        </td>
        <td>
          @if(strtolower($e->status) === 'approved')
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-success">Approved</span>
              <form action="http://127.0.0.1:8000/events/{{ $e->id }}/cancel" method="GET" class="cancel-form m-0 p-0" style="display:inline;">
                <input type="hidden" name="reason" value="">
                <button type="button" class="btn btn-sm btn-outline-danger cancel-btn">Cancel</button>
              </form>
            </div>
          @else
            <span>{{ ucfirst($e->status) }}</span>
          @endif
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
{{ $events->links() }}

<!-- Cancel Reason Modal -->
<div class="modal fade" id="cancelReasonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Cancel Booking</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label for="cancelReasonInput" class="form-label">Reason for cancellation</label>
        <textarea id="cancelReasonInput" class="form-control" rows="3" placeholder="Please enter a clear reason (min 5 characters)"></textarea>
        <small class="text-muted">Your request will be reviewed by admin.</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="submitCancelReasonBtn">Submit Request</button>
      </div>
    </div>
  </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var activeCancelForm = null;
  var reasonModal = document.getElementById('cancelReasonModal');
  var reasonInput = document.getElementById('cancelReasonInput');
  var submitBtn = document.getElementById('submitCancelReasonBtn');

  document.querySelectorAll('.cancel-form .cancel-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      activeCancelForm = e.target.closest('form');
      reasonInput.value = '';
      var modal = new bootstrap.Modal(reasonModal);
      modal.show();
    });
  });

  submitBtn.addEventListener('click', function () {
    var reason = (reasonInput.value || '').trim();
    if (reason.length < 5) {
      reasonInput.classList.add('is-invalid');
      return;
    }
    reasonInput.classList.remove('is-invalid');
    if (activeCancelForm) {
      activeCancelForm.querySelector('input[name="reason"]').value = reason;
      activeCancelForm.method = 'GET';
      activeCancelForm.submit();
    }
    var modal = bootstrap.Modal.getInstance(reasonModal);
    if (modal) modal.hide();
  });
});
</script>
@endsection
