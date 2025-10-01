<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Admin Dashboard')</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
</head>
<body class="bg-gray-100 font-[Inter,sans-serif] min-h-screen text-gray-800">

    {{-- Header --}}
    <header class="bg-white border-b border-gray-200 shadow-sm px-6 py-3 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 text-blue-600">...</div>
            <h1 class="text-xl font-bold text-gray-900">Admin Dashboard</h1>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-semibold text-sm transition-colors duration-200 shadow-sm">
                Logout
            </button>
        </form>
    </header>

    {{-- Navbar --}}
<nav class="bg-white border-b border-gray-100 px-6 py-3 shadow-sm">
    <ul class="flex gap-4 items-center">
        {{-- Tiket --}}
        <li class="relative group">
            <button class="px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-blue-600 transition-colors duration-200">
                Tiket
            </button>
            {{-- Dropdown Tiket --}}
            <ul class="absolute left-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg opacity-0 invisible group-hover:visible group-hover:opacity-100 transition-all duration-200 z-50">
                <li>
                    <a href="{{ route('admin.dashboard') }}" 
                       class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 text-sm">
                        Transaksi Tiket
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.absensi') }}" 
                       class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 text-sm">
                        Absensi
                    </a>
                </li>
            </ul>
        </li>

        {{-- Merch --}}
        <li class="relative group">
            <button class="px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-blue-600 transition-colors duration-200">
                Merch
            </button>
            {{-- Dropdown Merch --}}
            <ul class="absolute left-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg opacity-0 invisible group-hover:visible group-hover:opacity-100 transition-all duration-200 z-50">
                <li>
                    <a href="{{ route('admin.merch.dashboard') }}" 
                       class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 text-sm">
                        Transaksi Merch
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>



    {{-- Content --}}
    <main class="container mx-auto px-6 py-6">
        @yield('content')
    </main>

    @yield('scripts')

</body>
</html>
