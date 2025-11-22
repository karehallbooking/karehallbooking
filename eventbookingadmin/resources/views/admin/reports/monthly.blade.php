<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <h1>Monthly Registration Report</h1>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Total Registrations</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    <td>{{ $row->month }}</td>
                    <td>{{ $row->total }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>


