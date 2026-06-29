<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') — RSC Ticket</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap & Tailwind --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>

    <style>
        /* ─── RSC DESIGN TOKENS ─── */
        :root {
            --rsc-orange:       #E8470A;
            --rsc-orange-dark:  #C03A08;
            --rsc-orange-mid:   #F97040;
            --rsc-orange-light: #FFF0EB;
            --rsc-orange-pale:  #FDF5F2;
            --rsc-dark:         #1A1208;
            --rsc-ink:          #2D2519;
            --rsc-muted:        #7A6E66;
            --rsc-subtle:       #ADA49C;
            --rsc-border:       #EDE8E3;
            --rsc-border-med:   #DDD7D0;
            --rsc-bg:           #F9F6F2;
            --rsc-surface:      #FFFFFF;
            --rsc-sidebar-w:    230px;
            --font-main:        'Poppins', sans-serif;
        }

        /* ─── RESET / BASE ─── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; -webkit-font-smoothing: antialiased; }
        body {
            font-family: var(--font-main);
            background: var(--rsc-bg);
            color: var(--rsc-dark);
            min-height: 100vh;
        }
        a { text-decoration: none; color: inherit; }

        /* ─── SCROLLBAR GLOBAL BARU (LEBIH TEGAS & JELAS) ─── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #FDF5F2; }
        ::-webkit-scrollbar-thumb { background: var(--rsc-border-med); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--rsc-subtle); }

        /* ═══════════════════════════════
            LAYOUT SHELL
        ═══════════════════════════════ */
        .admin-shell {
            display: flex;
            min-height: 100vh;
        }

        /* ═══════════════════════════════
            SIDEBAR — FIXED, SCROLLABLE NAV
        ═══════════════════════════════ */
        .admin-sidebar {
            width: var(--rsc-sidebar-w);
            flex-shrink: 0;
            background: var(--rsc-surface);
            border-right: 1px solid var(--rsc-border);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            height: 100vh;
            z-index: 50;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Brand */
        .sidebar-brand {
            padding: 22px 20px 18px;
            border-bottom: 1px solid var(--rsc-border);
            flex-shrink: 0;
        }
        .sidebar-brand-name {
            font-family: var(--font-main);
            font-weight: 800;
            font-size: 17px;
            color: var(--rsc-orange);
            letter-spacing: -0.5px;
            line-height: 1;
        }
        .sidebar-brand-name span { color: var(--rsc-dark); }
        .sidebar-brand-sub {
            font-size: 10px;
            color: var(--rsc-muted);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }

        /* Nav Area */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto !important;
            padding: 12px 10px 80px !important;
            scrollbar-width: thin;
            scrollbar-color: var(--rsc-subtle) #F5F1EC;
        }

        /* Sidebar Scrollbar */
        .sidebar-nav::-webkit-scrollbar {
            width: 7px !important;
        }
        .sidebar-nav::-webkit-scrollbar-track {
            background: #F5F1EC !important;
            border-radius: 4px;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: var(--rsc-subtle) !important;
            border-radius: 4px;
            border: 1px solid #F5F1EC;
        }
        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: var(--rsc-orange) !important;
        }

        /* Group label */
        .nav-group { margin-top: 18px; }
        .nav-group:first-child { margin-top: 4px; }
        .nav-group-label {
            font-size: 9px;
            font-weight: 700;
            color: var(--rsc-subtle);
            text-transform: uppercase;
            letter-spacing: 1.3px;
            padding: 0 10px 6px;
        }

        /* Nav item */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--rsc-muted);
            transition: background 0.15s, color 0.15s;
            position: relative;
        }
        .nav-link:hover {
            background: var(--rsc-orange-light);
            color: var(--rsc-orange);
        }
        .nav-link.active {
            background: var(--rsc-orange-light);
            color: var(--rsc-orange);
            font-weight: 600;
        }
        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 6px;
            bottom: 6px;
            width: 3px;
            background: var(--rsc-orange);
            border-radius: 0 3px 3px 0;
        }
        .nav-link svg {
            flex-shrink: 0;
            width: 15px;
            height: 15px;
            opacity: 0.6;
            transition: opacity 0.15s;
        }
        .nav-link:hover svg, .nav-link.active svg { opacity: 1; }
        .nav-link-danger { color: var(--rsc-muted); }
        .nav-link-danger:hover { background: #FFF0F0; color: #B92929; }
        .nav-link-danger:hover svg { opacity: 1; }

        /* ═══════════════════════════════
            MAIN CONTENT AREA
        ═══════════════════════════════ */
        .admin-main {
            flex: 1;
            margin-left: var(--rsc-sidebar-w);
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: margin-left 0.3s ease;
        }

        /* Topbar */
        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--rsc-border);
            padding: 0 24px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .topbar-left { display: flex; align-items: center; gap: 10px; }
        .topbar-page-title {
            font-family: var(--font-main);
            font-weight: 700;
            font-size: 15px;
            color: var(--rsc-dark);
            letter-spacing: -0.2px;
        }

        .topbar-right { display: flex; align-items: center; gap: 12px; }

        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            background: transparent;
            border: none;
            color: var(--rsc-dark);
            cursor: pointer;
            padding: 4px;
        }

        /* Logout button */
        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            font-family: var(--font-main);
            background: var(--rsc-orange);
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-logout:hover { background: var(--rsc-orange-dark); }
        .btn-logout svg { width: 14px; height: 14px; }

        /* Backdrop overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 45;
            backdrop-filter: blur(2px);
        }

        /* Page Content */
        .admin-content { flex: 1; padding: 24px; }

        /* Flash Messages */
        .flash-success, .flash-error, .flash-warning {
            display: flex; align-items: flex-start; gap: 10px;
            border-radius: 10px; padding: 12px 16px;
            font-size: 13px; font-weight: 500; margin-bottom: 22px;
        }
        .flash-success { background: #E8F5EE; border: 1px solid #B7DFC9; color: #1A7A44; }
        .flash-error   { background: #FDECEC; border: 1px solid #F5B8B8; color: #9C2222; }
        .flash-warning { background: #FFF5E0; border: 1px solid #F5D98A; color: #9A6200; }
        .flash-success svg, .flash-error svg, .flash-warning svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }

        /* Responsive */
        @media (max-width: 991.98px) {
            .mobile-toggle { display: block; }
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.open { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .sidebar-overlay.open { display: block; }
        }
    </style>

    @stack('styles')
</head>
<body>

@php
    $current = request()->route()?->getName();
@endphp

{{-- Backdrop Overlay untuk Mobile --}}
<div id="sidebar-overlay" class="sidebar-overlay"></div>

<div class="admin-shell">

    {{-- ════════════════════════════
         SIDEBAR
    ════════════════════════════ --}}
    <aside id="admin-sidebar" class="admin-sidebar">

        {{-- Brand --}}
        <div class="sidebar-brand">
            <div class="sidebar-brand-name">RSC<span>Ticket</span></div>
            <div class="sidebar-brand-sub">Owner Panel</div>
        </div>

        {{-- Navigation — area scrollable --}}
        <nav class="sidebar-nav">

            {{-- Dashboard --}}
            <div class="nav-group">
                <a href="{{ route('admin.dashboard') }}"
                   class="nav-link {{ $current === 'admin.dashboard' ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    Dashboard
                </a>
            </div>

            {{-- Manajemen Event --}}
            <div class="nav-group">
                <div class="nav-group-label">Manajemen Event</div>

                <a href="{{ route('admin.event.index') }}"
                   class="nav-link {{ request()->routeIs('admin.event.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4"/>
                        <path d="M21 12c0 5-4 9-9 9S3 17 3 12 7 3 12 3s9 4 9 9z"/>
                    </svg>
                    Persetujuan Event
                </a>

                <a href="{{ route('admin.event.index') }}"
                   class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Event Disetujui
                </a>
            </div>

            {{-- Tiket --}}
            <div class="nav-group">
                <div class="nav-group-label">Tiket</div>

                <a href="{{ route('admin.dashboard') }}"
                   class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    Transaksi Tiket
                </a>

                <a href="{{ route('admin.absensi') }}"
                   class="nav-link {{ $current === 'admin.absensi' ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                    Data Peserta
                </a>

                <a href="{{ route('admin.dashboard') }}"
                   class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3v18h18"/>
                        <path d="M18 17l-5-5-4 4-3-3"/>
                    </svg>
                    Laporan Penjualan Tiket
                </a>
            </div>

            {{-- Merchandise --}}
            <div class="nav-group">
                <div class="nav-group-label">Merchandise</div>

                <a href="{{ route('admin.merch.dashboard') }}"
                   class="nav-link {{ request()->routeIs('admin.merch.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    Transaksi Merchandise
                </a>

                <a href="{{ route('admin.merch.dashboard') }}"
                   class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20V10"/>
                        <path d="M18 20V4"/>
                        <path d="M6 20v-6"/>
                    </svg>
                    Penjualan Merchandise
                </a>
            </div>

            {{-- Monitoring --}}
            <div class="nav-group">
                <div class="nav-group-label">Monitoring</div>

                <a href="{{ route('admin.dashboard') }}"
                   class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3v18h18"/>
                        <path d="M7 14l4-4 4 4 5-5"/>
                    </svg>
                    Ringkasan Penjualan Global
                </a>

                <a href="{{ route('admin.dashboard') }}"
                   class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        <path d="M21 21v-2a4 4 0 0 0-3-3.87"/>
                    </svg>
                    Performa Event Organizer
                </a>
            </div>
        <div class="nav-group">
            <div class="nav-group-label">Saldo</div>

            {{-- UPDATE: Tombol Dompet & Finansial EO Sudah Satu Tema Menggunakan nav-link & SVG Icon --}}
            <a href="{{ route('admin.finance.index') }}" 
               class="nav-link {{ request()->routeIs('admin.finance.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                Dompet & Finansial EO
            </a>

            <a href="{{ route('platform.wallet.index') }}"
               class="nav-link {{ request()->routeIs('platform.wallet.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/>
                    <path d="M4 6v12a2 2 0 0 0 2 2h14v-4"/>
                    <path d="M18 12a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h4v-6z"/>
                </svg>
                Dompet Platform
            </a>
        </div>

            {{-- Refund --}}
            <div class="nav-group">
                <div class="nav-group-label">Refund</div>

                <a href="{{ route('admin.refunds.index') }}"
                   class="nav-link nav-link-danger {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 14 4 9 9 4"/>
                        <path d="M20 20v-7a4 4 0 0 0-4-4H4"/>
                    </svg>
                    Persetujuan Refund
                </a>
            </div>

        </nav>

    </aside>

    {{-- ════════════════════════════
         MAIN AREA
    ════════════════════════════ --}}
    <div class="admin-main">

        {{-- ─── TOPBAR ─── --}}
        <header class="admin-topbar">

            <div class="topbar-left">
                {{-- Hamburger untuk Mobile --}}
                <button id="mobile-toggle-btn" class="mobile-toggle" title="Buka Menu">
                    <svg style="width:22px; height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <span style="display:block; width:3px; height:18px; background:var(--rsc-orange); border-radius:2px;"></span>
                <h1 class="topbar-page-title">@yield('title', 'Dashboard')</h1>
            </div>

            <div class="topbar-right">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-logout">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>

        </header>

        {{-- ─── PAGE CONTENT ─── --}}
        <main class="admin-content">

            {{-- Flash Messages --}}
            @if(session('success'))
            <div class="flash-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="flash-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                {{ session('error') }}
            </div>
            @endif

            @if(session('warning'))
            <div class="flash-warning">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                {{ session('warning') }}
            </div>
            @endif

            @yield('content')

        </main>

    </div>

</div>

{{-- ─── SCRIPTS ─── --}}
<script>
document.addEventListener("DOMContentLoaded", function () {
    const mobileToggleBtn = document.getElementById('mobile-toggle-btn');
    const sidebar         = document.getElementById('admin-sidebar');
    const overlay         = document.getElementById('sidebar-overlay');

    if (mobileToggleBtn && sidebar && overlay) {
        function toggleSidebar() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        }
        mobileToggleBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        }
    });
});
</script>

@stack('scripts')

</body>
</html>