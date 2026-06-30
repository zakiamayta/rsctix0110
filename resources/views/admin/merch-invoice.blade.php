<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice Merchandise</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 30px;
        }
        .ticket {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            max-width: 540px;
            margin: auto;
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
            border: 1px solid #e5e7eb;
        }

        /* Header */
        .ticket-header {
            background: #1e3a8a;
            color: #ffffff;
            padding: 28px;
            text-align: center;
        }
        .ticket-header img {
            height: 50px;
            width: auto;
            margin-bottom: 10px;
        }
        .ticket-header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Body */
        .ticket-body {
            padding: 28px;
            text-align: center;
        }
        .details {
            margin: 18px 0;
            font-size: 15px;
            color: #333;
            text-align: left;
            border: 1px dashed #ccc;
            border-radius: 10px;
            padding: 15px 18px;
            background: #fafafa;
        }
        .details p {
            margin: 6px 0;
        }
        .details strong {
            color: #111;
        }
        .details hr {
            border: 0;
            border-top: 1px dashed #ccc;
            margin: 10px 0;
        }
        .email {
            font-weight: bold;
            color: #1e3a8a;
            font-size: 15px;
        }

        /* QR Code */
        .qrcode {
            margin: 25px 0;
            padding: 16px;
            border: 2px dashed #1e3a8a;
            border-radius: 12px;
            display: inline-block;
            background: #f9fafb;
        }

        /* Note */
        .note {
            font-size: 14px;
            color: #444;
            margin-top: 10px;
            line-height: 1.6;
        }
        .note strong {
            color: #1e3a8a;
        }

        /* Footer */
        .ticket-footer {
            background: #f3f4f6;
            padding: 16px;
            font-size: 13px;
            color: #666;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="ticket">
        {{-- Header --}}
        <div class="ticket-header">
            <img src="{{ public_path('logo.PNG') }}" alt="Logo">
            <h1>Invoice Merchandise</h1>
        </div>

        {{-- Body --}}
        <div class="ticket-body">
            <div class="details">
                <p class="email">Email Transaksi: {{ $transaction->email ?? '-' }}</p>
                <hr>
                <p><strong>Kode Unik:</strong> {{ $transaction->kode_unik ?? '-' }}</p>
                <p>
                    <strong>Status Pembayaran:</strong>
                    {{ ucfirst($transaction->payment_status ?? '-') }}
                </p>
                <hr>

                @foreach($transaction->details as $detail)

                    @php
                        $varian = $detail->varian ?? null;
                        $ukuran = $detail->ukuran ?? null;
                    @endphp

                    <p><strong>Nama Pembeli:</strong> {{ $detail->buyer_name ?? '-' }}</p>
                    <p><strong>No. HP:</strong> {{ $detail->buyer_phone ?? '-' }}</p>

                    <p>
                        <strong>Produk:</strong>
                        {{ $detail->product->name ?? '-' }}
                        @if($varian) - {{ $varian->varian }} @endif
                    </p>

                    <p>
                        <strong>Ukuran:</strong> {{ $ukuran->ukuran ?? '-' }} —
                        <strong>Jumlah:</strong> x{{ $detail->quantity ?? 0 }}
                    </p>

                    <p>
                        <strong>Subtotal:</strong>
                        Rp {{ number_format($detail->subtotal ?? 0, 0, ',', '.') }}
                    </p>

                    @if(!$loop->last)
                        <hr>
                    @endif
                @endforeach

                <hr>
                <p>
                    <strong>Total Bayar:</strong>
                    Rp {{ number_format($transaction->grand_total ?? 0, 0, ',', '.') }}
                </p>
            </div>

            <div class="qrcode">
                <img src="{{ public_path('images/qrcodes_merch/merch_' . $transaction->kode_unik . '.png') }}" width="200" height="200" alt="QR Code">
            </div>

            <p class="note">
                Merchandise Anda sudah terdaftar. <br>
                Simpan invoice ini dan tunjukkan QR Code kepada petugas saat pengambilan barang.
            </p>
        </div>

        {{-- Footer --}}
        <div class="ticket-footer">
            Invoice ini sah tanpa tanda tangan maupun cap resmi.<br>
            © {{ date('Y') }} Merchandise Committee
        </div>
    </div>
</body>
</html>