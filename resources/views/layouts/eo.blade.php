<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EO Dashboard')</title>

    {{-- Fonts & Tailwind --}}
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>

    {{-- Custom CSS --}}
    <link rel="stylesheet" href="{{ asset('css/admin_dashboard.css') }}">
    @stack('styles')
</head>

<body class="bg-gray-100 font-[Inter,sans-serif] min-h-screen text-gray-800">

<div class="flex min-h-screen">

    {{-- SIDEBAR EO --}}
    <aside class="w-64 bg-white border-r border-gray-200 shadow-sm fixed inset-y-0 left-0 z-40">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-blue-600">EO Dashboard</h2>
            <p class="text-xs text-gray-500">Event Organizer Panel</p>
        </div>

        <nav class="px-4 py-4 space-y-1 text-sm">

            {{-- Dashboard --}}
            <a href="#"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-gray-700 hover:bg-blue-50 hover:text-blue-600 font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24">
                    <path d="M3 13h8V3H3v10zM13 21h8V11h-8v10zM13 3v6h8V3h-8zM3 21h8v-6H3v6z"/>
                </svg>
                Dashboard
            </a>

            {{-- Profil EO --}}
            <a href="#"
               class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-blue-50 hover:text-blue-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24">
                    <circle cx="12" cy="7" r="4"/>
                    <path d="M5 21v-2a4 4 0 014-4h6a4 4 0 014 4v2"/>
                </svg>
                Profil EO
            </a>

            {{-- Event --}}
            <div class="mt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase">Event</p>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 mt-1 rounded-md hover:bg-blue-50 hover:text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 002-2V7H3v12a2 2 0 002 2z"/>
                    </svg>
                    Event Saya
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-blue-50 hover:text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Ajukan Event
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-blue-50 hover:text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                    Status Persetujuan
                </a>
            </div>

            {{-- Penjualan --}}
            <div class="mt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase">Penjualan</p>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-blue-50 hover:text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <circle cx="12" cy="7" r="4"/>
                        <path d="M5 21v-2a4 4 0 014-4h6a4 4 0 014 4v2"/>
                    </svg>
                    Peserta
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-blue-50 hover:text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <path d="M2 10h20"/>
                    </svg>
                    Transaksi Tiket & Merch
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-blue-50 hover:text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M3 3v18h18"/>
                        <path d="M18 17l-5-5-4 4-3-3"/>
                    </svg>
                    Laporan Penjualan
                </a>
            </div>

            {{-- Keuangan --}}
            <div class="mt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase">Keuangan</p>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-blue-50 hover:text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M12 8c-3.314 0-6 1.791-6 4s2.686 4 6 4 6-1.791 6-4"/>
                        <path d="M6 12v4c0 2.209 2.686 4 6 4s6-1.791 6-4v-4"/>
                    </svg>
                    Ajukan Penarikan
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-blue-50 hover:text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                    Riwayat Penarikan
                </a>
            </div>

            {{-- Pembatalan & Refund --}}
            <div class="mt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase">Pembatalan & Refund</p>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-red-50 hover:text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Pembatalan Event
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-red-50 hover:text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M3 10h11a4 4 0 110 8h-1"/>
                        <path d="M3 10l4-4m-4 4l4 4"/>
                    </svg>
                    Refund Pembeli
                </a>
            </div>

        </nav>
    </aside>

    {{-- CONTENT --}}
    <div class="flex-1 ml-64">

        <header class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center shadow-sm">
            <h1 class="text-lg font-semibold">@yield('title')</h1>

            <form action="#" method="POST">
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-semibold">
                    Logout
                </button>
            </form>
        </header>

        <main class="p-6">
            @yield('content')
        </main>

    </div>

</div>

</body>
</html>
