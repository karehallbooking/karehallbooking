@extends('layouts.admin')

@section('content')
<div style="margin-bottom: 16px;">
    <a class="back-to-dashboard" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
</div>

<div class="section-block">
    <h2>Faculty Access</h2>
    <p style="color:#475569; margin-top:4px;">Manage faculty list and admin access.</p>
    <div style="margin: 12px 0;">
        <input type="text" placeholder="Search faculty" style="width: 260px; padding: 8px;" disabled>
        <button type="button" style="padding: 8px 16px; margin-left: 6px;" disabled>Search</button>
    </div>
    <div style="margin-top: 16px; padding: 16px; border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc;">
        <strong>No faculty available.</strong>
        <p style="margin: 6px 0 0 0; color: #475569;">Add faculty data source later. “Make Admin” actions will appear here once faculty records are loaded.</p>
    </div>
</div>
@endsection
