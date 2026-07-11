@extends('layouts.app')

@section('title', 'My Merchandise')

@section('content')

<style>
.btn-warning {
    background: linear-gradient(135deg, #ff8a00, #ff6a00);
    border: none;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #ff7a00, #ff5a00);
}

.btn-white {
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
    <div style="width:48px; height:3px; background:linear-gradient(to right,#f97316,#fbbf24); border-radius:4px;"></div>
</div>

{{-- TAB BUTTON --}}
<div class="d-flex gap-3 mb-4">
    <a href="{{ route('user.tickets') }}" class="btn rounded-pill px-4 py-2 fw-semibold shadow-sm {{ request()->routeIs('user.tickets') ? 'btn-warning text-white border-0' : 'btn-white border text-dark' }}">
        <i class="bi bi-ticket-perforated me-1"></i> Tiket
    </a>
    <a href="{{ route('user.merch') }}" class="btn rounded-pill px-4 py-2 fw-semibold shadow-sm {{ request()->routeIs('user.merch') ? 'btn-warning text-white border-0' : 'btn-white border text-dark' }}">
        <i class="bi bi-bag me-1"></i> Merchandise
    </a>
</div>

{{-- NOTIFIKASI SUKSES (ALERT SUCCESS SAMA DENGAN TEMA TIKET) --}}
@if(session('success'))
    <div class="alert alert-success border-0 rounded-4 shadow-sm p-3 mb-4 d-flex align-items-center gap-2 small text-dark">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <div>
            {{ session('success') }}
        </div>
    </div>
@endif

@forelse($transactions as $trx)

@php
    $totalQty = $trx->details->sum('quantity');
    
    // Hubungkan relasi Event secara berantai: detail -> product -> event
    $firstDetail = $trx->details->first();
    $relatedEvent = $firstDetail && $firstDetail->product ? $firstDetail->product->event : null;
    
    $currentKlaimStatus = $trx->klaim_status ?? 'belum_diambil';
    $currentRefundStatus = $trx->refund_status ?? null; 
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

        {{-- HEADER KARTU --}}
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1">
                    {{ $relatedEvent ? $relatedEvent->title : 'Merchandise Order' }}
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

                    {{-- BADGE EVENT CANCEL --}}
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

        {{-- INFO UTAMA KARTU --}}
        <div class="row mt-4 align-items-center">
            <div class="col-md-4 mb-3 mb-md-0">
                <small class="text-muted d-block">
                    Jumlah Item
                </small>
                <strong>
                    {{ $totalQty }} item
                </strong>
            </div>

            <div class="col-md-4 mb-3 mb-md-0">
                <small class="text-muted d-block">
                    Total Pembayaran
                </small>
                <strong class="text-orange">
                    Rp {{ number_format($trx->grand_total ?? $trx->total_amount,0,',','.') }}
                </strong>
            </div>

            {{-- BLOK ACTION TOMBOL REFUND & DETAIL DIJAJAR DI SINI SEJAJAR TIKET --}}
            <div class="col-md-4 text-md-end d-flex flex-wrap gap-2 justify-content-md-end">
                
                @if($trx->payment_status == 'paid' && $relatedEvent && $relatedEvent->status === 'cancelled')
                    
                    {{-- 1. Opsi Refund --}}
                    @if($relatedEvent->merch_cancel_decision === 'refund')
                        @if(empty($currentRefundStatus))
                            <a href="{{ route('user.merch-refund.create', $trx->id) }}" class="btn btn-warning rounded-pill px-4 text-white fw-semibold">
                                <i class="bi bi-exclamation-circle me-1"></i> Ajukan Refund
                            </a>
                        @elseif($currentRefundStatus === 'waiting' || $currentRefundStatus === 'pending')
                        <span class="badge bg-info text-dark rounded-pill px-3 py-2 small" title="Menunggu pembukaan batch administrasi berikutnya oleh admin utama">
                            <i class="bi bi-clock-history"></i> Antrean Berkas (Waiting)
                        </span>
                        @elseif($currentRefundStatus === 'pending')
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2 small">
                            <i class="bi bi-hourglass-split"></i> Refund Diproses Admin
                        </span>
                        @elseif($currentRefundStatus === 'refunded')
                        <div class="text-md-end me-2">
                            <span class="badge bg-success rounded-pill px-3 py-2 small mb-1">
                                <i class="bi bi-check-all"></i> Refund Berhasil
                            </span>
                        </div>
                        @endif
                    
                    {{-- 2. Opsi Pengiriman Mandiri --}}
                    @elseif($relatedEvent->merch_cancel_decision === 'ship' || $relatedEvent->merch_cancel_decision === 'ship_independently')
                        <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill fw-medium align-self-center small">
                            <i class="bi bi-truck me-1"></i> Tetap Dikirim EO
                        </span>
                    @endif

                @endif

                <button
                    class="btn btn-orange-pill px-4"
                    data-bs-toggle="modal"
                    data-bs-target="#detailModal{{ $trx->id }}"
                >
                    <i class="bi bi-eye me-1"></i> Detail
                </button>
            </div>
        </div>

    </div>
</div>

{{-- MODAL DETAIL (MENGIKUTI KONSEP TEMA RIWAYAT TIKET ANDA) --}}
<div class="modal fade" id="detailModal{{ $trx->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4">

            {{-- HEADER MODAL --}}
            <div class="modal-header border-0 pb-0">
                <div>
                    <h3 class="fw-bold mb-1">
                        {{ $relatedEvent ? $relatedEvent->title : 'Merchandise Order' }}
                    </h3>
                    <p class="text-muted mb-0">
                        {{ $trx->kode_unik }}
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">

                {{-- ALERT EVENT BATAL DI DALAM MODAL --}}
                @if($relatedEvent && $relatedEvent->status === 'cancelled')
                    <div class="alert alert-danger rounded-4 border-0 mb-4 p-3 small text-dark d-flex gap-2">
                        <i class="bi bi-info-circle-fill text-danger h5 mb-0 mt-0.5"></i>
                        <div>
                            <strong>Pemberitahuan Pembatalan:</strong>
                            <p class="mb-0 mt-1">Event ini resmi dibatalkan.</p>
                            @if($relatedEvent->merch_cancel_decision === 'refund')
                                <p class="mb-0 text-muted small">Kebijakan: Pengembalian dana penuh telah dibuka pada halaman riwayat.</p>
                            @elseif($relatedEvent->merch_cancel_decision === 'ship' || $relatedEvent->merch_cancel_decision === 'ship_independently')
                                <p class="mb-0 text-muted small">Kebijakan: Merchandise ini akan tetap diproduksi oleh EO dan dikirim ke alamat terdaftar Anda.</p>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- QR CODE & STATUS AMBIL BARANG --}}
                @if($trx->payment_status == 'paid')
                    <div class="p-4 rounded-4 mb-4 bg-light text-center border">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-qr-code-scan text-orange me-1"></i> Kode Batang Pengambilan
                        </h6>
                        
                        <div class="bg-white p-3 d-inline-block rounded-3 shadow-sm mb-3">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ route('guests.merch.qr', ['kode_unik' => $trx->kode_unik]) }}" alt="QR Code" class="img-fluid" style="max-width: 160px;">
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

                {{-- DETAIL ITEM (MENGIKUTI STRUKTUR SUSUNAN GRID SEPERTI DI FOTO TIKET ANDA) --}}
                <h5 class="fw-bold mb-3">Detail Produk</h5>

                @foreach($trx->details as $item)
                <div class="p-3 bg-light rounded-4 mb-3 border border-light">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-5 mb-2 mb-md-0">
                            <small class="text-muted d-block uppercase tracking-wider small mb-1">Nama Barang</small>
                            <h6 class="fw-bold text-dark mb-0">
                                {{ $item->product->name ?? '-' }}
                            </h6>
                        </div>
                        <div class="col-6 col-md-4">
                            <small class="text-muted d-block uppercase tracking-wider small mb-1">Pilihan Varian</small>
                            <span class="small text-dark fw-medium">
                                Varian: {{ $item->varian->varian ?? '-' }} <br>
                                Ukuran: {{ $item->ukuran->ukuran ?? '-' }}
                            </span>
                        </div>
                        <div class="col-6 col-md-3 text-end">
                            <small class="text-muted d-block uppercase tracking-wider small mb-1">Qty & Subtotal</small>
                            <span class="text-muted small d-block">{{ $item->quantity }}x Item</span>
                            <strong class="text-dark">
                                Rp {{ number_format($item->subtotal,0,',','.') }}
                            </strong>
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- REKAP NOMINAL PEMBAYARAN (MENGIKUTI STRUKTUR TOTALAN TIKET) --}}
                <h5 class="fw-bold mt-4 mb-3">Ringkasan Pembayaran</h5>

                <div class="p-3 rounded-4 bg-light small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal Belanja</span>
                        <span class="text-dark fw-medium">Rp {{ number_format($trx->total_amount,0,',','.') }}</span>
                    </div>
                    
                    {{-- Menampilkan Service Tax / Biaya Layanan jika ada nilainya --}}
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Biaya Layanan (Service Tax)</span>
                        <span class="text-dark fw-medium">Rp {{ number_format($trx->service_tax ?? 0,0,',','.') }}</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between fw-bold text-orange fs-6">
                        <span>Grand Total</span>
                        <span>Rp {{ number_format($trx->grand_total ?? $trx->total_amount,0,',','.') }}</span>
                    </div>
                </div>

                {{-- BUTTON INVOICE JIKA UNPAID --}}
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
        <i class="bi bi-bag text-orange" style="font-size:48px; opacity:0.4;"></i>
        <p class="text-muted mt-3 mb-0">
            Belum ada pembelian merchandise.
        </p>
    </div>
</div>

@endforelse

</div>

@endsection