

<?php $__env->startSection('content'); ?>

<?php if(session('success')): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-error" style="margin-bottom: 20px;"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<div style="margin-bottom: 16px;">
    <a href="<?php echo e(route('student.dashboard', ['section' => 'available'])); ?>" class="back-link">← Back to Available Events</a>
</div>

<!-- Event Details Section -->
<div class="section-block" style="margin-bottom: 24px;">
    <h2 style="margin: 0 0 16px; color: #0a2f6c; font-size: 28px;"><?php echo e($event->title); ?></h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <div class="detail-item">
            <strong>Organizer:</strong> <?php echo e($event->organizer); ?>

        </div>
        <div class="detail-item">
            <strong>Date:</strong> 
            <?php echo e($event->start_date->format('d M Y')); ?>

            <?php if($event->end_date && $event->end_date != $event->start_date): ?>
                - <?php echo e($event->end_date->format('d M Y')); ?>

            <?php endif; ?>
        </div>
        <div class="detail-item">
            <strong>Time:</strong> 
            <?php echo e(date('H:i', strtotime($event->start_time))); ?>

            <?php if($event->end_time): ?>
                - <?php echo e(date('H:i', strtotime($event->end_time))); ?>

            <?php endif; ?>
        </div>
        <div class="detail-item">
            <strong>Venue:</strong> <?php echo e($event->venue); ?>

        </div>
        <div class="detail-item">
            <strong>Seats:</strong> 
            <?php echo e($event->capacity); ?> / <?php echo e($event->registrations_count); ?> / 
            <?php if($event->seats_remaining > 0): ?>
                <span style="color: #2e7d32; font-weight: 600;"><?php echo e($event->seats_remaining); ?> remaining</span>
            <?php else: ?>
                <span class="tag tag-booked">All Booked</span>
            <?php endif; ?>
        </div>
        <?php if($event->faculty_coordinator_name || $event->faculty_coordinator_contact): ?>
            <div class="detail-item">
                <strong>Faculty Coordinator:</strong>
                <div><?php echo e($event->faculty_coordinator_name ?? 'NA'); ?></div>
                <?php if($event->faculty_coordinator_contact): ?>
                    <div>Contact: <?php echo e($event->faculty_coordinator_contact); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if($event->student_coordinator_name || $event->student_coordinator_contact): ?>
            <div class="detail-item">
                <strong>Student Coordinator:</strong>
                <div><?php echo e($event->student_coordinator_name ?? 'NA'); ?></div>
                <?php if($event->student_coordinator_contact): ?>
                    <div>Contact: <?php echo e($event->student_coordinator_contact); ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 16px;">
        <?php if($event->is_paid): ?>
            <span class="tag tag-paid">Paid - ₹<?php echo e(number_format($event->amount, 2)); ?></span>
        <?php else: ?>
            <span class="tag tag-free">Free</span>
        <?php endif; ?>
    </div>

    <?php if($event->description): ?>
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
            <strong style="color: #0a2f6c; display: block; margin-bottom: 8px;">Description:</strong>
            <p style="color: #555; line-height: 1.6;"><?php echo e($event->description); ?></p>
        </div>
    <?php endif; ?>

    <?php if($pdfCount > 0): ?>
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
            <strong style="color: #008B8B; display: block; margin-bottom: 12px;">📄 PDFs Available (<?php echo e($pdfCount); ?>):</strong>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <?php if($event->brochure_path): ?>
                    <a href="#" onclick="openPdfModal('<?php echo e(route('student.events.brochure', $event->id)); ?>', 'Event Brochure'); return false;" class="pdf-link-simple">📑 View Brochure</a>
                <?php endif; ?>
                <?php if($event->attachment_path): ?>
                    <a href="#" onclick="openPdfModal('<?php echo e(route('student.events.attachment', $event->id)); ?>', 'Event Attachment'); return false;" class="pdf-link-simple">📑 View Attachment</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Registration Section -->
<?php if($existingRegistration): ?>
    <?php if($existingRegistration->payment_status === 'paid' && $existingRegistration->ticket): ?>
        <div class="section-block" style="background: #e8f5e9; border-color: #4caf50;">
            <h3 style="color: #2e7d32; margin: 0 0 12px;">✓ You are registered for this event</h3>
            <p style="margin: 0; color: #555;">
                <strong>Registration Date:</strong> <?php echo e($existingRegistration->registered_at->format('d M Y, h:i A')); ?><br>
                <strong>Ticket Code:</strong> <?php echo e($existingRegistration->ticket->ticket_code); ?>

            </p>
            <div style="margin-top: 12px;">
                <a href="#" onclick="openTicketModal('<?php echo e(route('student.ticket.show', $existingRegistration->id)); ?>'); return false;" class="btn btn-primary">View Ticket</a>
            </div>
        </div>
    <?php elseif($existingRegistration->payment_status === 'pending' && $event->is_paid): ?>
        <div class="section-block" style="background: #fff3cd; border-color: #ffc107;">
            <h3 style="color: #856404; margin: 0 0 12px;">⚠ Payment Required</h3>
            <p style="margin: 0; color: #555; margin-bottom: 16px;">Your registration is incomplete. Please complete the payment to receive your ticket.</p>
            <div style="margin-top: 12px;">
                <a href="<?php echo e(route('events.register', $event->id)); ?>" class="btn btn-primary">Complete Payment</a>
            </div>
        </div>
    <?php else: ?>
        <div class="section-block" style="background: #e8f5e9; border-color: #4caf50;">
            <h3 style="color: #2e7d32; margin: 0 0 12px;">✓ You are registered for this event</h3>
            <p style="margin: 0; color: #555;">
                <strong>Registration Date:</strong> <?php echo e($existingRegistration->registered_at->format('d M Y, h:i A')); ?>

            </p>
            <?php if($existingRegistration->qr_code): ?>
                <div style="margin-top: 12px;">
                    <a href="#" onclick="openTicketModal('<?php echo e(route('student.ticket.show', $existingRegistration->id)); ?>'); return false;" class="btn btn-primary">View Ticket</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php elseif($event->seats_remaining <= 0): ?>
    <div class="section-block" style="background: #ffebee; border-color: #ef5350;">
        <h3 style="color: #c62828; margin: 0;">All seats are booked for this event</h3>
    </div>
<?php else: ?>
    <div class="section-block">
        <h3 style="margin: 0 0 20px; color: #0a2f6c; font-size: 20px; border-bottom: 2px solid #008B8B; padding-bottom: 10px;">Register for this Event</h3>
        <?php if($event->is_paid && $event->amount > 0): ?>
            <p style="margin-bottom: 16px; color: #555;">This is a paid event. Please fill in your details below and click the payment button to continue.</p>
        <?php endif; ?>
        <form method="POST" action="<?php echo e(route('student.events.register', $event->id)); ?>" id="registration-form">
            <?php echo csrf_field(); ?>
            <div class="form-container-row">
                <div class="form-group-box">
                    <label class="form-label-bold">Full Name *</label>
                    <small class="form-hint">(This name will appear on your certificate)</small>
                    <input type="text" name="student_name" value="<?php echo e(old('student_name')); ?>" required class="form-input-box">
                </div>
                
                <div class="form-group-box">
                    <label class="form-label-bold">Email *</label>
                    <input type="email" name="student_email" value="<?php echo e(old('student_email')); ?>" required class="form-input-box">
                </div>
                
                <div class="form-group-box">
                    <label class="form-label-bold">Roll Number / Student ID *</label>
                    <input type="text" name="student_roll" value="<?php echo e(old('student_roll')); ?>" required class="form-input-box">
                </div>
                
                <div class="form-group-box">
                    <label class="form-label-bold">Phone (Optional)</label>
                    <input type="text" name="student_phone" value="<?php echo e(old('student_phone')); ?>" class="form-input-box">
                </div>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <?php if($event->is_paid && $event->amount > 0): ?>
                    <button type="submit" class="btn btn-primary" style="padding: 12px 32px; font-size: 15px; font-weight: 600;">Pay ₹<?php echo e(number_format($event->amount, 2)); ?> and Register</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary" style="padding: 12px 32px; font-size: 15px; font-weight: 600;">Confirm Registration</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\sande\Downloads\KAREHALL\eventbookingstudents\resources\views/student/events/show.blade.php ENDPATH**/ ?>