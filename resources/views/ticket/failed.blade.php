@extends('layouts.app')

@section('title', 'Pembelian Gagal')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-10 bg-gray-50 text-gray-800">
  <div class="bg-white shadow-lg rounded-2xl p-8 w-full max-w-3xl">

    <!-- Judul -->
    <h2 class="text-2xl md:text-3xl font-extrabold text-center text-red-600 mb-8">
      ❌ Pembelian Gagal
    </h2>

    <!-- Info Transaksi -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 mb-6">
      <div><span class="font-semibold">📧 Email:</span> {{ $transaction->email ?? '-' }}</div>
      <div><span class="font-semibold">💳 Status Pembayaran:</span> {{ $transaction->payment_status ?? '-' }}</div>
      <div><span class="font-semibold">🕒 Waktu Checkout:</span> {{ $transaction->checkout_time ?? '-' }}</div>
      <div><span class="font-semibold">🆔 ID Transaksi:</span> {{ $transaction->id ?? '-' }}</div>
    </div>

    <!-- Pesan -->
    <div class="text-center mb-8">
      <p class="text-lg text-gray-700">
        Maaf, transaksi Anda gagal diproses.  
        Silakan coba melakukan pembelian ulang atau hubungi admin untuk bantuan.
      </p>
    </div>

    <!-- Tombol -->
    <div class="text-center">
      <a href="{{ url('/form?event_id=' . ($transaction->event_id ?? 1)) }}" class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
        🔄 Coba Lagi
      </a>
    </div>

  </div>
</div>
@endsection
