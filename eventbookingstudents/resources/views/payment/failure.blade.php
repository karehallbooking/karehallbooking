@extends('layouts.student')

@section('content')

<div style="margin-bottom: 16px;">
    <a href="{{ route('student.dashboard') }}" class="back-link">← Back to Dashboard</a>
</div>

<div class="section-block" style="background: #ffebee; border-color: #ef5350; text-align: center; max-width: 600px; margin: 0 auto;">
    <div style="font-size: 48px; margin-bottom: 16px;">✗</div>
    <h2 style="color: #c62828; margin: 0 0 16px;">Payment Failed or Cancelled</h2>
    <p style="color: #555; margin-bottom: 24px;">
        Your payment could not be processed. This could be due to:
    </p>
    <ul style="text-align: left; display: inline-block; color: #555; margin-bottom: 24px;">
        <li>Payment was cancelled</li>
        <li>Insufficient funds</li>
        <li>Network error</li>
        <li>Payment gateway issue</li>
    </ul>

    <div style="margin-top: 24px;">
        <a href="{{ route('student.dashboard', ['section' => 'available']) }}" class="btn btn-primary">Browse Events</a>
    </div>

    <p style="margin-top: 24px; color: #999; font-size: 14px;">
        If you believe this is an error, please contact support with your registration details.
    </p>
</div>

@endsection

