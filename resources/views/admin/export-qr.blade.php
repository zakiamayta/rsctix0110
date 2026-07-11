<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tiket Acara</title>
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
    @php
        $ticket = $guest->ticket ?? null;
        $jadwal = $ticket ? $ticket->jadwal : null;
    @endphp

    <div class="ticket">
        {{-- Header --}}
        <div class="ticket-header">
            <img src="{{ public_path('logo.PNG') }}" alt="Logo">
            <h1>{{ $transaction->event->title ?? 'Nama Acara' }}</h1>
        </div>

        {{-- Body --}}
        <div class="ticket-body">
            <div class="details">
                <p class="email">Email Peserta: {{ $guest->email ?? '-' }}</p>
                <hr>
                <p>
                    <strong>Tanggal Acara:</strong>
                    {{ $transaction->event->date ? \Carbon\Carbon::parse($transaction->event->date)->translatedFormat('d F Y') : '-' }}
                </p>
                <hr>

                <p><strong>Nama:</strong> {{ $guest->name ?? '-' }}</p>
                <p><strong>No. HP:</strong> {{ $guest->phone_number ?? '-' }}</p>

                <p>
                    <strong>Jenis Tiket:</strong>
                    {{ $ticket->name ?? '-' }}
                </p>

                <p>
                    <strong>Jadwal:</strong>
                    {{ $jadwal->info ?? '-' }} —
                    {{ $jadwal && $jadwal->tanggal
                        ? \Carbon\Carbon::parse($jadwal->tanggal)->translatedFormat('d F Y H:i')
                        : '-' }}
                </p>
            </div>

            <div class="qrcode">
                <img src="{{ public_path($guest->qr_code ?? ('images/qrcodes/ticket_' . $guest->kode_unik . '.png')) }}" width="200" height="200" alt="QR Code">
            </div>

            <p class="note">
                Tiket Anda untuk <strong>{{ $transaction->event->title ?? 'Acara' }}</strong> sudah terdaftar. <br>
                Simpan tiket ini dan tunjukkan QR Code kepada petugas saat memasuki acara.
            </p>
        </div>

        {{-- Footer --}}
        <div class="ticket-footer">
            Tiket ini sah tanpa tanda tangan maupun cap resmi.<br>
            © {{ date('Y') }} {{ $transaction->event->title ?? 'Event' }} Committee
        </div>
    </div>
</body>
</html>