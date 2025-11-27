

<?php $__env->startSection('content'); ?>

<?php if(!$selectedSection): ?>
    <!-- Main Dashboard - Show Section Cards -->
    <div class="section-block" style="margin-bottom: 24px;">
        <h2 style="margin: 0 0 8px; color: #0a2f6c;">Student Dashboard</h2>
        <p style="margin: 0; color: #4b5d77;">Select a section to view your events, registrations, and certificates.</p>
    </div>

    <div class="section-grid">
        <a href="<?php echo e(route('student.dashboard', ['section' => 'available'])); ?>" class="section-card">
            <div>
                <h3>Available Events</h3>
                <p>Browse and register for upcoming events.</p>
            </div>
            <span>View Available Events →</span>
        </a>

        <a href="<?php echo e(route('student.dashboard', ['section' => 'upcoming'])); ?>" class="section-card">
            <div>
                <h3>Upcoming Events</h3>
                <p>View your registered upcoming events and download QR codes.</p>
            </div>
            <span>View Upcoming →</span>
        </a>

        <a href="<?php echo e(route('student.dashboard', ['section' => 'history'])); ?>" class="section-card">
            <div>
                <h3>History</h3>
                <p>Review your past event registrations and attendance.</p>
            </div>
            <span>View History →</span>
        </a>

        <a href="<?php echo e(route('student.dashboard', ['section' => 'certificates'])); ?>" class="section-card">
            <div>
                <h3>Certificates</h3>
                <p>Download your event participation certificates.</p>
            </div>
            <span>View Certificates →</span>
        </a>
    </div>

<?php else: ?>
    <!-- Section Content - Show when section is selected -->
    <div style="margin-bottom: 16px;">
        <a href="<?php echo e(route('student.dashboard')); ?>" class="back-link">← Back to Dashboard</a>
    </div>

    <?php if($selectedSection === 'available'): ?>
        <!-- Available Events Section -->
        <div class="section-block">
            <h2 class="section-title" style="margin-top: 0;">Available Events</h2>
        </div>
        <?php if($availableEvents->count() > 0): ?>
            <div class="event-grid">
                <?php $__currentLoopData = $availableEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="event-card" onclick="window.location='<?php echo e(route('student.events.show', $event->id)); ?>'" style="cursor: pointer;">
                        <h3><?php echo e($event->title); ?></h3>
                        <p><strong>Organizer:</strong> <?php echo e($event->organizer); ?></p>
                        <p><strong>Department:</strong> <?php echo e($event->department); ?></p>
                        <p><strong>Date:</strong> 
                            <?php echo e($event->start_date->format('d M Y')); ?>

                            <?php if($event->end_date && $event->end_date != $event->start_date): ?>
                                - <?php echo e($event->end_date->format('d M Y')); ?>

                            <?php endif; ?>
                        </p>
                        <p><strong>Time:</strong> 
                            <?php echo e(date('H:i', strtotime($event->start_time))); ?>

                            <?php if($event->end_time): ?>
                                - <?php echo e(date('H:i', strtotime($event->end_time))); ?>

                            <?php endif; ?>
                        </p>
                        <p><strong>Venue:</strong> <?php echo e($event->venue); ?></p>
                        <p><strong>Seats:</strong> 
                            <?php echo e($event->capacity); ?> / <?php echo e($event->registrations_count); ?> / 
                            <?php if($event->seats_remaining > 0): ?>
                                <span style="color: #2e7d32;"><?php echo e($event->seats_remaining); ?> remaining</span>
                            <?php else: ?>
                                <span class="tag tag-booked">All Booked</span>
                            <?php endif; ?>
                        </p>
                        <p>
                            <?php if($event->is_paid): ?>
                                <span class="tag tag-paid">Paid - ₹<?php echo e(number_format($event->amount, 2)); ?></span>
                            <?php else: ?>
                                <span class="tag tag-free">Free</span>
                            <?php endif; ?>
                        </p>
                        <?php
                            $pdfCount = 0;
                            if($event->brochure_path) $pdfCount++;
                            if($event->attachment_path) $pdfCount++;
                        ?>
                        <?php if($pdfCount > 0): ?>
                            <p style="margin-top: 12px; margin-bottom: 8px;">
                                <strong style="color: #008B8B;">📄 PDFs Available (<?php echo e($pdfCount); ?>):</strong>
                            </p>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                <?php if($event->brochure_path): ?>
                                    <a href="#" onclick="event.stopPropagation(); openPdfModal('<?php echo e(route('student.events.brochure', $event->id)); ?>', 'Event Brochure'); return false;" class="pdf-link-simple">📑 View Brochure</a>
                                <?php endif; ?>
                                <?php if($event->attachment_path): ?>
                                    <a href="#" onclick="event.stopPropagation(); openPdfModal('<?php echo e(route('student.events.attachment', $event->id)); ?>', 'Event Attachment'); return false;" class="pdf-link-simple">📑 View Attachment</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <button 
                            type="button" 
                            class="btn btn-primary" 
                            style="width: 100%; margin-top: 12px;"
                            onclick="event.stopPropagation(); window.location='<?php echo e(route('student.events.show', $event->id)); ?>'"
                            <?php if($event->seats_remaining <= 0): ?> disabled <?php endif; ?>
                        >
                            <?php echo e($event->seats_remaining > 0 ? 'View Details & Register' : 'All Booked'); ?>

                        </button>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="section-block">
                <div class="empty-state">
                    <p>No events available at the moment.</p>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif($selectedSection === 'upcoming'): ?>
        <!-- Upcoming Events Section -->
        <div class="section-block">
            <h2 class="section-title" style="margin-top: 0;">Upcoming Events</h2>
        </div>
        <?php if($upcomingRegistrations->count() > 0): ?>
            <div class="section-block">
                <?php $__currentLoopData = $upcomingRegistrations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $registration): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="list-item">
                        <div class="list-item-info">
                            <strong><?php echo e($registration->event->title); ?></strong>
                            <span>
                                Date: <?php echo e($registration->event->start_date->format('d M Y')); ?>

                                <?php if($registration->event->end_date && $registration->event->end_date != $registration->event->start_date): ?>
                                    - <?php echo e($registration->event->end_date->format('d M Y')); ?>

                                <?php endif; ?>
                            </span>
                            <span style="display: block; margin-top: 4px;">
                                Status: 
                                <span style="color: <?php echo e($registration->payment_status === 'paid' || $registration->payment_status === 'free' ? '#2e7d32' : '#e65100'); ?>;">
                                    <?php echo e(ucfirst($registration->payment_status)); ?>

                                </span>
                            </span>
                        </div>
                        <div>
                            <?php if($registration->qr_code): ?>
                                <a href="#" onclick="openTicketModal('<?php echo e(route('student.ticket.show', $registration->id)); ?>'); return false;" class="btn btn-link">View Ticket</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="section-block">
                <div class="empty-state">
                    <p>You have no upcoming registered events.</p>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif($selectedSection === 'history'): ?>
        <!-- History Section -->
        <div class="section-block">
            <h2 class="section-title" style="margin-top: 0;">History</h2>
        </div>
        <?php if($historyRegistrations->count() > 0): ?>
            <div class="section-block">
                <?php $__currentLoopData = $historyRegistrations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $registration): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="list-item">
                        <div class="list-item-info">
                            <strong><?php echo e($registration->event->title); ?></strong>
                            <span>
                                Registered: <?php echo e($registration->registered_at->format('d M Y, h:i A')); ?>

                            </span>
                            <span style="display: block; margin-top: 4px;">
                                Attendance: 
                                <span style="color: <?php echo e($registration->attendance_status === 'present' ? '#2e7d32' : ($registration->attendance_status === 'absent' ? '#c62828' : '#666')); ?>;">
                                    <?php echo e(ucfirst($registration->attendance_status)); ?>

                                </span>
                            </span>
                        </div>
                        <div>
                            <?php if($registration->certificate_issued && $registration->certificate): ?>
                                <a href="#" onclick="openPdfModal('<?php echo e(route('student.certificates.view', $registration->certificate->id)); ?>', 'Certificate - <?php echo e($registration->event->title); ?>'); return false;" class="btn btn-link">View Certificate</a>
                                <a href="<?php echo e(route('student.certificates.download', $registration->certificate->id)); ?>" target="_blank" class="btn btn-link" style="margin-left: 8px;">Download</a>
                            <?php else: ?>
                                <span style="color: #999; font-size: 13px;">Certificate not issued</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="section-block">
                <div class="empty-state">
                    <p>No past event registrations found.</p>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif($selectedSection === 'certificates'): ?>
        <!-- Certificates Section -->
        <div class="section-block">
            <h2 class="section-title" style="margin-top: 0;">Certificates</h2>
        </div>
        <?php if($certificates->count() > 0): ?>
            <div class="section-block">
                <?php $__currentLoopData = $certificates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $certificate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="list-item">
                        <div class="list-item-info">
                            <strong><?php echo e($certificate->event->title); ?></strong>
                            <span>
                                Issued for: <?php echo e($certificate->registration->student_name); ?>

                            </span>
                            <span style="display: block; margin-top: 4px;">
                                Event Date: <?php echo e($certificate->event->start_date->format('d M Y')); ?>

                            </span>
                        </div>
                        <div>
                            <a href="#" onclick="openPdfModal('<?php echo e(route('student.certificates.view', $certificate->id)); ?>', 'Certificate - <?php echo e($certificate->event->title); ?>'); return false;" class="btn btn-link">View Certificate</a>
                            <a href="<?php echo e(route('student.certificates.download', $certificate->id)); ?>" target="_blank" class="btn btn-link" style="margin-left: 8px;">Download</a>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="section-block">
                <div class="empty-state">
                    <p>No certificates available.</p>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<script>
    function toggleForm(eventId) {
        const form = document.getElementById('form-' + eventId);
        if (form) {
            form.classList.toggle('active');
        }
    }

</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\sande\Downloads\KAREHALL\eventbookingstudents\resources\views/student/dashboard.blade.php ENDPATH**/ ?>