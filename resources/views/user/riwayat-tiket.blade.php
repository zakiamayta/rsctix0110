@extends('layouts.app')

@section('title', 'My Tickets')

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

    {{-- NOTIFIKASI ALERTS --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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

    <div class="d-flex gap-3 mb-4">
        {{-- TIKET --}}
        <a href="{{ route('user.tickets') }}"
           class="btn rounded-pill px-4 py-2 fw-semibold shadow-sm
           {{ request()->routeIs('user.tickets') ? 'btn-warning text-white border-0' : 'btn-white border text-dark' }}">
            <i class="bi bi-ticket-perforated me-1"></i>
            Tiket
        </a>

        {{-- MERCH --}}
        <a href="{{ route('user.merch') }}"
           class="btn rounded-pill px-4 py-2 fw-semibold shadow-sm
           {{ request()->routeIs('user.merch') ? 'btn-warning text-white border-0' : 'btn-white border text-dark' }}">
            <i class="bi bi-bag me-1"></i>
            Merchandise
        </a>
    </div>

    @forelse($transactions as $trx)

    @php
        $totalQty = $trx->details->count();
        // Membaca status refund secara aman dari data transaksi (baik berupa string langsung atau relasi objek)
        $currentRefundStatus = $trx->refund_status ?? ($trx->refund->status ?? null);
    @endphp

    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        {{-- TOP BAR --}}
        <div style="height:5px; background: {{ $trx->payment_status == 'paid' ? 'linear-gradient(to right,#22c55e,#86efac)' : 'linear-gradient(to right,#f97316,#fbbf24)' }};"></div>

        <div class="card-body p-4">
            {{-- HEADER CARD --}}
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold mb-1">
                        {{ $trx->details[0]->event_title ?? '-' }}
                    </h4>
                    <p class="text-muted mb-2 small">
                        {{ $trx->kode_unik }}
                    </p>
                    <span class="badge rounded-pill px-3 py-2
                        {{ $trx->payment_status == 'paid' ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning' }}">
                        {{ strtoupper($trx->payment_status) }}
                    </span>
                </div>

                <div class="text-end">
                    <small class="text-muted d-block">Tanggal</small>
                    <strong>
                        {{ \Carbon\Carbon::parse($trx->checkout_time)->translatedFormat('d F Y, H:i') }}
                    </strong>
                </div>
            </div>

            {{-- INFO KONTEN TRX --}}
            <div class="row mt-4">
                <div class="col-md-4 mb-3">
                    <small class="text-muted d-block">Jumlah Tiket</small>
                    <strong>{{ $totalQty }} item</strong>
                </div>

                <div class="col-md-4 mb-3">
                    <small class="text-muted d-block">Total Pembayaran</small>
                    <strong class="text-orange">
                        Rp {{ number_format($trx->grand_total ?? $trx->total_amount,0,',','.') }}
                    </strong>
                </div>

                <div class="col-md-4 mb-3 text-md-end d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                    
                    {{-- TOMBOL LOGIKA REFUND DI CARD UTAMA --}}
                    @if($trx->payment_status === 'refunded' || $currentRefundStatus === 'refunded' || $currentRefundStatus === 'paid')
                        <div class="text-md-end me-2">
                            <span class="badge bg-success rounded-pill px-3 py-2 small mb-1">
                                <i class="bi bi-check-all"></i> Refund Berhasil
                            </span>
                        </div>
                    @elseif($currentRefundStatus === 'waiting')
                        <span class="badge bg-info text-dark rounded-pill px-3 py-2 small" title="Menunggu pembukaan batch administrasi berikutnya oleh admin utama">
                            <i class="bi bi-clock-history"></i> Antrean Berkas (Waiting)
                        </span>
                    @elseif($currentRefundStatus === 'pending')
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2 small">
                            <i class="bi bi-hourglass-split"></i> Refund Diproses Admin
                        </span>
                    @elseif($currentRefundStatus === 'rejected')
                        <span class="badge bg-danger rounded-pill px-3 py-2 small">
                            <i class="bi bi-x-circle"></i> Refund Ditolak
                        </span>
                    @elseif($trx->payment_status === 'paid' && ($trx->event_status === 'cancelled' || $trx->event_is_rescheduled > 0))
                        <a href="{{ route('buyer.refund.create', $trx->id) }}" class="btn btn-warning rounded-pill px-3 text-white fw-semibold">
                            <i class="bi bi-exclamation-circle me-1"></i> Ajukan Refund
                        </a>
                    @endif

                    <button class="btn btn-orange-pill px-4" data-bs-toggle="modal" data-bs-target="#detailModal{{ $trx->id }}">
                        <i class="bi bi-eye me-1"></i> Detail
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DETAIL --}}
    <div class="modal fade" id="detailModal{{ $trx->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4">
                
                {{-- MODAL HEADER --}}
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h3 class="fw-bold mb-1">{{ $trx->details[0]->event_title ?? '-' }}</h3>
                        <p class="text-muted mb-0">{{ $trx->kode_unik }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    {{-- STATUS EVENT DI DALAM MODAL JIKA CANCELLED/RESCHEDULED --}}
                    @if($trx->event_status === 'cancelled')
                        <div class="p-3 rounded-4 mb-3 bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20 small">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Event ini telah dibatalkan oleh pihak penyelenggara. Anda berhak mengajukan pengembalian dana penuh sesuai ketentuan tiket murni.
                        </div>
                    @elseif($trx->event_is_rescheduled > 0)
                        <div class="p-3 rounded-4 mb-3 bg-info bg-opacity-10 text-info border border-info border-opacity-20 small">
                            <i class="bi bi-info-circle-fill me-2"></i> Penyelenggara telah melakukan penjadwalan ulang pada event ini. Anda berhak melakukan pengajuan refund apabila berhalangan hadir.
                        </div>
                    @endif

                    {{-- BADGE STATUS PEMBAYARAN UTAMA --}}
                    <div class="p-3 rounded-4 mb-4 {{ $trx->payment_status == 'paid' ? 'bg-success bg-opacity-10' : 'bg-warning bg-opacity-10' }}">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi {{ $trx->payment_status == 'paid' ? 'bi-check-circle-fill text-success' : 'bi-clock-fill text-warning' }}"></i>
                            <strong>{{ strtoupper($trx->payment_status) }}</strong>
                        </div>
                    </div>

                    {{-- METADATA TRANSAKSI --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded bg-light">
                                <small class="text-muted d-block">Tanggal Transaksi</small>
                                <strong>{{ \Carbon\Carbon::parse($trx->checkout_time)->translatedFormat('d F Y, H:i') }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded bg-light">
                                <small class="text-muted d-block">Email Pembeli</small>
                                <strong>{{ $trx->email }}</strong>
                            </div>
                        </div>
                    </div>

                    {{-- DETAIL ITEMS TIKET --}}
                    <h5 class="fw-bold mb-3">Detail Tiket</h5>
                    @foreach($trx->details as $item)
                    <div class="border rounded-4 p-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-1">{{ $item->ticket_name }}</h6>
                                <small class="text-muted d-block">Pemegang: {{ $item->name }}</small>
                                <small class="text-muted">
                                    {{ $item->jadwal_info }} ({{ \Carbon\Carbon::parse($item->jadwal_tanggal)->translatedFormat('d F Y') }})
                                </small>
                            </div>
                            <div class="text-end">
                                <strong>Rp {{ number_format($item->price,0,',','.') }}</strong>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    {{-- RINGKASAN STRUKTURAL NOMINAL --}}
                    <h5 class="fw-bold mt-4 mb-3">Ringkasan Pembayaran</h5>
                    <div class="p-3 rounded-4 bg-light">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Tiket</span>
                            <span>{{ $totalQty }} item</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>Rp {{ number_format($trx->total_amount,0,',','.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Service Tax</span>
                            <span>Rp {{ number_format($trx->service_tax ?? 0,0,',','.') }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold text-orange">
                            <span>Grand Total</span>
                            <span>Rp {{ number_format($trx->grand_total ?? $trx->total_amount,0,',','.') }}</span>
                        </div>
                    </div>

                    {{-- AKSESIBILITAS INTEGRAL REFUND & BAYAR DI DALAM MODAL --}}
                    <div class="mt-4 text-end d-flex justify-content-end gap-2 align-items-center flex-wrap">
                        @if($trx->payment_status == 'unpaid')
                            <a href="{{ route('ticket.payment', $trx->id) }}" class="btn btn-orange-pill px-4">
                                <i class="bi bi-credit-card me-1"></i> Bayar Sekarang
                            </a>
                        @endif

                        @if($trx->payment_status === 'refunded' || $currentRefundStatus === 'refunded' || $currentRefundStatus === 'paid')
                            <div class="text-end me-2">
                                <span class="text-success d-block fw-bold small mb-1"><i class="bi bi-check-circle-fill"></i> Refund Berhasil</span>
                                <span class="text-muted d-block small" style="font-size: 0.8rem;">Dana murni tiket telah ditransfer balik. Silakan cek email dan mutasi rekening Anda.</span>
                            </div>
                            <button class="btn btn-success rounded-pill px-4" disabled>
                                <i class="bi bi-check-all"></i> Selesai
                            </button>
                        @elseif($currentRefundStatus === 'waiting')
                            <div class="text-end me-2">
                                <span class="text-info d-block fw-bold small mb-1">⏳ Antrean Berkas Diterima</span>
                                <span class="text-muted d-block small" style="font-size: 0.75rem;">Berkas aman dalam sistem. Menunggu pembukaan batch berkas resmi oleh Admin Utama.</span>
                            </div>
                            <button class="btn btn-info text-dark rounded-pill px-4" disabled>
                                <i class="bi bi-clock-history"></i> Waiting List
                            </button>
                        @elseif($currentRefundStatus === 'pending')
                            <button class="btn btn-secondary rounded-pill px-4" disabled>
                                <i class="bi bi-hourglass-split"></i> Status: Masuk Antrean Batch (Diproses Admin)
                            </button>
                        @elseif($currentRefundStatus === 'rejected')
                            <button class="btn btn-danger rounded-pill px-4" disabled>
                                <i class="bi bi-x-circle"></i> Status: Pengajuan Refund Ditolak
                            </button>
                        @elseif($trx->payment_status === 'paid' && ($trx->event_status === 'cancelled' || $trx->event_is_rescheduled > 0))
                            <a href="{{ route('buyer.refund.create', $trx->id) }}" class="btn btn-warning rounded-pill px-4 text-white fw-semibold">
                                <i class="bi bi-exclamation-circle me-1"></i> Ajukan Pengembalian Dana (Refund)
                            </a>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

    @empty
    <div class="card border-0 shadow-sm rounded-4 bg-white text-center py-5">
        <div class="card-body">
            <i class="bi bi-ticket-perforated text-orange" style="font-size:48px; opacity:0.4;"></i>
            <p class="text-muted mt-3 mb-0">Belum ada tiket yang dibeli.</p>
            <a href="{{ url('/') }}" class="btn btn-orange-pill mt-3 px-4">
                <i class="bi bi-search me-1"></i> Cari Event
            </a>
        </div>
    </div>
    @endforelse

</div>
@endsection