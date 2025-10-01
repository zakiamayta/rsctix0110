<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR Code untuk {{ $guest->name }}</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-gray-100 font-[Inter,sans-serif] min-h-screen text-gray-800 flex flex-col">

<header class="bg-white border-b border-gray-200 shadow-sm px-6 py-3 flex justify-between items-center">
    <div class="flex items-center gap-2">
        <div class="w-6 h-6 text-blue-600">
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M24 45.8096C19.6865 45.8096 15.4698 44.5305 11.8832 42.134C8.29667 39.7376 5.50128 36.3314 3.85056 32.3462C2.19985 28.361 1.76794 23.9758 2.60947 19.7452C3.451 15.5145 5.52816 11.6284 8.57829 8.5783C11.6284 5.52817 15.5145 3.45101 19.7452 2.60948C23.9758 1.76795 28.361 2.19986 32.3462 3.85057C36.3314 5.50129 39.7376 8.29668 42.134 11.8833C44.5305 15.4698 45.8096 19.6865 45.8096 24L24 24L24 45.8096Z" fill="currentColor"></path>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900">Admin Dashboard</h1>
    </div>
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-semibold text-sm transition-colors duration-200 shadow-sm">
            Logout
        </button>
    </form>
</header>

<main class="flex-grow flex items-center justify-center px-4 py-8">
    <div class="max-w-md w-full bg-white border border-gray-200 rounded-2xl shadow-xl p-8 text-center">
        <h2 class="text-lg font-semibold text-gray-700 mb-2">QR Code untuk</h2>
        <p class="text-base text-gray-600 mb-1">{{ $guest->email }}</p>
        <h1 class="text-3xl font-bold text-gray-900 mb-6">{{ strtoupper($guest->name) }}</h1>

        {{-- QR Code --}}
        <div class="my-6 p-4 bg-gray-50 rounded-lg border border-gray-200 inline-block">
            <img src="{{ asset('qrcodes/ticket_' . $guest->kode_unik . '.png') }}">
        </div>

        {{-- Tombol Aksi --}}
        <div class="flex justify-center gap-3 my-6">
            <a href="{{ route('guest.export.qr', $guest->kode_unik) }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
               Export QR ke PDF
            </a>
            <a href="{{ route('admin.dashboard') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
               ← Back ke Dashboard
            </a>
        </div>

        {{-- URL QR Code --}}
        <p class="text-sm text-gray-500 mb-4">Scan QR code di atas atau kunjungi:</p>
        <a href="{{ route('absen.form', ['kode' => $guest->kode_unik]) }}" class="inline-block bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 break-all">
            {{ route('absen.form', ['kode' => $guest->kode_unik]) }}
        </a>
    </div>
</main>

</body>
</html>
