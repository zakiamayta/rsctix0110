@extends('layouts.app')

@section('content')

<div class="min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow text-center max-w-md">

        <h2 class="text-xl font-bold mb-3">
            Menunggu Verifikasi
        </h2>

        <p class="text-gray-600 text-sm mb-4">
            Pengajuan Event Organizer kamu sedang diperiksa oleh admin.
        </p>

        <p class="text-yellow-500 font-semibold">
            Status: Pending
        </p>

        <a href="{{ route('home') }}"
           class="mt-5 inline-block px-4 py-2 bg-gray-200 rounded">
            Kembali ke Home
        </a>

    </div>
</div>

@endsection