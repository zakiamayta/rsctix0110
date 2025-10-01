@extends('layouts.app')

@section('content')
<div class="container text-center py-5">
    <h3 class="mb-3">QR Code Merchandise</h3>

    <p><strong>Kode Unik Transaksi:</strong> {{ $transaction->kode_unik }}</p>
    <p><strong>Email:</strong> {{ $transaction->email }}</p>
    <p><strong>Status:</strong> 
        <span class="{{ $transaction->payment_status === 'paid' ? 'text-success' : 'text-danger' }}">
            {{ strtoupper($transaction->payment_status) }}
        </span>
    </p>

    @if($transaction->qr_code)
        <img src="{{ asset($transaction->qr_code) }}" 
             alt="QR Code Merchandise" 
             class="img-fluid my-3" 
             style="max-width: 300px;" />
    @else
        <p class="text-muted">QR Code belum tersedia. Silakan cek kembali setelah pembayaran terverifikasi.</p>
    @endif

    @if($transaction->details && $transaction->details->count())
        <h5 class="mt-4">Detail Merchandise</h5>
        <table class="table table-bordered mt-2">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Jumlah</th>
                    <th>Harga Satuan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->details as $detail)
                    <tr>
                        <td>{{ $detail->product->name ?? 'Produk tidak ditemukan' }}</td>
                        <td>{{ $detail->quantity }}</td>
                        <td>Rp {{ number_format($detail->price, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
