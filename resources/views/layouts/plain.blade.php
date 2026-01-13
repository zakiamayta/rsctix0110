<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Absensi Tiket RSCTIX')</title>
    
    {{-- Fonts dan Tailwind CSS --}}
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    
    <style>
        /* Menggunakan font Inter secara default */
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Style scrollbar standar (opsional, untuk konsistensi) */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
    
    {{-- KRITIKAL: Tempat terbaik untuk Alpine.js --}}
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v3.x/dist/cdn.min.js" defer></script>
</head>
<body class="antialiased">

    {{-- KONTEN UTAMA DARI VIEW AKAN DIMASUKKAN DI SINI --}}
    @yield('content')

    @yield('scripts')
</body>
</html>