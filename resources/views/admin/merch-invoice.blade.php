<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice Merch</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; padding: 20px; }
        .invoice { border: 1px solid #ddd; border-radius: 10px; padding: 20px; }
        .title { font-size: 20px; font-weight: bold; margin-bottom: 15px; }
        .qrcode { margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="title">Invoice Pembelian Merchandise</div>

        <p><strong>Pembeli:</strong> {{ $transaction->email }}</p>
        <p><strong>Kode Unik:</strong> {{ $transaction->kode_unik }}</p>
        <p><strong>Total:</strong> Rp {{ number_format($transaction->amount, 0, ',', '.') }}</p>
        <p><strong>Status:</strong> {{ $transaction->status }}</p>

        <div class="qrcode">
            <img src="{{ base_path('public_html/' . $transaction->qr_code) }}" 
                 width="200" height="200" alt="QR Code">
            <p>Gunakan QR ini untuk verifikasi merch Anda.</p>
        </div>
    </div>
</body>
</html>
