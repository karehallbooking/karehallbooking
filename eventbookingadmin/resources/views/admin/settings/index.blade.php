@extends('layouts.admin')

@section('content')
@php($view = request('view'))

@if(!$view)
    <div style="margin-bottom: 16px;">
        <a class="back-to-dashboard" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
    </div>
    <div class="card-grid">
        <div class="card">
            <h3>QR Secret Key</h3>
            <p>Update signing secret.</p>
            <a href="{{ route('admin.settings.index', ['view' => 'qr']) }}">Edit</a>
        </div>
        <div class="card">
            <h3>Event Rules / Limits</h3>
            <p>Set capacity guidelines.</p>
            <a href="{{ route('admin.settings.index', ['view' => 'rules']) }}">Edit</a>
        </div>
        <div class="card">
            <h3>Basic Configuration</h3>
            <p>System description.</p>
            <a href="{{ route('admin.settings.index', ['view' => 'system']) }}">Update</a>
        </div>
        <div class="card">
            <h3>Storage Path</h3>
            <p>Certificates location.</p>
            <a href="{{ route('admin.settings.index', ['view' => 'storage']) }}">Change</a>
        </div>
        <div class="card">
            <h3>Certificate Template</h3>
            <p>Default text.</p>
            <a href="{{ route('admin.settings.index', ['view' => 'template']) }}">Edit</a>
        </div>
        <div class="card">
            <h3>System Info</h3>
            <p>Display metadata.</p>
            <a href="{{ route('admin.settings.index', ['view' => 'system']) }}">Update</a>
        </div>
    </div>
@elseif($view === 'qr')
    <a class="back-link" href="{{ route('admin.settings.index') }}">Back to Settings</a>
    <div class="section-block">
        <h2>QR Secret Key</h2>
        <form method="POST" action="{{ route('admin.settings.qr-secret') }}">
            @csrf
            <input type="text" name="qr_secret_key" value="{{ $settings['qr_secret_key'] }}" required>
            <button type="submit">Update Secret</button>
        </form>
    </div>
@elseif($view === 'rules')
    <a class="back-link" href="{{ route('admin.settings.index') }}">Back to Settings</a>
    <div class="section-block">
        <h2>Event Rules / Limits</h2>
        <form method="POST" action="{{ route('admin.settings.event-rules') }}">
            @csrf
            <textarea name="event_rules" rows="4">{{ $settings['event_rules'] }}</textarea>
            <button type="submit">Save Rules</button>
        </form>
    </div>
@elseif($view === 'template')
    <a class="back-link" href="{{ route('admin.settings.index') }}">Back to Settings</a>
    <div class="section-block">
        <h2>Certificate Template</h2>
        <form method="POST" action="{{ route('admin.settings.certificate-template') }}">
            @csrf
            <textarea name="certificate_template" rows="5">{{ $settings['certificate_template'] }}</textarea>
            <button type="submit">Save Template</button>
        </form>
    </div>
@elseif($view === 'storage')
    <a class="back-link" href="{{ route('admin.settings.index') }}">Back to Settings</a>
    <div class="section-block">
        <h2>Storage Path for Certificates</h2>
        <form method="POST" action="{{ route('admin.settings.storage-path') }}">
            @csrf
            <input type="text" name="certificate_storage_path" value="{{ $settings['certificate_storage_path'] }}" required>
            <button type="submit">Update Path</button>
        </form>
    </div>
@elseif($view === 'system')
    <a class="back-link" href="{{ route('admin.settings.index') }}">Back to Settings</a>
    <div class="section-block">
        <h2>System Info</h2>
        <form method="POST" action="{{ route('admin.settings.system-info') }}">
            @csrf
            <textarea name="system_info" rows="3">{{ $settings['system_info'] }}</textarea>
            <button type="submit">Save Info</button>
        </form>
    </div>
@endif
@endsection


