@extends('layouts.app')

@section('title', 'Konfirmasi Pembayaran')

@section('content')
<div class="container-fluid py-5" style="max-width: 700px; margin:auto; padding-left:12px; padding-right:12px;">
  <div class="card shadow-lg border-0 rounded-3 fade-in">
    
    {{-- Header --}}
    <div class="card-header text-center">
      <h2 class="text-white fs-2 mb-0 fw-bold">Konfirmasi Pembayaran</h2>
    </div>

    <div class="card-body p-4">
      {{-- Info Transaksi --}}
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="p-3 rounded bg-card">
            <span class="fw-semibold">
              <i class="bi bi-envelope me-1"></i> Email:
            </span><br>
            {{ $transaction->email ?? '-' }}
          </div>
        </div>
        <div class="col-md-6">
          <div class="p-3 rounded bg-card">
            <span class="fw-semibold">
              <i class="bi bi-credit-card me-1"></i> Status:
            </span><br>
            <span class="{{ $transaction->payment_status == 'paid' ? 'text-success fw-semibold' : 'text-warning fw-semibold' }}">
              {{ ucfirst($transaction->payment_status ?? 'Belum diketahui') }}
            </span>
          </div>
        </div>
        <div class="col-md-6">
          <div class="p-3 rounded bg-card">
            <span class="fw-semibold">
              <i class="bi bi-clock me-1"></i> Waktu Checkout:
            </span><br>
            {{ $transaction->checkout_time ?? '-' }}
          </div>
        </div>
        <div class="col-md-6">
          <div class="p-3 rounded bg-card">
            <span class="fw-semibold">
              <i class="bi bi-upc-scan me-1"></i> ID Transaksi:
            </span><br>
            #{{ $transaction->id ?? '-' }}
          </div>
        </div>
      </div>

      {{-- Daftar Tiket --}}
      <h5 class="fw-bold mb-3">Daftar Tiket</h5>
      @forelse($details as $d)
        <div class="d-flex align-items-center p-3 mb-3 rounded bg-card shadow-sm">
          <div class="btn-orange-circle me-3">
            <i class="bi bi-ticket-perforated"></i>
          </div>
          <div>
            <p class="fw-semibold mb-0">{{ $d->name ?? 'Tanpa Nama' }}</p>
            <small class="text-muted">
              <i class="bi bi-telephone me-1"></i> {{ $d->phone_number ?? '-' }}
            </small>
          </div>
        </div>
      @empty
        <p class="text-muted fst-italic">Tidak ada data tiket ditemukan.</p>
      @endforelse

      {{-- Ringkasan Harga --}}
      <h5 class="fw-bold mt-4 mb-3">Ringkasan Pembayaran</h5>
      <div class="p-3 rounded bg-bg-card">
        <div class="d-flex justify-content-between mb-2">
          <span>Harga per Tiket</span>
          <span>Rp{{ number_format($hargaTiket, 0, ',', '.') }}</span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span>Jumlah Tiket</span>
          <span>{{ count($details) }}</span>
        </div>
        <div class="border-top pt-2 mt-2 d-flex justify-content-between fw-bold text-orange-500">
          <span>Total Bayar</span>
          <span>Rp{{ number_format($totalBayar, 0, ',', '.') }}</span>
        </div>
      </div>

      {{-- Error --}}
      @if(isset($errorMessage))
        <div class="alert alert-danger mt-4 fade-in">
          <strong>Info:</strong> {{ $errorMessage }}
        </div>
      @endif

      {{-- Tombol --}}
      @if($transaction->payment_status == 'unpaid')
      <div class="d-flex justify-content-end gap-2 mt-4">
        <form action="{{ route('ticket.cancel', $transaction->id) }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-secondary rounded-pill px-4">
            Batalkan
          </button>
        </form>
        <form action="{{ route('ticket.pay', $transaction->id) }}" method="POST">
          @csrf
          <button type="submit" class="btn-orange-pill px-4">
            Bayar Sekarang
          </button>
        </form>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection
