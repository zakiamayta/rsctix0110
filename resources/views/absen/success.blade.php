@extends('layouts.app')

@section('title', 'Absensi Berhasil')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-green-50 px-4 py-8">
  <div class="bg-white p-8 rounded-xl shadow-xl w-full max-w-3xl">
    <h2 class="text-2xl font-bold text-green-700 mb-4 text-center">✅ Absensi Berhasil</h2>

    <div class="text-gray-800 text-sm mb-4">
        <p><strong>📅 Event:</strong> {{ $transaction->event->title ?? '-' }}</p>
        <p><strong>🆔 Kode Transaksi:</strong> {{ $transaction->kode_unik }}</p>
        <p><strong>🎫 Jumlah Tiket:</strong> {{ $details->count() }}</p>
    </div>


    <div class="mt-4">
      <h3 class="text-md font-semibold mb-2">Daftar Nama & Nomor Telepon:</h3>
      @foreach($details as $d)
        <div class="p-3 mb-2 bg-gray-100 rounded shadow-sm">
          <p><strong>{{ $d->name }}</strong></p>
          <p class="text-sm text-gray-600">📞 {{ $d->phone_number ?? 'Tidak ada' }}</p>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
