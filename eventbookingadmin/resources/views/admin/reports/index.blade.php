@extends('layouts.admin')

@section('content')
<div style="margin-bottom: 16px;">
    <a class="back-to-dashboard" href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
</div>
<div class="section-block">
    <h2>Export Full Event & Registration Report</h2>
    <form method="GET" action="{{ route('admin.reports.index') }}" class="form-row" style="flex-wrap:wrap;">
        <label style="flex:1 1 160px;">
            From date
            <input
                type="text"
                name="from"
                id="report-from-date"
                class="date-picker date-upcoming-only"
                placeholder="yyyy-mm-dd"
                data-linked-end="#report-to-date"
                data-allow-past="true"
                autocomplete="off"
                value="{{ $filterFrom ?? '' }}"
            >
        </label>
        <label style="flex:1 1 160px;">
            To date
            <input
                type="text"
                name="to"
                id="report-to-date"
                class="date-picker date-upcoming-only date-upcoming-end"
                placeholder="yyyy-mm-dd"
                data-linked-start="#report-from-date"
                data-allow-past="true"
                autocomplete="off"
                value="{{ $filterTo ?? '' }}"
            >
        </label>
        <div style="align-self:flex-end;">
            <button type="submit">Check Events</button>
        </div>
    </form>
    <p style="margin-top:8px;">Select a date range and click <strong>Check Events</strong> to see events in that period and download Excel reports.</p>

    @if(isset($filteredEvents) && $filteredEvents->count() > 0)
        <div style="margin-top:16px; border-top:1px solid #d2def2; padding-top:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                <div>
                    <strong>Events found:</strong> {{ $filteredEvents->count() }}
                    @if($filterFrom || $filterTo)
                        <span style="color:#555; margin-left:8px;">
                            Range:
                            @if($filterFrom) from <strong>{{ $filterFrom }}</strong>@endif
                            @if($filterTo) to <strong>{{ $filterTo }}</strong>@endif
                        </span>
                    @endif
                </div>
                <div>
                    <a href="{{ route('admin.reports.events.excel', ['from' => $filterFrom, 'to' => $filterTo]) }}" class="btn btn-primary btn-sm">
                        Download All Events (Excel)
                    </a>
                </div>
            </div>

            <table style="margin-top:12px;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Date Range</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($filteredEvents as $event)
                        <tr>
                            <td>{{ $event->id }}</td>
                            <td>{{ $event->title }}</td>
                            <td>{{ optional($event->start_date)->format('Y-m-d') }} to {{ optional($event->end_date)->format('Y-m-d') }}</td>
                            <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                <a href="{{ route('admin.reports.events.excel', ['event_id' => $event->id, 'from' => $filterFrom, 'to' => $filterTo]) }}" class="btn btn-outline btn-sm">
                                    Download Excel
                                </a>
                                <a href="{{ route('admin.reports.event.zip', $event->id) }}" class="btn btn-primary btn-sm">
                                    Download All (ZIP)
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection


