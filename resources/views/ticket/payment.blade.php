@extends('layouts.app')

@section('title', 'Konfirmasi Pembayaran')

@section('content')
<div class="container py-5" style="max-width:700px;">
  <div class="card shadow border-0 rounded-4">

    {{-- Header --}}
    <div class="card-header bg-orange text-white text-center rounded-top-4 py-3">
      <h2 class="fw-bold mb-0">Konfirmasi Pembayaran</h2>
    </div>

    <div class="card-body p-4 bg-white">

      {{-- Info Transaksi --}}
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="p-3 rounded bg-light border">
            <span class="fw-semibold text-muted">
              <i class="bi bi-envelope me-1"></i> Email
            </span><br>
            {{ $transaction->email ?? '-' }}
          </div>
        </div>

        <div class="col-md-6">
          <div class="p-3 rounded bg-light border">
            <span class="fw-semibold text-muted">
              <i class="bi bi-credit-card me-1"></i> Status
            </span><br>
            <span class="{{ $transaction->payment_status == 'paid' ? 'text-success fw-semibold' : 'text-warning fw-semibold' }}">
              {{ ucfirst($transaction->payment_status ?? '-') }}
            </span>
          </div>
        </div>

        <div class="col-md-6">
          <div class="p-3 rounded bg-light border">
            <span class="fw-semibold text-muted">
              <i class="bi bi-clock me-1"></i> Waktu Checkout
            </span><br>
            {{ $transaction->checkout_time ?? '-' }}
          </div>
        </div>

        <div class="col-md-6">
          <div class="p-3 rounded bg-light border">
            <span class="fw-semibold text-muted">
              <i class="bi bi-upc-scan me-1"></i> ID Transaksi
            </span><br>
            #{{ $transaction->id ?? '-' }}
          </div>
        </div>
      </div>

      {{-- Daftar Tiket --}}
      <h5 class="fw-bold mb-3">Daftar Tiket</h5>

      @forelse($details as $d)
        <div class="d-flex align-items-center p-3 mb-3 rounded border bg-white shadow-sm">
          <div class="btn-orange-circle me-3">
            <i class="bi bi-ticket-perforated"></i>
          </div>
          <div>
            <p class="fw-semibold mb-0">
                {{ $d->ticket_name ?? 'Tiket' }} - {{ $d->name ?? 'Tanpa Nama' }}
            </p>

            <small class="text-muted d-block">
                <i class="bi bi-calendar-event me-1"></i>
                {{ $d->jadwal_info ?? '-' }} —
                {{ \Carbon\Carbon::parse($d->jadwal_tanggal)->translatedFormat('d F Y H:i') }}
            </small>
            </p>
            <small class="text-muted">
              <i class="bi bi-telephone me-1"></i> {{ $d->phone_number ?? '-' }}
            </small>
          </div>
        </div>
      @empty
        <p class="text-muted fst-italic">Tidak ada data tiket ditemukan.</p>
      @endforelse

      {{-- Ringkasan Pembayaran --}}
    <h5 class="fw-bold mt-4 mb-3">Ringkasan Pembayaran</h5>
    <div class="p-3 rounded bg-light border">

      @foreach($ticketSummary as $name => $item)
        <div class="d-flex justify-content-between mb-2">
          <span>{{ $name }} (x{{ $item['qty'] }})</span>
          <span>Rp{{ number_format($item['total'], 0, ',', '.') }}</span>
        </div>

        <div class="d-flex justify-content-between mb-2 text-muted small">
          <span>Harga satuan</span>
          <span>Rp{{ number_format($item['price'], 0, ',', '.') }}</span>
        </div>

        <div class="d-flex justify-content-between mt-2 text-muted">
            <span>Biaya Layanan ({{ $servicePercent }}%)</span>
            <span>Rp{{ number_format($serviceFee, 0, ',', '.') }}</span>
        </div>

        <hr class="my-2">
      @endforeach

      <div class="d-flex justify-content-between fw-bold text-orange mt-3">
          <span>Total Bayar</span>
          <span>Rp{{ number_format($totalBayar, 0, ',', '.') }}</span>
      </div>


    </div>

      {{-- Error --}}
      @if(isset($errorMessage))
        <div class="alert alert-danger mt-4">
          <strong>Info:</strong> {{ $errorMessage }}
        </div>
      @endif

      {{-- Tombol --}}
      @if($transaction->payment_status == 'unpaid')
      <div class="d-flex justify-content-end gap-2 mt-4">
        <form action="{{ route('ticket.cancel', $transaction->id) }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-outline-secondary rounded-pill px-4">
            Batalkan
          </button>
        </form>

        <form action="{{ route('ticket.pay', $transaction->id) }}" method="POST">
          @csrf
          <button type="submit" class="btn-orange-pill">
            Bayar Sekarang
          </button>
        </form>
      </div>
      @endif

    </div>
  </div>
</div>
@endsection
