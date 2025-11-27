<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'Student Dashboard - Event Booking'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            color: #0f243d;
            line-height: 1.6;
        }
        header {
            background: #0c5fd1;
            color: #fff;
            padding: 20px 24px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(9, 46, 120, 0.3);
        }
        header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        main {
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #0a2f6c;
            margin: 32px 0 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #0e63d8;
        }
        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 36px;
        }
        .section-card {
            border: 2px solid #0e63d8;
            padding: 20px;
            background: #fff;
            min-height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 59, 140, 0.2);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
        }
        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 59, 140, 0.3);
        }
        .section-card h3 {
            margin: 0 0 8px;
            font-size: 19px;
            color: #0b336b;
        }
        .section-card p {
            margin: 0 0 12px;
            font-size: 14px;
            color: #4b5d77;
        }
        .section-card span {
            margin-top: auto;
            text-decoration: none;
            font-size: 13px;
            color: #0a5ad4;
            font-weight: 500;
        }
        .section-block {
            background: #fff;
            border: 2px solid #0e63d8;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 8px 18px rgba(14, 63, 150, 0.15);
        }
        .detail-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #008B8B;
        }
        .detail-item strong {
            color: #0a2f6c;
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .back-link {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #1E90FF 0%, #0A66C2 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(14, 99, 210, 0.3);
        }
        .back-link:hover {
            background: linear-gradient(135deg, #0A66C2 0%, #1E90FF 100%);
            text-decoration: none;
        }
        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .event-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.2s;
        }
        .event-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .event-card h3 {
            color: #0a2f6c;
            margin: 0 0 12px;
            font-size: 20px;
        }
        .event-card p {
            margin: 6px 0;
            font-size: 14px;
            color: #555;
        }
        .event-card .tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin: 4px 4px 4px 0;
        }
        .tag-free {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .tag-paid {
            background: #fff3e0;
            color: #e65100;
        }
        .tag-booked {
            background: #ffebee;
            color: #c62828;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1E90FF 0%, #0A66C2 100%);
            color: #fff;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #0A66C2 0%, #1E90FF 100%);
        }
        .btn:disabled {
            background: #ccc;
            color: #666;
            cursor: not-allowed;
        }
        .btn-link {
            background: transparent;
            color: #0b5cc8;
            padding: 6px 12px;
            font-size: 13px;
        }
        .btn-link:hover {
            text-decoration: underline;
        }
        .pdf-link-simple {
            background: #008B8B;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid #008B8B;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .pdf-link-simple:hover {
            background: #006666;
            border-color: #006666;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 139, 139, 0.3);
            text-decoration: none;
            color: #fff;
        }
        .form-container-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        .form-container-two-col {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .form-group-box {
            background: #f8f9fa;
            border: 2px solid #008B8B;
            border-radius: 8px;
            padding: 12px;
            transition: all 0.2s;
        }
        .form-group-box:focus-within {
            border-color: #006666;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 139, 139, 0.15);
        }
        .form-label-bold {
            display: block;
            margin-bottom: 4px;
            font-weight: 700;
            color: #0a2f6c;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-hint {
            display: block;
            margin-bottom: 6px;
            font-size: 11px;
            color: #008B8B;
            font-weight: 500;
            font-style: italic;
        }
        .form-input-box {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #333;
            background: #fff;
            transition: all 0.2s;
        }
        .form-input-box:focus {
            outline: none;
            border-color: #008B8B;
            box-shadow: 0 0 0 3px rgba(0, 139, 139, 0.1);
            background: #fff;
        }
        @media (max-width: 1200px) {
            .form-container-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .form-container-row,
            .form-container-two-col {
                grid-template-columns: 1fr;
            }
        }
        .list-section {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .list-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .list-item:last-child {
            border-bottom: none;
        }
        .list-item-info {
            flex: 1;
        }
        .list-item-info strong {
            color: #0a2f6c;
            display: block;
            margin-bottom: 4px;
        }
        .list-item-info span {
            font-size: 13px;
            color: #666;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #4caf50;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .registration-form {
            background: #f9f9f9;
            padding: 16px;
            border-radius: 6px;
            margin-top: 12px;
            display: none;
        }
        .registration-form.active {
            display: block;
        }
        .form-group {
            margin-bottom: 12px;
        }
        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: #333;
            font-size: 13px;
        }
        .form-group label small {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            color: #008B8B;
            font-style: italic;
        }
        .form-group input::placeholder {
            color: #999;
            font-size: 12px;
        }
        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #0c5fd1;
        }
        .pdf-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            overflow: auto;
        }
        .pdf-modal.active {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .pdf-modal-header {
            width: 100%;
            max-width: 95%;
            background: #fff;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .pdf-modal-header h3 {
            margin: 0;
            color: #0a4a8a;
            font-size: 18px;
        }
        .pdf-modal-close {
            background: linear-gradient(135deg, #d8435e 0%, #c62828 100%);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }
        .pdf-modal-close:hover {
            background: linear-gradient(135deg, #c62828 0%, #b71c1c 100%);
        }
        .pdf-modal-body {
            width: 100%;
            max-width: 95%;
            height: calc(100% - 60px);
            background: #525252;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }
        .pdf-modal-body iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .ticket-modal {
            display: none;
            position: fixed;
            z-index: 10001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            overflow: auto;
        }
        .ticket-modal.active {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .ticket-modal-header {
            width: 100%;
            max-width: 400px;
            background: #fff;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .ticket-modal-header h3 {
            margin: 0;
            color: #0a4a8a;
            font-size: 18px;
        }
        .ticket-modal-close {
            background: linear-gradient(135deg, #d8435e 0%, #c62828 100%);
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }
        .ticket-modal-close:hover {
            background: linear-gradient(135deg, #c62828 0%, #b71c1c 100%);
        }
        .ticket-modal-body {
            width: 100%;
            max-width: 400px;
            height: calc(100% - 60px);
            max-height: 600px;
            background: #525252;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }
        .ticket-modal-body iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        @media (max-width: 768px) {
            .event-grid {
                grid-template-columns: 1fr;
            }
            main {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Student Dashboard</h1>
    </header>
    <main>
        <?php if(session('success')): ?>
            <div class="alert alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="alert alert-error"><?php echo e(session('error')); ?></div>
        <?php endif; ?>

        <!-- PDF Modal -->
        <div id="pdfModal" class="pdf-modal">
            <div class="pdf-modal-header">
                <h3 id="pdfModalTitle">PDF Viewer</h3>
                <button class="pdf-modal-close" onclick="closePdfModal()">Close</button>
            </div>
            <div class="pdf-modal-body">
                <iframe id="pdfFrame" src=""></iframe>
            </div>
        </div>

        <!-- Ticket Modal -->
        <div id="ticketModal" class="ticket-modal">
            <div class="ticket-modal-header">
                <h3 id="ticketModalTitle">Event Ticket</h3>
                <button class="ticket-modal-close" onclick="closeTicketModal()">Close</button>
            </div>
            <div class="ticket-modal-body">
                <iframe id="ticketFrame" src=""></iframe>
            </div>
        </div>

        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <script>
        function openPdfModal(pdfUrl, title) {
            document.getElementById('pdfModalTitle').textContent = title;
            document.getElementById('pdfFrame').src = pdfUrl;
            document.getElementById('pdfModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closePdfModal() {
            document.getElementById('pdfModal').classList.remove('active');
            document.getElementById('pdfFrame').src = '';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside the PDF viewer
        document.getElementById('pdfModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePdfModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePdfModal();
            }
        });
    </script>

    <script>
        // Ticket Modal Functions
        function openTicketModal(ticketUrl) {
            const modal = document.getElementById('ticketModal');
            const frame = document.getElementById('ticketFrame');
            const title = document.getElementById('ticketModalTitle');
            
            if (modal && frame && title) {
                title.textContent = 'Event Ticket';
                frame.src = ticketUrl;
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeTicketModal() {
            const modal = document.getElementById('ticketModal');
            const frame = document.getElementById('ticketFrame');
            
            if (modal && frame) {
                modal.classList.remove('active');
                frame.src = '';
                document.body.style.overflow = 'auto';
            }
        }

        // Close ticket modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const ticketModal = document.getElementById('ticketModal');
            if (ticketModal) {
                ticketModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeTicketModal();
                    }
                });
            }

            // Close ticket modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const pdfModal = document.getElementById('pdfModal');
                    const ticketModal = document.getElementById('ticketModal');
                    if (ticketModal && ticketModal.classList.contains('active')) {
                        closeTicketModal();
                    } else if (pdfModal && pdfModal.classList.contains('active')) {
                        closePdfModal();
                    }
                }
            });
        });
    </script>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>

<?php /**PATH C:\Users\sande\Downloads\KAREHALL\eventbookingstudents\resources\views/layouts/student.blade.php ENDPATH**/ ?>