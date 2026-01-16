<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Owner Dashboard')</title>

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

    {{-- SIDEBAR --}}
    <aside class="w-64 bg-white border-r border-gray-200 shadow-sm fixed inset-y-0 left-0 z-40">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-purple-600">RSCtix</h2>
            <p class="text-xs text-gray-500">RSC Owner Panel</p>
        </div>

        <nav class="px-4 py-4 space-y-1 text-sm">

            {{-- Dashboard --}}
            <a href="#"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-gray-700 hover:bg-purple-50 hover:text-purple-600 font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24">
                    <path d="M3 13h8V3H3v10zM13 21h8V11h-8v10zM13 3v6h8V3h-8zM3 21h8v-6H3v6z"/>
                </svg>
                Dashboard
            </a>

            {{-- Event --}}
            <div class="mt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase">Manajemen Event</p>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 mt-1 rounded-md hover:bg-purple-50 hover:text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7"/>
                    </svg>
                    Persetujuan Event
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-purple-50 hover:text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 002-2V7H3v12a2 2 0 002 2z"/>
                    </svg>
                    Event Disetujui
                </a>
            </div>

            {{-- Tiket --}}
            <div class="mt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase">Tiket</p>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-blue-50 hover:text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M15 5l-6 7 6 7"/>
                    </svg>
                    Transaksi Tiket
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-blue-50 hover:text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <circle cx="12" cy="7" r="4"/>
                        <path d="M5 21v-2a4 4 0 014-4h6a4 4 0 014 4v2"/>
                    </svg>
                    Data Peserta
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-blue-50 hover:text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M3 3v18h18"/>
                        <path d="M18 17l-5-5-4 4-3-3"/>
                    </svg>
                    Laporan Penjualan Tiket
                </a>
            </div>

            {{-- Merch --}}
            <div class="mt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase">Merchandise</p>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-indigo-50 hover:text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M3 3h18v18H3z"/>
                        <path d="M3 9h18"/>
                    </svg>
                    Transaksi Merchandise
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-indigo-50 hover:text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M12 20V10"/>
                        <path d="M18 20V4"/>
                        <path d="M6 20v-6"/>
                    </svg>
                    Penjualan Merchandise
                </a>
            </div>

            {{-- Monitoring --}}
            <div class="mt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase">Monitoring</p>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-purple-50 hover:text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M3 3v18h18"/>
                        <path d="M7 14l4-4 4 4 5-5"/>
                    </svg>
                    Ringkasan Penjualan Global
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-purple-50 hover:text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M16 11c1.657 0 3-1.567 3-3.5S17.657 4 16 4s-3 1.567-3 3.5S14.343 11 16 11z"/>
                        <path d="M8 11c1.657 0 3-1.567 3-3.5S9.657 4 8 4 5 5.567 5 7.5 6.343 11 8 11z"/>
                        <path d="M2 20v-1a4 4 0 014-4h4"/>
                        <path d="M14 15h4a4 4 0 014 4v1"/>
                    </svg>
                    Performa Event Organizer
                </a>
            </div>

            {{-- Keuangan --}}
            <div class="mt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase">Keuangan</p>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-green-50 hover:text-green-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <path d="M2 10h20"/>
                    </svg>
                    Persetujuan Penarikan Dana
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-green-50 hover:text-green-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                    Riwayat Penarikan Dana
                </a>
            </div>

            {{-- Refund --}}
            <div class="mt-4">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase">Refund</p>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-red-50 hover:text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M3 10h11a4 4 0 110 8h-1"/>
                        <path d="M3 10l4-4m-4 4l4 4"/>
                    </svg>
                    Persetujuan Refund
                </a>

                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-md hover:bg-red-50 hover:text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24">
                        <path d="M12 8v4l3 3"/>
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    Riwayat Refund
                </a>
            </div>

        </nav>
    </aside>

    {{-- CONTENT --}}
    <div class="flex-1 ml-64">

        <header class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center shadow-sm">
            <h1 class="text-lg font-semibold">@yield('title')</h1>
            <form action="#" method="POST">
                <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-semibold">
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
