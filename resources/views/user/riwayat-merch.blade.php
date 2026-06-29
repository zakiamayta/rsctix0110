@extends('layouts.app')

@section('title', 'My Merchandise')

@section('content')

<style>
.btn-warning{
    background: linear-gradient(135deg, #ff8a00, #ff6a00);
    border: none;
}

.btn-warning:hover{
    background: linear-gradient(135deg, #ff7a00, #ff5a00);
}

.btn-white{
    background: #fff;
}
</style>

<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 py-8 bg-light">

{{-- HEADER --}}
<div class="mb-4">

    <h1 class="h3 fw-bold text-dark mb-1">
        <i class="bi bi-receipt-cutoff text-orange me-2"></i>
        Riwayat Pesanan
    </h1>

    <p class="text-muted small">
        Riwayat tiket & merchandise Anda
    </p>

    <div style="
        width:48px;
        height:3px;
        background:linear-gradient(to right,#f97316,#fbbf24);
        border-radius:4px;
    "></div>

</div>

{{-- TAB BUTTON --}}
<div class="d-flex gap-3 mb-4">

    {{-- TIKET --}}
    <a href="{{ route('user.tickets') }}"
       class="btn rounded-pill px-4 py-2 fw-semibold shadow-sm
       {{ request()->routeIs('user.tickets')
            ? 'btn-warning text-white border-0'
            : 'btn-white border text-dark' }}">

        <i class="bi bi-ticket-perforated me-1"></i>
        Tiket
    </a>

    {{-- MERCH --}}
    <a href="{{ route('user.merch') }}"
       class="btn rounded-pill px-4 py-2 fw-semibold shadow-sm
       {{ request()->routeIs('user.merch')
            ? 'btn-warning text-white border-0'
            : 'btn-white border text-dark' }}">

        <i class="bi bi-bag me-1"></i>
        Merchandise
    </a>

</div>

@forelse($transactions as $trx)

@php
    $totalQty = $trx->details->sum('quantity');
    
    // Tarik info Event & Status Klaim Lapangan
    $firstDetail = $trx->details->first();
    $relatedEvent = $firstDetail && $firstDetail->product ? $firstDetail->product->event : null;
    
    // Status pengambilan (sesuai data loop dari controller)
    $currentKlaimStatus = $trx->klaim_status ?? 'belum_diambil';
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">

    {{-- TOP BAR --}}
    <div style="
        height:5px;
        background:
        {{ $trx->payment_status == 'paid'
            ? 'linear-gradient(to right,#22c55e,#86efac)'
            : 'linear-gradient(to right,#f97316,#fbbf24)' }};
    "></div>

    <div class="card-body p-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

            <div>

                <h4 class="fw-bold mb-1">
                    Merchandise Order
                </h4>

                <p class="text-muted mb-2 small">
                    {{ $trx->kode_unik }}
                </p>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge rounded-pill px-3 py-2
                        {{ $trx->payment_status == 'paid'
                            ? 'bg-success bg-opacity-10 text-success'
                            : 'bg-warning bg-opacity-10 text-warning' }}">
                        {{ strtoupper($trx->payment_status) }}
                    </span>

                    {{-- INDIKATOR STATUS EVENT DI CARD UTAMA --}}
                    @if($relatedEvent && $relatedEvent->status === 'cancelled')
                        <span class="badge rounded-pill px-3 py-2 bg-danger bg-opacity-10 text-danger small">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> EVENT CANCEL
                        </span>
                    @endif
                </div>

            </div>

            <div class="text-end">

                <small class="text-muted d-block">
                    Tanggal
                </small>

                <strong>
                    {{ \Carbon\Carbon::parse($trx->created_at)->translatedFormat('d F Y, H:i') }}
                </strong>

            </div>

        </div>

        {{-- INFO --}}
        <div class="row mt-4">

            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    Jumlah Item
                </small>

                <strong>
                    {{ $totalQty }} item
                </strong>

            </div>

            <div class="col-md-4 mb-3">

                <small class="text-muted d-block">
                    Total Pembayaran
                </small>

                <strong class="text-orange">
                    Rp {{ number_format($trx->grand_total ?? $trx->total_amount,0,',','.') }}
                </strong>

            </div>

            <div class="col-md-4 mb-3 text-md-end">

                <button
                    class="btn btn-orange-pill px-4"
                    data-bs-toggle="modal"
                    data-bs-target="#detailModal{{ $trx->id }}"
                >

                    <i class="bi bi-eye me-1"></i>
                    Detail

                </button>

            </div>

        </div>

    </div>
</div>

{{-- MODAL DETAIL --}}
<div class="modal fade"
     id="detailModal{{ $trx->id }}"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content border-0 rounded-4">

            {{-- HEADER --}}
            <div class="modal-header border-0 pb-0">

                <div>

                    <h3 class="fw-bold mb-1">
                        Merchandise Order
                    </h3>

                    <p class="text-muted mb-0">
                        {{ $trx->kode_unik }}
                    </p>

                </div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>

            </div>

            <div class="modal-body p-4">

                {{-- NOTIFIKASI POLA PENYELESAIAN JIKA EVENT DIBATALKAN --}}
                @if($relatedEvent && $relatedEvent->status === 'cancelled')
                    <div class="alert alert-danger rounded-4 border-0 mb-4 p-3 small text-dark d-flex gap-2">
                        <i class="bi bi-info-circle-fill text-danger h5 mb-0 mt-0.5"></i>
                        <div>
                            <strong>Informasi Pembatalan Event:</strong>
                            <p class="mb-0 mt-1">Event terkait pesanan ini telah dibatalkan.</p>
                            {{-- Kondisional logis pilihan kebijakan dari Event Organizer --}}
                            @if(isset($relatedEvent->merch_policy) && $relatedEvent->merch_policy === 'ship')
                                <p class="mb-0 text-muted small">Kebijakan EO: <strong>Merchandise tetap dikirim/diproduksi</strong> ke alamat pemesan. Silakan pantau pengiriman berkala.</p>
                            @else
                                <p class="mb-0 text-muted small">Kebijakan EO: Pembeli dipersilakan melakukan koordinasi/pengajuan refund sesuai instruksi panitia pelaksana resmi.</p>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- QR CODE & STATUS AMBIL BARANG (HANYA MUNCUL JIKA SUDAH LUNAS) --}}
                @if($trx->payment_status == 'paid')
                    <div class="p-4 rounded-4 mb-4 bg-light text-center border">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-qr-code-scan text-orange me-1"></i> Kode Batang Pengambilan
                        </h6>
                        
                        <div class="bg-white p-3 d-inline-block rounded-3 shadow-sm mb-3">
                            {{-- Membaca aset qr_code hasil webhook secara dinamis --}}
                            @if($trx->qr_code && file_exists(base_path($trx->qr_code)))
                                <img src="{{ asset($trx->qr_code) }}" alt="QR Code" class="img-fluid" style="max-width: 160px;">
                            @else
                                {{-- Fallback jika file fisik QR belum tergenerate di server --}}
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ route('guests.merch.qr', ['kode_unik' => $trx->kode_unik]) }}" alt="QR Code" class="img-fluid" style="max-width: 160px;">
                            @endif
                        </div>

                        <p class="small text-muted mb-2">Tunjukkan QR di atas ke panitia penyerahan merchandise di lokasi event</p>
                        
                        <div>
                            @if($currentKlaimStatus === 'sudah_diambil')
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-4 py-2 fw-bold small shadow-sm">
                                    <i class="bi bi-bag-check-fill me-1"></i> BARANG SUDAH DIAMBIL
                                </span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-4 py-2 fw-bold small shadow-sm">
                                    <i class="bi bi-hourglass-split me-1"></i> BELUM DIAMBIL
                                </span>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- STATUS PEMBAYARAN ORIGINAL --}}
                <div class="p-3 rounded-4 mb-4
                    {{ $trx->payment_status == 'paid'
                        ? 'bg-success bg-opacity-10'
                        : 'bg-warning bg-opacity-10' }}">

                    <div class="d-flex align-items-center gap-2">

                        <i class="bi
                            {{ $trx->payment_status == 'paid'
                                ? 'bi-check-circle-fill text-success'
                                : 'bi-clock-fill text-warning' }}">
                        </i>

                        <strong>
                            {{ strtoupper($trx->payment_status) }}
                        </strong>

                    </div>

                </div>

                {{-- INFO --}}
                <div class="row g-3 mb-4">

                    <div class="col-md-6">

                        <div class="p-3 rounded bg-light">

                            <small class="text-muted d-block">
                                Email
                            </small>

                            <strong>
                                {{ $trx->email }}
                            </strong>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="p-3 rounded bg-light">

                            <small class="text-muted d-block">
                                Tanggal
                            </small>

                            <strong>
                                {{ \Carbon\Carbon::parse($trx->created_at)->translatedFormat('d F Y, H:i') }}
                            </strong>

                        </div>

                    </div>

                </div>

                {{-- DETAIL ITEM --}}
                <h5 class="fw-bold mb-3">
                    Detail Merchandise
                </h5>

                @foreach($trx->details as $item)

                <div class="border rounded-4 p-3 mb-3">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <h6 class="fw-bold mb-1">
                                {{ $item->product->name ?? '-' }}
                            </h6>

                            <small class="text-muted d-block">
                                Varian:
                                {{ $item->varian->varian ?? '-' }}
                            </small>

                            <small class="text-muted d-block">
                                Ukuran:
                                {{ $item->ukuran->ukuran ?? '-' }}
                            </small>

                            <small class="text-muted">
                                Qty:
                                {{ $item->quantity }}
                            </small>

                        </div>

                        <div class="text-end">

                            <strong>
                                Rp {{ number_format($item->subtotal,0,',','.') }}
                            </strong>

                        </div>

                    </div>

                </div>

                @endforeach

                {{-- RINGKASAN --}}
                <h5 class="fw-bold mt-4 mb-3">
                    Ringkasan Pembayaran
                </h5>

                <div class="p-3 rounded-4 bg-light">

                    <div class="d-flex justify-content-between mb-2">

                        <span>Total Item</span>

                        <span>
                            {{ $totalQty }} item
                        </span>

                    </div>

                    <div class="d-flex justify-content-between mb-2">

                        <span>Subtotal</span>

                        <span>
                            Rp {{ number_format($trx->total_amount,0,',','.') }}
                        </span>

                    </div>

                    <div class="d-flex justify-content-between mb-2">

                        <span>Service Tax</span>

                        <span>
                            Rp {{ number_format($trx->service_tax ?? 0,0,',','.') }}
                        </span>

                    </div>

                    <hr>

                    <div class="d-flex justify-content-between fw-bold text-orange">

                        <span>Grand Total</span>

                        <span>
                            Rp {{ number_format($trx->grand_total ?? $trx->total_amount,0,',','.') }}
                        </span>

                    </div>

                </div>

                {{-- BUTTON --}}
                @if($trx->payment_status == 'unpaid')

                <div class="mt-4 text-end">

                    <a href="{{ $trx->xendit_invoice_url }}"
                       target="_blank"
                       class="btn btn-orange-pill px-4">

                        <i class="bi bi-credit-card me-1"></i>
                        Bayar Sekarang

                    </a>

                </div>

                @endif

            </div>
        </div>
    </div>
</div>

@empty

<div class="card border-0 shadow-sm rounded-4 bg-white text-center py-5">

    <div class="card-body">

        <i class="bi bi-bag text-orange"
           style="font-size:48px; opacity:0.4;"></i>

        <p class="text-muted mt-3 mb-0">
            Belum ada pembelian merchandise.
        </p>

        <a href="{{ route('merch.index') }}"
           class="btn btn-orange-pill mt-3 px-4">

            <i class="bi bi-shop me-1"></i>
            Belanja Merchandise

        </a>

    </div>
</div>

@endforelse

</div>

@endsection