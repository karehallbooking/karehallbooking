<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Event Booking Admin' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            color-scheme: light;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            background: #edf2f8;
            color: #0f243d;
        }
        header {
            background: #0c5fd1;
            color: #fff;
            padding: 28px 24px;
            text-align: center;
            box-shadow: 0 4px 18px rgba(9, 46, 120, 0.35);
        }
        header h1 {
            margin: 0;
            font-size: 30px;
            font-weight: 600;
            letter-spacing: 0.6px;
        }
        main {
            padding: 28px;
            max-width: 1400px;
            margin: 0 auto;
        }
        a {
            color: #0b5cc8;
            text-decoration: none;
        }
        a:hover {
            text-decoration: none;
        }
        .status-banner {
            background: #009aa7;
            color: #fff;
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-weight: 500;
            box-shadow: 0 6px 16px rgba(0, 154, 167, 0.28);
            text-align: center;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            margin-bottom: 36px;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }
        .stat-card {
            border: 2px solid #0e63d8;
            padding: 18px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 18px rgba(14, 63, 150, 0.15);
        }
        .stat-card p {
            margin: 0 0 6px;
            font-size: 13px;
            font-weight: 500;
            color: #44546a;
        }
        .stat-card strong {
            font-size: 22px;
            color: #0a2f6c;
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
        .section-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .section-card-link {
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }
        .section-card-link span {
            margin-top: auto;
            text-decoration: none;
            font-size: 13px;
            color: #0a5ad4;
        }
        .card {
            border: 2px solid #0e63d8;
            padding: 18px;
            background: #fff;
            min-height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(16, 55, 130, 0.18);
        }
        .card h3 {
            margin: 0 0 8px;
            font-size: 17px;
            color: #0c336b;
        }
        .card p {
            margin: 0 0 12px;
            font-size: 14px;
            color: #546579;
        }
        .card a,
        .card button,
        .card form button {
            display: inline-block;
            padding: 9px 14px;
            border: 1px solid #0b5cc8;
            background: #0c5fd1;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
            border-radius: 6px;
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .card button:hover,
        .card a:hover {
            background: #0a48a0;
            color: #fff;
            text-decoration: none;
            transform: translateY(-1px);
        }
        .section-block {
            border: 2px solid #0e63d8;
            padding: 22px;
            margin-bottom: 28px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(12, 52, 123, 0.18);
        }
        .section-block h2 {
            margin-top: 0;
            font-size: 20px;
            color: #0a2f6a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(13, 49, 115, 0.18);
        }
        th, td {
            border: 1px solid #d2def2;
            padding: 10px;
            font-size: 13px;
            text-align: left;
            color: #1b2f4a;
        }
        th {
            background: #eaf1ff;
            font-weight: 600;
        }
        form input,
        form select,
        form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #b9cbed;
            background: #fff;
            color: #0f243d;
            font-size: 14px;
            border-radius: 6px;
        }
        form label {
            display: flex;
            flex-direction: column;
            font-size: 13px;
            gap: 4px;
            color: #203553;
        }
        .form-row {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .form-row.two-col > * {
            flex: 1;
            min-width: 220px;
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .alert {
            border: 1px solid #0c5fd1;
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #e8f0ff;
        }
        .alert-success {
            background: #e3f7ef;
            border-color: #23a96a;
        }
        .alert-error {
            background: #fdecef;
            border-color: #d8435e;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #1E90FF 0%, #0A66C2 100%);
            color: #ffffff;
            font-weight: bold;
            font-size: 14px;
            text-decoration: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(30, 144, 255, 0.25), 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }
        .back-link:hover {
            background: linear-gradient(135deg, #0A66C2 0%, #0056b3 100%);
            box-shadow: 0 4px 12px rgba(30, 144, 255, 0.35), 0 2px 6px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
            text-decoration: none;
        }
        .back-link:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(30, 144, 255, 0.3), 0 1px 3px rgba(0, 0, 0, 0.12);
        }
        .back-link::before {
            content: "←";
            font-size: 16px;
            font-weight: bold;
            line-height: 1;
        }
        .back-to-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #1E90FF 0%, #0A66C2 100%);
            color: #ffffff;
            font-weight: bold;
            font-size: 14px;
            text-decoration: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(30, 144, 255, 0.25), 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }
        .back-to-dashboard:hover {
            background: linear-gradient(135deg, #0A66C2 0%, #0056b3 100%);
            box-shadow: 0 4px 12px rgba(30, 144, 255, 0.35), 0 2px 6px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }
        .back-to-dashboard:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(30, 144, 255, 0.3), 0 1px 3px rgba(0, 0, 0, 0.12);
        }
        .back-to-dashboard::before {
            content: "←";
            font-size: 16px;
            font-weight: bold;
            line-height: 1;
        }
        button,
        .actions a,
        .actions button,
        table a,
        table button,
        form button,
        form a {
            text-decoration: none !important;
        }
        button:hover,
        .actions a:hover,
        .actions button:hover,
        table a:hover,
        table button:hover,
        form button:hover,
        form a:hover {
            text-decoration: none !important;
        }
    </style>
</head>
<body>
    <header>
        <h1>{{ $title ?? 'Event Booking Admin' }}</h1>
    </header>
    <main>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">
                <strong>Validation Errors:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof flatpickr === 'undefined') {
                return;
            }

            const isoToday = new Date().toISOString().split('T')[0];

            function updateEndPicker(startInput) {
                const selector = startInput.dataset.linkedEnd;
                if (!selector) {
                    return;
                }
                const endInput = document.querySelector(selector);
                if (!endInput) {
                    return;
                }
                const allowPastEnd = endInput.dataset.allowPast === 'true';
                const minDate = startInput.value || (allowPastEnd ? null : isoToday);

                if (endInput._flatpickr) {
                    endInput._flatpickr.set('minDate', minDate || (allowPastEnd ? null : 'today'));
                    if (minDate && endInput.value && endInput.value < minDate) {
                        endInput._flatpickr.setDate(minDate, true);
                    }
                } else if (!allowPastEnd) {
                    endInput.min = minDate || isoToday;
                    if (minDate && endInput.value && endInput.value < minDate) {
                        endInput.value = minDate;
                    }
                }
            }

            document.querySelectorAll('.date-picker').forEach(function (input) {
                const allowPast = input.dataset.allowPast === 'true';
                const defaultDate = input.value || (allowPast ? null : isoToday);

                const config = {
                    dateFormat: 'Y-m-d',
                    allowInput: true,
                    disableMobile: true,
                    defaultDate: defaultDate,
                    minDate: allowPast ? null : 'today',
                    onChange: function (selectedDates, dateStr) {
                        input.value = dateStr;
                        if (input.dataset.linkedEnd) {
                            updateEndPicker(input);
                        }
                    },
                    onReady: function () {
                        if (input.dataset.linkedStart) {
                            const startInput = document.querySelector(input.dataset.linkedStart);
                            if (startInput) {
                                const sync = function () {
                                    const minDate = startInput.value || (allowPast ? null : isoToday);
                                    input._flatpickr.set('minDate', minDate || (allowPast ? null : 'today'));
                                    if (minDate && input.value && input.value < minDate) {
                                        input._flatpickr.setDate(minDate, true);
                                    }
                                };
                                startInput.addEventListener('change', sync);
                                sync();
                            }
                        }
                    }
                };

                input._flatpickr = flatpickr(input, config);

                if (input.dataset.linkedEnd) {
                    updateEndPicker(input);
                }
            });
        });
    </script>
</body>
</html>

