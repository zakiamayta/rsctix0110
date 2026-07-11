@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow text-center max-w-md">

        @if($eo && $eo->status === 'rejected')
            <h2 class="text-xl font-bold mb-3 text-red-600">
                Pendaftaran Ditolak
            </h2>

            <p class="text-gray-600 text-sm mb-2">
                Pengajuan Event Organizer kamu ditolak oleh admin.
            </p>

            @if($eo->rejected_reason)
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4 text-left">
                    <strong>Alasan:</strong> {{ $eo->rejected_reason }}
                </div>
            @endif

            <a href="{{ route('eo.register') }}"
               class="mt-2 inline-block px-4 py-2 bg-orange-500 text-white rounded font-semibold">
                Daftar Ulang
            </a>
        @else
            <h2 class="text-xl font-bold mb-3">
                Menunggu Verifikasi
            </h2>

            <p class="text-gray-600 text-sm mb-4">
                Pengajuan Event Organizer kamu sedang diperiksa oleh admin.
            </p>

            <p class="text-yellow-500 font-semibold">
                Status: Pending
            </p>
        @endif

        <a href="{{ route('home') }}"
           class="mt-5 inline-block px-4 py-2 bg-gray-200 rounded">
            Kembali ke Home
        </a>

    </div>
</div>
@endsection