<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Transaksi EO</title>

    <style>
        body{
            font-family: sans-serif;
            font-size: 12px;
        }

        table{
            width:100%;
            border-collapse: collapse;
            margin-top:20px;
        }

        table, th, td{
            border:1px solid #000;
        }

        th, td{
            padding:8px;
            text-align:left;
        }

        h2{
            margin-bottom:0;
        }

        .total{
            margin-top:20px;
            font-weight:bold;
        }
    </style>
</head>
<body>

    <h2>Laporan Transaksi EO</h2>
    <p>Tanggal Export: {{ now()->format('d-m-Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Event</th>
                <th>Email</th>
                <th>Status</th>
                <th>Total</th>
                <th>Checkout</th>
            </tr>
        </thead>

        <tbody>
            @foreach($transactions as $transaction)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $transaction->event->title ?? '-' }}</td>
                    <td>{{ $transaction->email }}</td>
                    <td>{{ ucfirst($transaction->payment_status) }}</td>
                    <td>
                        Rp{{ number_format($transaction->total_amount,0,',','.') }}
                    </td>
                    <td>{{ $transaction->checkout_time }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">
        Total Uang Masuk:
        Rp{{ number_format($totalPaidAmount,0,',','.') }}
    </p>

</body>
</html>