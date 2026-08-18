<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
        }
        h1 {
            font-size: 16px;
            margin: 0 0 4px;
        }
        .meta {
            color: #6b7280;
            margin-bottom: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #78736e;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #4f46e5;
            color: #ffffff;
            font-weight: bold;
        }
        tr:nth-child(even) td {
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">Generated {{ $generatedAt }} · {{ count($rows) }} {{ count($rows) === 1 ? 'record' : 'records' }}</div>

    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell === null || $cell === '' ? '—' : $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(1, count($headers)) }}">No records match the current filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
