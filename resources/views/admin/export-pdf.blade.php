<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export PDF - Transactions</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 6px;
            border: 1px solid #000;
            text-align: left;
        }
        th {
            background-color: #eee;
        }
    </style>
</head>
<body>
    <h2>Transaction Report</h2>

    <h3>Total Uang Masuk (Paid): Rp{{ number_format($totalPaidAmount, 0, ',', '.') }}</h3>

    <table>
        <thead>
            <tr>
                <th>Email</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Checkout Time</th>
                <th>Paid Time</th>
                <th>Status</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
                @if($transaction->attendees->isEmpty())
                    <tr>
                        <td>{{ $transaction->email ?? '-' }}</td>
                        <td>-</td>
                        <td>-</td>
                        <td>{{ $transaction->checkout_time ?? '-' }}</td>
                        <td>{{ $transaction->paid_time ?? '-' }}</td>
                        <td>{{ ucfirst($transaction->payment_status ?? '-') }}</td>
                        <td>Rp{{ number_format($transaction->total_amount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @else
                    @foreach($transaction->attendees as $attendee)
                        <tr>
                            <td>{{ $transaction->email ?? '-' }}</td>
                            <td>{{ $attendee->name ?? '-' }}</td>
                            <td>{{ $attendee->phone_number ?? '-' }}</td>
                            <td>{{ $transaction->checkout_time ?? '-' }}</td>
                            <td>{{ $transaction->paid_time ?? '-' }}</td>
                            <td>{{ ucfirst($transaction->payment_status ?? '-') }}</td>
                            <td>Rp{{ number_format($transaction->total_amount ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>
</body>
</html>
