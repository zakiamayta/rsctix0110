@extends('layouts.plain')

@section('title', 'Konfirmasi Absensi')

@section('content')

{{-- =============================================================== --}}
{{--  KONTAINER UTAMA --}}
{{-- =============================================================== --}}
<div class="min-h-screen flex items-center justify-center bg-blue-50 p-4 sm:p-8"
     x-data="{ showModal: {{ session()->has('status') ? 'true' : 'false' }} }"
     x-init="
        // jika modal muncul, auto tutup setelah 5 detik
        if (showModal) { 
            setTimeout(() => showModal = false, 5000); 
        }
     ">

    {{-- CARD --}}
    <div class="w-full max-w-sm sm:max-w-md bg-white p-6 sm:p-10 rounded-xl shadow-2xl transition duration-300 hover:shadow-3xl border border-gray-100">
        
        {{-- HEADER --}}
        <div class="text-center mb-8">
            <div class="mx-auto w-10 h-10 text-blue-600 mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6v10.5M19.5 10.5c0 1.933-1.006 3.712-2.527 4.747L13.5 17.25M19.5 10.5h-4.5m4.5 0a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
                </svg>
            </div>
            <h2 class="text-2xl font-extrabold text-gray-900">Konfirmasi Absensi</h2>
            <p class="text-sm text-gray-500 mt-1">Hanya petugas yang berwenang yang dapat melakukan absensi.</p>
        </div>

        {{-- ALERT ERROR --}}
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm font-medium" role="alert">
                {{ session('error') }}
            </div>
        @endif

        {{-- FORM --}}
        <form action="{{ route('absen.submit', ['kode' => $transaction->kode_unik]) }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                <p class="font-semibold mb-1">Pengunjung Tujuan:</p>
                <p class="font-mono">{{ $transaction->kode_unik }}</p>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Password Petugas
                </label>
                <div class="relative">
                    <input type="password" 
                           name="password" 
                           id="password"
                           required 
                           autocomplete="current-password"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base" />
                </div>
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 text-white font-semibold py-3 rounded-lg hover:bg-blue-700 transition duration-200 ease-in-out shadow-md hover:shadow-lg">
                Konfirmasi dan Absen
            </button>
        </form>
        
        <div class="mt-8 text-center">
            <p class="text-xs text-gray-400">Pastikan kode absensi: <span class="font-mono text-gray-600">{{ $transaction->kode_unik }}</span> sudah benar.</p>
        </div>
    </div>

    {{-- =============================================================== --}}
    {{-- MODAL STATUS --}}
    {{-- =============================================================== --}}
    <template x-teleport="body">
        <div x-show="showModal" 
             x-transition.opacity
             class="fixed inset-0 bg-gray-600 bg-opacity-75 z-50 flex items-center justify-center p-4">

            <div @click.away="showModal = false"
                 class="bg-white rounded-xl shadow-3xl w-full max-w-sm sm:max-w-md mx-auto overflow-hidden transform transition-all"
                 x-transition.scale>

                <div class="p-6 sm:p-8 text-center">
                    
                    {{-- SUDAH ABSEN --}}
                    @if(session('status') === 'already_scanned')
                        <div class="text-red-600 mx-auto mb-4 w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.57.17 3.535 1.94 3.535h17.126c1.77 0 2.804-1.965 1.939-3.535l-8.567-15.549c-.662-1.203-2.324-1.203-2.986 0L12 9zM12 15h.01" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-red-700 mb-2">Sudah Absen!</h3>
                        <p class="text-gray-600 mb-6">Tiket dengan kode 
                            <span class="font-mono font-semibold">{{ session('transaction_kode_unik', $transaction->kode_unik) }}</span> 
                            sudah digunakan untuk absensi.
                        </p>

                    {{-- BERHASIL ABSEN --}}
                    @elseif(session('status') === 'success')
                        <div class="text-green-600 mx-auto mb-4 w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-green-700 mb-4">Absensi Berhasil! 🎉</h3>
                        <div class="text-left bg-green-50 p-4 rounded-lg space-y-2 mb-6 border border-green-200">
                            <p class="text-sm"><span class="font-semibold text-gray-700">Nama:</span> <span class="float-right text-gray-900 font-medium">{{ session('attendee_name') }}</span></p>
                            <p class="text-sm"><span class="font-semibold text-gray-700">Kode Unik:</span> <span class="float-right text-gray-900 font-mono">{{ session('transaction_kode_unik') }}</span></p>
                            <p class="text-sm"><span class="font-semibold text-gray-700">No. Telepon:</span> <span class="float-right text-gray-900">{{ session('attendee_phone') }}</span></p>
                            <p class="text-sm border-t pt-2 mt-2 border-green-100"><span class="font-bold text-green-700">Jumlah Tiket:</span> <span class="float-right text-green-800 font-bold text-lg">{{ session('ticket_count') ?? 1 }}</span></p>
                        </div>

                    {{-- GAGAL --}}
                    @else
                        <div class="text-gray-600 mx-auto mb-4 w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-700 mb-2">Proses Gagal</h3>
                        <p class="text-gray-600 mb-6">Terjadi kesalahan saat absensi. Coba lagi.</p>
                    @endif

                    <button @click="showModal = false"
                            class="w-full bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition duration-200 ease-in-out">
                        Tutup & Scan Berikutnya
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

{{-- ========================================= --}}
{{-- Load Alpine.js --}}
{{-- ========================================= --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

@endsection
