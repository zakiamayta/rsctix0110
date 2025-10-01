@extends('layouts.app')

@section('title', 'Pembelian Sukses')

@section('content')
<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 py-10 bg-gray-900 min-h-screen flex items-center justify-center">
  <div class="bg-gray-800 shadow-2xl rounded-2xl p-8 w-full max-w-2xl text-center">

    <!-- Ikon Sukses -->
    <div class="flex justify-center mb-6">
      <div class="bg-green-900/30 p-4 rounded-full shadow-inner animate-bounce">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
      </div>
    </div>

    <!-- Judul -->
    <h2 class="text-3xl font-extrabold text-green-400 mb-2">
      Pembelian Berhasil
    </h2>
    <p class="text-gray-400 mb-6">Detail transaksi Anda telah tercatat di sistem kami.</p>

    <!-- Info Transaksi -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-300 mb-8 text-left bg-gray-700/50 p-4 rounded-lg">
      <div><span class="font-semibold text-gray-200">Email:</span> {{ $transaction->email ?? '-' }}</div>
      <div>
        <span class="font-semibold text-gray-200">Status Pembayaran:</span> 
        <span class="{{ $transaction->payment_status == 'paid' ? 'text-green-400 font-semibold' : 'text-yellow-400 font-semibold' }}">
          {{ ucfirst($transaction->payment_status ?? '-') }}
        </span>
      </div>
      <div><span class="font-semibold text-gray-200">Waktu Checkout:</span> {{ $transaction->checkout_time ?? '-' }}</div>
      <div><span class="font-semibold text-gray-200">ID Transaksi:</span> #{{ $transaction->id ?? '-' }}</div>
    </div>

    <!-- Pesan -->
    <div class="text-center mb-6">
      <p class="text-gray-300 text-base leading-relaxed">
        Terima kasih telah memesan tiket!<br>
        Anda akan menerima <strong class="text-gray-100">E-Ticket</strong> melalui email 
        <span class="text-green-400 font-semibold">setelah pembayaran terverifikasi</span>.
      </p>
    </div>

    <!-- Peringatan Email -->
    <div class="bg-yellow-900/30 border-l-4 border-yellow-500 p-4 mb-8 text-left rounded-md">
      <p class="text-yellow-200 text-sm">
        <strong>Catatan:</strong> Jika dalam waktu <strong>24 jam</strong> email berisi E-Ticket belum Anda terima, 
        silakan hubungi <span class="font-semibold text-yellow-300">kontak person kami</span> di 
        <a href="https://wa.me/6285230088828" target="_blank" class="underline hover:text-yellow-400">+6285-2300-882-8</a>.
      </p>
    </div>

    <!-- Tombol Selesai -->
    <div class="flex justify-center">
      <a href="{{ url('/') }}" 
         class="inline-block bg-gradient-to-r from-green-500 to-green-400 hover:from-green-600 hover:to-green-500 text-white font-semibold py-3 px-8 rounded-lg shadow-md transition transform hover:scale-105">
        Selesai
      </a>
    </div>

  </div>
</div>
@endsection
