@extends('layouts.app')

@section('title', 'My Tickets')

@section('content')
<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 py-8 bg-light">

    {{-- Page Header --}}
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark mb-1">
            <i class="bi bi-ticket-perforated-fill text-orange me-2"></i>My Tickets
        </h1>
        <p class="text-muted small">Riwayat & status tiket pembelian Anda</p>
        <div style="width:48px; height:3px; background:linear-gradient(to right,#f97316,#fbbf24); border-radius:4px;"></div>
    </div>

    @forelse($transactions as $trx)

        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">

            {{-- Colored top strip based on status --}}
            <div style="height:4px; background: {{ $trx->payment_status == 'paid' ? 'linear-gradient(to right,#22c55e,#86efac)' : 'linear-gradient(to right,#f97316,#fbbf24)' }};"></div>

            <div class="card-body p-4">

                {{-- HEADER ROW --}}
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">
                            {{ $trx->details[0]->event_title ?? '-' }}
                        </h5>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-calendar-event text-orange me-1"></i>
                            {{ $trx->details[0]->jadwal_info ?? '-' }}
                            &nbsp;|&nbsp;
                            {{ $trx->details[0]->jadwal_tanggal ?? '-' }}
                        </p>
                    </div>

                    <span class="badge rounded-pill px-3 py-2 small fw-semibold
                        {{ $trx->payment_status == 'paid'
                            ? 'bg-success bg-opacity-10 text-success'
                            : 'bg-warning bg-opacity-10 text-warning' }}"
                        style="font-size:0.75rem; border: 1px solid {{ $trx->payment_status == 'paid' ? '#22c55e' : '#fbbf24' }};">
                        <i class="bi {{ $trx->payment_status == 'paid' ? 'bi-check-circle-fill' : 'bi-clock-fill' }} me-1"></i>
                        {{ strtoupper($trx->payment_status) }}
                    </span>
                </div>

                {{-- INFO ROW --}}
                <div class="d-flex flex-wrap gap-3 mb-3 small text-muted">
                    <span>
                        <i class="bi bi-envelope me-1 text-orange"></i>
                        {{ $trx->email }}
                    </span>
                    <span>
                        <i class="bi bi-cash-coin me-1 text-orange"></i>
                        Total: <strong class="text-dark">Rp {{ number_format($trx->total_amount) }}</strong>
                    </span>
                </div>

                {{-- TICKET LIST --}}
                <div class="border-top pt-3 mb-3">
                    @foreach($trx->details as $item)
                        <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <p class="fw-semibold text-dark mb-0 small">{{ $item->name }}</p>
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-ticket me-1 text-orange"></i>
                                    {{ $item->ticket_name }}
                                </p>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </div>
                    @endforeach
                </div>

                {{-- ACTION BUTTON --}}
                <div class="d-flex justify-content-end">
                    @if($trx->payment_status == 'unpaid')
                        <a href="{{ route('ticket.payment', $trx->id) }}"
                           class="btn btn-orange-pill px-4">
                            <i class="bi bi-credit-card me-1"></i> Bayar Sekarang
                        </a>
                    @else
                        <!-- <a href="{{ route('ticket.success', $trx->id) }}"
                           class="btn btn-outline-orange px-4">
                            <i class="bi bi-eye me-1"></i> Lihat Tiket
                        </a> -->
                    @endif
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