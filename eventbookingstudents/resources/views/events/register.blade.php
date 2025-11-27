@extends('layouts.student')

@section('content')

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 20px;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error" style="margin-bottom: 20px;">{{ session('error') }}</div>
@endif

<div style="margin-bottom: 16px;">
    <a href="{{ route('student.dashboard', ['section' => 'available']) }}" class="back-link">← Back to Available Events</a>
</div>

<!-- Event Details Section -->
<div class="section-block" style="margin-bottom: 24px;">
    <h2 style="margin: 0 0 16px; color: #0a2f6c; font-size: 28px;">{{ $event->title }}</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <div class="detail-item">
            <strong>Organizer:</strong> {{ $event->organizer }}
        </div>
        <div class="detail-item">
            <strong>Department:</strong> {{ $event->department }}
        </div>
        <div class="detail-item">
            <strong>Date:</strong> 
            {{ $event->start_date->format('d M Y') }}
            @if($event->end_date && $event->end_date != $event->start_date)
                - {{ $event->end_date->format('d M Y') }}
            @endif
        </div>
        <div class="detail-item">
            <strong>Time:</strong> 
            {{ date('H:i', strtotime($event->start_time)) }}
            @if($event->end_time)
                - {{ date('H:i', strtotime($event->end_time)) }}
            @endif
        </div>
        <div class="detail-item">
            <strong>Venue:</strong> {{ $event->venue }}
        </div>
        <div class="detail-item">
            <strong>Seats:</strong> 
            {{ $event->capacity }} / {{ $event->registrations_count }} / 
            @if($event->seats_remaining > 0)
                <span style="color: #2e7d32; font-weight: 600;">{{ $event->seats_remaining }} remaining</span>
            @else
                <span class="tag tag-booked">All Booked</span>
            @endif
        </div>
    </div>

    <div style="margin-bottom: 16px;">
        @if($event->is_paid && $event->amount > 0)
            <span class="tag tag-paid">Paid Event - ₹{{ number_format($event->amount, 2) }}</span>
            <p style="margin-top: 8px; color: #666; font-size: 14px;">Payment required to complete registration</p>
        @else
            <span class="tag tag-free">Free Event</span>
            <p style="margin-top: 8px; color: #666; font-size: 14px;">No payment required</p>
        @endif
    </div>

    @if($event->description)
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
            <strong style="color: #0a2f6c; display: block; margin-bottom: 8px;">Description:</strong>
            <p style="color: #555; line-height: 1.6;">{{ $event->description }}</p>
        </div>
    @endif
</div>

<!-- Registration Section -->
@if($hasPaidPayment && $ticketCode)
    <div class="section-block" style="background: #e8f5e9; border-color: #4caf50;">
        <h3 style="color: #2e7d32; margin: 0 0 12px;">✓ Payment Successful - You are registered!</h3>
        <p style="margin: 0; color: #555;">
            <strong>Ticket Code:</strong> {{ $ticketCode }}<br>
            <strong>Registration Date:</strong> {{ $registration->registered_at->format('d M Y, h:i A') ?? 'N/A' }}
        </p>
        <div style="margin-top: 12px;">
            <a href="{{ route('payment.success', ['ticket' => $ticketCode]) }}" class="btn btn-primary">View Ticket</a>
        </div>
    </div>
@elseif($registration && $registration->payment_status === 'pending')
    {{-- For paid events with pending payment, show registration form again to complete payment --}}
    <div class="section-block" style="background: #fff3cd; border-color: #ffc107;">
        <h3 style="color: #856404; margin: 0 0 12px;">⚠ Payment Required</h3>
        <p style="margin: 0; color: #555; margin-bottom: 16px;">Please complete the payment to confirm your registration and receive your ticket.</p>
        <form id="registration-form">
            @csrf
            <div class="form-container-row">
                <div class="form-group-box">
                    <label class="form-label-bold">Full Name *</label>
                    <small class="form-hint">(This name will appear on your certificate)</small>
                    <input type="text" name="student_name" id="student_name" value="{{ $registration->student_name }}" required class="form-input-box" readonly>
                </div>
                
                <div class="form-group-box">
                    <label class="form-label-bold">Email *</label>
                    <input type="email" name="student_email" id="student_email" value="{{ $registration->student_email }}" required class="form-input-box" readonly>
                </div>
                
                <div class="form-group-box">
                    <label class="form-label-bold">Roll Number / Student ID *</label>
                    <input type="text" name="student_roll" id="student_roll" value="{{ $registration->student_id }}" required class="form-input-box" readonly>
                </div>
                
                <div class="form-group-box">
                    <label class="form-label-bold">Phone (Optional)</label>
                    <input type="text" name="student_phone" id="student_phone" value="{{ $registration->student_phone }}" class="form-input-box" readonly>
                </div>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <button type="button" id="pay-btn" class="btn btn-primary" style="padding: 12px 32px; font-size: 15px; font-weight: 600;">Pay ₹{{ number_format($event->amount, 2) }} and Register</button>
            </div>
        </form>
    </div>
@elseif($event->seats_remaining <= 0)
    <div class="section-block" style="background: #ffebee; border-color: #ef5350;">
        <h3 style="color: #c62828; margin: 0;">All seats are booked for this event</h3>
    </div>
@else
    <div class="section-block">
        <h3 style="margin: 0 0 20px; color: #0a2f6c; font-size: 20px; border-bottom: 2px solid #008B8B; padding-bottom: 10px;">Register for this Event</h3>
        
        <form id="registration-form">
            @csrf
            <div class="form-container-row">
                <div class="form-group-box">
                    <label class="form-label-bold">Full Name *</label>
                    <small class="form-hint">(This name will appear on your certificate)</small>
                    <input type="text" name="student_name" id="student_name" value="{{ $studentName ?? old('student_name') }}" required class="form-input-box">
                </div>
                
                <div class="form-group-box">
                    <label class="form-label-bold">Email *</label>
                    <input type="email" name="student_email" id="student_email" value="{{ $studentEmail ?? old('student_email') }}" required class="form-input-box">
                </div>
                
                <div class="form-group-box">
                    <label class="form-label-bold">Roll Number / Student ID *</label>
                    <input type="text" name="student_roll" id="student_roll" value="{{ $studentRoll ?? old('student_roll') }}" required class="form-input-box">
                </div>
                
                <div class="form-group-box">
                    <label class="form-label-bold">Phone (Optional)</label>
                    <input type="text" name="student_phone" id="student_phone" value="{{ $studentPhone ?? old('student_phone') }}" class="form-input-box">
                </div>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                @if($event->is_paid && $event->amount > 0)
                    <button type="button" id="pay-btn" class="btn btn-primary" style="padding: 12px 32px; font-size: 15px; font-weight: 600;">Pay ₹{{ number_format($event->amount, 2) }} and Register</button>
                    <p style="margin-top: 12px; font-size: 13px; color: #666;">Click to proceed to secure payment gateway</p>
                @else
                    <button type="button" id="register-btn" class="btn btn-primary" style="padding: 12px 32px; font-size: 15px; font-weight: 600;">Confirm Registration</button>
                    <p style="margin-top: 12px; font-size: 13px; color: #666;">This is a free event - no payment required</p>
                @endif
            </div>
        </form>
    </div>
@endif

<!-- Loading Overlay -->
<div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 30px; border-radius: 8px; text-align: center;">
        <p style="margin: 0; font-size: 16px; color: #0a2f6c;">Processing payment...</p>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const payBtn = document.getElementById('pay-btn');
    const registerBtn = document.getElementById('register-btn');
    const loadingOverlay = document.getElementById('loading-overlay');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    function showLoading() {
        if (loadingOverlay) loadingOverlay.style.display = 'flex';
    }

    function hideLoading() {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    }

    if (payBtn) {
        payBtn.addEventListener('click', function() {
            const studentName = document.getElementById('student_name').value;
            const studentEmail = document.getElementById('student_email').value;
            const studentPhone = document.getElementById('student_phone').value;
            const studentRoll = document.getElementById('student_roll').value;

            if (!studentName || !studentEmail || !studentRoll) {
                alert('Please fill in all required fields.');
                return;
            }

            showLoading();

            // Create Razorpay order
            fetch('{{ route("events.createOrder", $event->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    student_name: studentName,
                    student_email: studentEmail,
                    student_phone: studentPhone,
                    student_roll: studentRoll
                })
            })
            .then(response => response.json())
            .then(function(data) {
                hideLoading();

                if (!data.success) {
                    alert(data.message || 'Failed to create payment order. Please try again.');
                    return;
                }

                if (data.already_paid) {
                    window.location.href = data.ticket_url;
                    return;
                }

                // Initialize Razorpay Checkout
                var options = {
                    key: data.razorpay_key,
                    amount: data.amount,
                    currency: data.currency,
                    name: "Kalasalingam Academy",
                    description: "Registration for " + data.event_name,
                    order_id: data.order_id,
                    prefill: {
                        name: data.student_name,
                        email: data.student_email,
                        contact: data.student_phone || ""
                    },
                    theme: {
                        color: "#0b5ed7"
                    },
                    handler: function (rzpResponse) {
                        showLoading();

                        // Send payment response to server for verification
                        fetch('{{ route("payment.success.post") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                razorpay_order_id: rzpResponse.razorpay_order_id,
                                razorpay_payment_id: rzpResponse.razorpay_payment_id,
                                razorpay_signature: rzpResponse.razorpay_signature
                            })
                        })
                        .then(res => res.json())
                        .then(function(responseData) {
                            hideLoading();
                            if (responseData.success && responseData.redirect_url) {
                                window.location.href = responseData.redirect_url;
                            } else {
                                alert(responseData.message || 'Payment verification failed. Please contact support.');
                                window.location.href = '{{ route("payment.failure") }}';
                            }
                        })
                        .catch(function(err) {
                            hideLoading();
                            console.error('Payment verification error:', err);
                            window.location.href = '{{ route("payment.failure") }}';
                        });
                    },
                    modal: {
                        ondismiss: function() {
                            hideLoading();
                            // User closed the modal
                        }
                    }
                };

                var rzp = new Razorpay(options);
                rzp.open();
            })
            .catch(function(err) {
                hideLoading();
                console.error('Error creating order:', err);
                alert('Failed to initialize payment. Please try again.');
            });
        });
    }

    if (registerBtn) {
        registerBtn.addEventListener('click', function() {
            // For free events, submit the form directly (no payment required)
            const studentName = document.getElementById('student_name').value;
            const studentEmail = document.getElementById('student_email').value;
            const studentRoll = document.getElementById('student_roll').value;

            if (!studentName || !studentEmail || !studentRoll) {
                alert('Please fill in all required fields.');
                return;
            }

            // Submit the form to the old registration route (for free events)
            const form = document.getElementById('registration-form');
            if (form) {
                form.action = '{{ route("student.events.register", $event->id) }}';
                form.method = 'POST';
                form.submit();
            }
        });
    }
});
</script>
@endpush

