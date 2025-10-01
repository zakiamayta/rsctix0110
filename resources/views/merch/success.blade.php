@extends('layouts.app')

@section('title', 'Pembayaran Merchandise Berhasil')

@section('content')
<div class="container py-5 d-flex justify-content-center align-items-center fade-in" style="min-height:70vh;">
    <div class="card border-0 shadow-lg rounded-4 text-center p-5" style="max-width: 500px;">

        {{-- ✅ Ikon sukses --}}
        <div class="mb-4">
            <i class="bi bi-check-circle-fill" style="font-size: 4rem; color: #22c55e;"></i>
        </div>

        {{-- 🎉 Judul --}}
        <h2 class="fw-bold mb-3 text-white">Pembayaran Berhasil!</h2>
        <p class="text-white mb-4">Terima kasih telah membeli merchandise. Pesananmu sedang diproses.</p>
        {{-- 🔙 Tombol kembali --}}
        <a href="{{ url('/') }}" class="btn btn-orange-pill btn-lg w-100">
            <i class="bi bi-house-door"></i> Kembali ke Beranda
        </a>
    </div>
</div>
@endsection@extends('layouts.app')

@section('title', 'Pembayaran Merchandise Berhasil')

@section('content')
<div class="container py-5 d-flex justify-content-center align-items-center fade-in" style="min-height:70vh;">
    <div class="card border-0 shadow-lg rounded-4 text-center p-5" style="max-width: 500px;">

        {{-- ✅ Ikon sukses --}}
        <div class="mb-4">
            <i class="bi bi-check-circle-fill" style="font-size: 4rem; color: #22c55e;"></i>
        </div>

        {{-- 🎉 Judul --}}
        <h2 class="fw-bold mb-3 text-white">Pembayaran Berhasil!</h2>
        <p class="text-white mb-4">Terima kasih telah membeli merchandise. Pesananmu sedang diproses.</p>
        {{-- 🔙 Tombol kembali --}}
        <a href="{{ url('/') }}" class="btn btn-orange-pill btn-lg w-100">
            <i class="bi bi-house-door"></i> Kembali ke Beranda
        </a>
    </div>
</div>
@endsection