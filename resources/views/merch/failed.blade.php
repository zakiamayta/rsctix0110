@extends('layouts.app')

@section('title', 'Pembayaran Merchandise Gagal')

@section('content')
<div class="container py-5">
  <div class="card shadow-lg border-0 rounded-4 text-center p-4 fade-in">
    
    {{-- Icon gagal --}}
    <div class="mb-3">
      <i class="bi bi-x-circle-fill text-danger" style="font-size: 64px;"></i>
    </div>

    {{-- Judul --}}
    <h1 class="text-danger mb-3 fw-bold">Pembayaran Gagal</h1>
    <p class="mb-4">Maaf, transaksi merchandise Anda tidak berhasil. Silakan coba lagi atau hubungi kami jika mengalami kendala.</p>

    {{-- Tombol --}}
    <div class="d-flex justify-content-center gap-3 mt-3">
      <a href="{{ route('merch.index', 1) }}" class="btn-orange-pill px-4">
        <i class="bi bi-arrow-repeat me-2"></i> Coba Lagi
      </a>
      <a href="{{ url('/') }}" class="btn-cancel">
        <i class="bi bi-house-door me-2"></i> Kembali ke Home
      </a>
    </div>

    {{-- Kontak Bantuan --}}
    <div class="mt-4 small text-muted">
      <p class="fw-semibold mb-2">Butuh bantuan?</p>
      <p class="mb-1">
        <i class="bi bi-envelope-fill text-orange me-2"></i>
        <a href="mailto:support@eventnesia.com" class="text-orange">support@eventnesia.com</a>
      </p>
      <p class="mb-0">
        <i class="bi bi-telephone-fill text-orange me-2"></i>
        <a href="https://wa.me/6281234567890" class="text-orange">+62 812-3456-7890</a>
      </p>
    </div>
  </div>
</div>
@endsection