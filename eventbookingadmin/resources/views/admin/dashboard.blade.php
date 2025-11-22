@extends('layouts.admin')

@section('content')
<div class="status-banner">
    System Update: All services are operational.
</div>

<div class="section-block" style="margin-bottom: 24px;">
    <h2>Admin Control Center</h2>
    <p>Monitor quick stats and jump straight into any management section.</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <p>Total Events</p>
        <strong>{{ $totalEvents }}</strong>
    </div>
    <div class="stat-card">
        <p>Upcoming Events</p>
        <strong>{{ $upcomingEvents }}</strong>
    </div>
    <div class="stat-card">
        <p>Total Registrations</p>
        <strong>{{ $totalRegistrations }}</strong>
    </div>
    <div class="stat-card">
        <p>Today's Attendance Logs</p>
        <strong>{{ $todayAttendance }}</strong>
    </div>
</div>

@php
    $sectionCards = [
        [
            'title' => 'Events Hub',
            'description' => 'Create, edit, cancel and export events.',
            'url' => route('admin.events.index'),
        ],
        [
            'title' => 'Registrations',
            'description' => 'Filter, update, mark attendance or export CSV.',
            'url' => route('admin.registrations.index'),
        ],
        [
            'title' => 'QR Scanner',
            'description' => 'Verify QR strings and mark attendance instantly.',
            'url' => route('admin.scanner.index'),
        ],
        [
            'title' => 'Attendance Logs',
            'description' => 'Review history, apply filters and bulk mark absent.',
            'url' => route('admin.attendance.index'),
        ],
        [
            'title' => 'Certificates',
            'description' => 'Generate, download, revoke or bulk create PDFs.',
            'url' => route('admin.certificates.index'),
        ],
        [
            'title' => 'Payments',
            'description' => 'Track paid, pending and refunded transactions.',
            'url' => route('admin.payments.index'),
        ],
        [
            'title' => 'Reports & Export',
            'description' => 'Download monthly PDFs or event summaries.',
            'url' => route('admin.reports.index'),
        ],
        [
            'title' => 'Settings',
            'description' => 'Update QR secret, event rules, storage paths.',
            'url' => route('admin.settings.index'),
        ],
    ];
@endphp

<div class="section-grid">
    @foreach($sectionCards as $section)
        <a class="section-card section-card-link" href="{{ $section['url'] }}">
            <div>
                <h3>{{ $section['title'] }}</h3>
                <p>{{ $section['description'] }}</p>
            </div>
            <span>Open {{ $section['title'] }}</span>
        </a>
    @endforeach
</div>
@endsection


