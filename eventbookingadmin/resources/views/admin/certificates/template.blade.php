<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: 1080px 720px;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Montserrat', 'Arial', 'Helvetica', sans-serif;
            width: 1080px;
            height: 720px;
            position: relative;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        .certificate-container {
            position: relative;
            width: 1080px;
            height: 720px;
        }
        .template-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 1080px;
            height: 720px;
            z-index: 0;
            object-fit: cover;
        }
        .content-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 1080px;
            height: 720px;
            z-index: 1;
        }
        .name-line {
            position: absolute;
            left: 140px;
            width: 800px;
            top: calc(44% + 55px);
            font-family: 'Montserrat', 'Arial', 'Helvetica', sans-serif;
            font-weight: bold;
            font-size: 26px;
            line-height: 1.2;
            text-align: center;
            color: #000;
        }
        .date-line {
            position: absolute;
            left: 140px;
            width: 800px;
            top: calc(49% + 60px);
            font-family: 'Montserrat', 'Arial', 'Helvetica', sans-serif;
            font-weight: bold;
            font-size: 26px;
            line-height: 1.2;
            text-align: center;
            color: #000;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        @if(!empty($templateImagePath))
            <img class="template-background" src="{{ $templateImagePath }}" alt="Certificate Background">
        @else
            <div class="template-background" style="background: #f5f5f5;"></div>
        @endif
        
        <div class="content-overlay">
            <!-- Name Line: [prefix] + student_name -->
            <div class="name-line">
                {{ $textPrefix }} {{ $data['STUDENT_NAME'] }}
            </div>
            
            <!-- Date Line: [before_date] + event_date + [after_date] -->
            <div class="date-line">
                {{ $textBeforeDate }} {{ $data['EVENT_DATE'] }} {{ $textAfterDate }}
            </div>
        </div>
    </div>
</body>
</html>
