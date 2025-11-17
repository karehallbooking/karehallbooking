@extends("kare.layout")
@section("content")
<div class="row g-3">
  <div class="col"><div class="card"><div class="card-body">Total: {{ $stats["total"] }}</div></div></div>
  <div class="col"><div class="card"><div class="card-body">Upcoming: {{ $stats["upcoming"] }}</div></div></div>
  <div class="col"><div class="card"><div class="card-body">Approved: {{ $stats["approved"] }}</div></div></div>
  <div class="col"><div class="card"><div class="card-body">Pending: {{ $stats["pending"] }}</div></div></div>
</div>
@endsection
