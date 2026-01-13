@extends('layouts.plain')

@section('title', 'Absen Merchandise')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="bg-white rounded-2xl shadow-lg p-8 max-w-md w-full text-center">
        <h2 class="text-2xl font-bold mb-4">Verifikasi Penukaran Merchandise</h2>

        {{-- Jika sudah absen --}}
        @if ($transaction->is_absen)
            <div class="text-green-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-16 w-16 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <h3 class="text-xl font-semibold">Sudah Absen!</h3>
                <p class="mt-2 text-gray-600">Tiket sudah ditukar.</p>
            </div>

        {{-- Jika password belum diverifikasi --}}
        @elseif(!session('absen_verified'))
            <form method="POST" action="{{ route('admin.absen.verify-merch', $transaction->kode_unik) }}" class="space-y-4">
                @csrf
                <p class="text-gray-700 mb-2">Masukkan password petugas untuk konfirmasi penukaran.</p>
                <input type="password" name="password" placeholder="Password Petugas"
                       class="w-full border rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                <button type="submit"
                        class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition">
                    Verifikasi
                </button>

                @if (session('error'))
                    <p class="text-red-600 mt-2">{{ session('error') }}</p>
                @endif
            </form>

        {{-- Jika password benar dan belum absen --}}
        @else
            <div class="text-left">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Detail Tiket Merchandise</h3>
                <div class="border rounded-lg p-4 mb-4 bg-gray-50">
                    <p><strong>Kode:</strong> {{ $transaction->kode_unik }}</p>
                    <p><strong>Email:</strong> {{ $transaction->email }}</p>
                    <p><strong>Status:</strong> {{ ucfirst($transaction->payment_status) }}</p>
                    <p><strong>Total:</strong> Rp{{ number_format($transaction->total_amount, 0, ',', '.') }}</p>
                </div>

                <h4 class="font-semibold mb-2">Daftar Item:</h4>
                <ul class="border rounded-lg divide-y">
                    @foreach ($transaction->details as $detail)
                        <li class="p-3">
                            <p class="font-medium">{{ $detail->product->name ?? 'Produk' }}</p>
                            <p class="text-sm text-gray-600">Qty: {{ $detail->quantity }}</p>
                            <p class="text-sm text-gray-600">Harga: Rp{{ number_format($detail->price, 0, ',', '.') }}</p>
                        </li>
                    @endforeach
                </ul>

                <form action="{{ route('admin.absen.store-merch', $transaction->kode_unik) }}" method="POST" class="mt-6">
                    @csrf
                    <button type="submit"
                            class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition">
                        Tandai Sudah Absen
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
