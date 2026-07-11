@extends('layouts.app')

@section('title', 'Pembelian Sukses')

@section('content')
<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 py-10 bg-light min-h-screen d-flex align-items-center justify-content-center">
  <div class="card shadow-lg rounded-4 p-5 w-100" style="max-width: 720px;">

    <!-- Ikon Sukses -->
    <div class="d-flex justify-content-center mb-4">
      <div class="rounded-circle d-flex align-items-center justify-content-center shadow"
           style="width:80px;height:80px;background:#dcfce7;">
        <i class="bi bi-check-lg fs-1 text-success"></i>
      </div>
    </div>

    <!-- Judul -->
    <h2 class="fw-bold text-center text-success mb-2">
      Pembelian Berhasil
    </h2>
    <p class="text-center text-muted mb-4">
      Detail transaksi Anda telah tercatat di sistem kami.
    </p>

    <!-- Info Transaksi -->
    <div class="row g-3 text-sm mb-4 bg-light border rounded-3 p-3">
      <div class="col-md-6">
        <strong>Email Pemesan:</strong><br>
        {{ $transaction->email ?? '-' }}
      </div>

      <div class="col-md-6">
        <strong>Status Pembayaran:</strong><br>
        <span class="{{ $transaction->payment_status == 'paid' ? 'text-success fw-semibold' : 'text-warning fw-semibold' }}">
          {{ ucfirst($transaction->payment_status ?? '-') }}
        </span>
      </div>

      <div class="col-md-6">
        <strong>Waktu Checkout:</strong><br>
        {{ $transaction->checkout_time ?? '-' }}
      </div>

      <div class="col-md-6">
        <strong>ID Transaksi:</strong><br>
        #{{ $transaction->id ?? '-' }}
      </div>
    </div>

    <!-- Daftar Peserta & Tujuan E-Tiket -->
    @if(isset($details) && count($details))
      <h6 class="fw-bold mb-3">Daftar Peserta &amp; Tujuan E-Tiket</h6>

      <div class="mb-4">
        @foreach($details as $d)
          <div class="d-flex align-items-start p-3 mb-2 rounded border bg-white shadow-sm">
            <div class="btn-orange-circle me-3">
              <i class="bi bi-ticket-perforated"></i>
            </div>

            <div class="flex-grow-1">
              <p class="fw-semibold mb-0">
                {{ $d->ticket_name ?? 'Tiket' }} - {{ $d->name ?? 'Tanpa Nama' }}
              </p>

              <small class="text-muted d-block">
                <i class="bi bi-telephone me-1"></i>
                {{ $d->phone_number ?? '-' }}
              </small>

              <small class="text-muted d-block">
                <i class="bi bi-envelope me-1"></i>
                E-Tiket dikirim ke:
                <strong>{{ $d->attendee_email ?? $d->email ?? '-' }}</strong>
              </small>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    <!-- Pesan -->
    <p class="text-center mb-4">
      Terima kasih telah memesan tiket.<br>
      <strong>E-Ticket</strong> untuk setiap peserta akan dikirim ke
      <span class="text-success fw-semibold">email masing-masing yang telah diisi saat pemesanan</span>,
      setelah pembayaran terverifikasi.
    </p>

    <!-- Catatan -->
    <div class="alert alert-warning text-start">
      <strong>Catatan:</strong><br>
      Pastikan email setiap peserta di atas sudah benar. Jika dalam waktu <strong>24 jam</strong>
      E-Ticket belum diterima, silakan hubungi kami melalui WhatsApp:
      <a href="https://wa.me/6285230088828" target="_blank" class="fw-semibold text-decoration-none">
        +62 852-3008-8828
      </a>
    </div>

    <!-- Tombol -->
    <div class="text-center mt-4">
      <a href="{{ url('/') }}" class="btn btn-orange-pill px-5 py-2">
        Selesai
      </a>
    </div>

  </div>
</div>
@endsection