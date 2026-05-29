<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EO Dashboard') — RSC Ticket</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;1,9..40,400&display=swap" rel="stylesheet">

    {{-- Tailwind --}}
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
            --font-display:     'Sora', sans-serif;
            --font-body:        'DM Sans', sans-serif;
        }

        /* ─── RESET / BASE ─── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; -webkit-font-smoothing: antialiased; }
        body {
            font-family: var(--font-body);
            background: var(--rsc-bg);
            color: var(--rsc-dark);
            min-height: 100vh;
        }
        a { text-decoration: none; color: inherit; }

        /* ─── SCROLLBAR ─── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--rsc-border-med); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--rsc-subtle); }

        /* ═══════════════════════════════
           LAYOUT SHELL
        ═══════════════════════════════ */
        .eo-shell {
            display: flex;
            min-height: 100vh;
        }

        /* ═══════════════════════════════
           SIDEBAR
        ═══════════════════════════════ */
        .eo-sidebar {
            width: var(--rsc-sidebar-w);
            flex-shrink: 0;
            background: var(--rsc-surface);
            border-right: 1px solid var(--rsc-border);
            position: fixed;
            inset-y: 0;
            left: 0;
            z-index: 50;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Logo */
        .sidebar-brand {
            padding: 22px 20px 18px;
            border-bottom: 1px solid var(--rsc-border);
            flex-shrink: 0;
        }
        .sidebar-brand-name {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 17px;
            color: var(--rsc-orange);
            letter-spacing: -0.5px;
            line-height: 1;
        }
        .sidebar-brand-name span {
            color: var(--rsc-dark);
        }
        .sidebar-brand-sub {
            font-size: 10px;
            color: var(--rsc-muted);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }

        /* Nav scroll area */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 12px 10px 20px;
        }

        /* Section labels */
        .nav-group {
            margin-top: 18px;
        }
        .nav-group:first-child {
            margin-top: 4px;
        }
        .nav-group-label {
            font-size: 9px;
            font-weight: 700;
            color: var(--rsc-subtle);
            text-transform: uppercase;
            letter-spacing: 1.3px;
            padding: 0 10px 6px;
        }

        /* Nav items */
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
        .nav-link:hover svg,
        .nav-link.active svg {
            opacity: 1;
        }
        .nav-link-danger:hover {
            background: #FFF0F0;
            color: #B92929;
        }
        .nav-link-danger:hover svg {
            opacity: 1;
        }

        /* Sidebar footer */
        .sidebar-footer {
            padding: 14px 14px;
            border-top: 1px solid var(--rsc-border);
            flex-shrink: 0;
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--rsc-border);
            flex-shrink: 0;
        }
        .sidebar-user-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--rsc-dark);
            line-height: 1.3;
        }
        .sidebar-user-email {
            font-size: 10px;
            color: var(--rsc-muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 130px;
        }

        /* ═══════════════════════════════
           MAIN CONTENT AREA
        ═══════════════════════════════ */
        .eo-main {
            flex: 1;
            margin-left: var(--rsc-sidebar-w);
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* ─── TOPBAR ─── */
        .eo-topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--rsc-border);
            padding: 0 28px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        /* Breadcrumb / page title */
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .topbar-page-title {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 15px;
            color: var(--rsc-dark);
            letter-spacing: -0.2px;
        }

        /* Right side: search + avatar */
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Search bar */
        .topbar-search {
            display: flex;
            align-items: center;
            gap: 7px;
            background: var(--rsc-bg);
            border: 1px solid var(--rsc-border);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 12px;
            color: var(--rsc-muted);
            width: 200px;
            transition: border-color 0.15s;
            cursor: text;
        }
        .topbar-search:hover {
            border-color: var(--rsc-border-med);
        }
        .topbar-search svg {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
            opacity: 0.5;
        }

        /* Notification bell */
        .topbar-icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid var(--rsc-border);
            background: var(--rsc-surface);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
            position: relative;
        }
        .topbar-icon-btn:hover {
            background: var(--rsc-orange-light);
            border-color: #F5C4A0;
        }
        .topbar-icon-btn svg {
            width: 16px;
            height: 16px;
            color: var(--rsc-muted);
        }
        .notif-dot {
            position: absolute;
            top: 6px;
            right: 7px;
            width: 6px;
            height: 6px;
            background: var(--rsc-orange);
            border-radius: 50%;
            border: 1.5px solid white;
        }

        /* Avatar + dropdown */
        .topbar-avatar-wrap {
            position: relative;
        }
        .topbar-avatar-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px 4px 4px;
            border-radius: 9px;
            border: 1px solid var(--rsc-border);
            background: var(--rsc-surface);
            cursor: pointer;
            transition: background 0.15s;
        }
        .topbar-avatar-btn:hover {
            background: var(--rsc-orange-light);
            border-color: #F5C4A0;
        }
        .topbar-avatar-img {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            object-fit: cover;
        }
        .topbar-avatar-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--rsc-dark);
        }
        .topbar-avatar-chevron {
            width: 12px;
            height: 12px;
            color: var(--rsc-muted);
        }

        /* Dropdown */
        .topbar-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            width: 220px;
            background: var(--rsc-surface);
            border: 1px solid var(--rsc-border);
            border-radius: 12px;
            box-shadow: 0 8px 28px rgba(26,18,8,0.1);
            overflow: hidden;
            display: none;
            z-index: 100;
        }
        .topbar-dropdown.open {
            display: block;
            animation: dropIn 0.15s ease;
        }
        @keyframes dropIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .dropdown-header {
            padding: 14px 16px;
            border-bottom: 1px solid var(--rsc-border);
        }
        .dropdown-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--rsc-dark);
            font-family: var(--font-display);
        }
        .dropdown-email {
            font-size: 11px;
            color: var(--rsc-muted);
            margin-top: 2px;
        }
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px 16px;
            font-size: 13px;
            color: var(--rsc-ink);
            font-weight: 500;
            transition: background 0.1s;
            cursor: pointer;
        }
        .dropdown-item:hover {
            background: var(--rsc-bg);
        }
        .dropdown-item svg {
            width: 14px;
            height: 14px;
            color: var(--rsc-muted);
            flex-shrink: 0;
        }
        .dropdown-divider {
            height: 1px;
            background: var(--rsc-border);
            margin: 4px 0;
        }
        .dropdown-item-danger {
            color: #B92929;
        }
        .dropdown-item-danger svg {
            color: #B92929;
        }
        .dropdown-item-danger:hover {
            background: #FFF0F0;
        }

        /* ─── PAGE CONTENT ─── */
        .eo-content {
            flex: 1;
            padding: 28px;
        }

        /* ─── FLASH MESSAGES ─── */
        .flash-success {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #E8F5EE;
            border: 1px solid #B7DFC9;
            color: #1A7A44;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 22px;
        }
        .flash-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #FDECEC;
            border: 1px solid #F5B8B8;
            color: #9C2222;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 22px;
        }
        .flash-warning {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #FFF5E0;
            border: 1px solid #F5D98A;
            color: #9A6200;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 22px;
        }
        .flash-success svg, .flash-error svg, .flash-warning svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* ─── UTILITY CLASSES ─── */
        .rsc-card {
            background: var(--rsc-surface);
            border: 1px solid var(--rsc-border);
            border-radius: 12px;
        }
        .rsc-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            font-family: var(--font-display);
            cursor: pointer;
            transition: all 0.15s;
            border: none;
        }
        .rsc-btn-primary {
            background: var(--rsc-orange);
            color: #fff;
        }
        .rsc-btn-primary:hover {
            background: var(--rsc-orange-dark);
            transform: translateY(-1px);
        }
        .rsc-btn-secondary {
            background: transparent;
            color: var(--rsc-muted);
            border: 1px solid var(--rsc-border);
        }
        .rsc-btn-secondary:hover {
            background: var(--rsc-orange-light);
            border-color: #F5C4A0;
            color: var(--rsc-orange);
        }
        .rsc-badge-approved {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            padding: 3px 9px;
            border-radius: 5px;
            background: #E8F5EE;
            color: #1A7A44;
        }
        .rsc-badge-pending {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            padding: 3px 9px;
            border-radius: 5px;
            background: #FFF5E0;
            color: #9A6200;
        }
        .rsc-badge-rejected {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            padding: 3px 9px;
            border-radius: 5px;
            background: #FDECEC;
            color: #9C2222;
        }
        .rsc-badge-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
            flex-shrink: 0;
        }

        /* Page heading helper */
        .page-heading {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 22px;
            color: var(--rsc-dark);
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        .page-sub {
            font-size: 13px;
            color: var(--rsc-muted);
            margin-top: 4px;
        }
    </style>

    @stack('styles')
</head>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<body>

@php
    $user = auth('user')->user();
    $eo   = \Illuminate\Support\Facades\DB::table('eo')
                ->where('user_id', $user->id)
                ->first();

    /* active route helper */
    $current = request()->route()->getName();
@endphp

<div class="eo-shell">

    {{-- ════════════════════════════
         SIDEBAR
    ════════════════════════════ --}}
    <aside class="eo-sidebar">

        {{-- Brand --}}
        <div class="sidebar-brand">
            <div class="sidebar-brand-name">RSC<span>Ticket</span></div>
            <div class="sidebar-brand-sub">Event Organizer Panel</div>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav">

            {{-- Main --}}
            <div class="nav-group">
                <a href="{{ route('eo.dashboard') }}"
                   class="nav-link {{ $current === 'eo.dashboard' ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('eo.profile') }}"
                   class="nav-link {{ $current === 'eo.profile' ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                    Profil EO
                </a>
            </div>

            {{-- Event --}}
            <div class="nav-group">
                <div class="nav-group-label">Event</div>
                <a href="{{ route('eo.event.index') }}"
                   class="nav-link {{ $current === 'eo.event.index' ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Event Saya
                </a>
                <a href="{{ route('eo.event.create') }}"
                   class="nav-link {{ $current === 'eo.event.create' ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/>
                        <line x1="12" y1="8" x2="12" y2="16"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    Ajukan Event
                </a>
                <a href="{{ route('eo.status') }}"
                   class="nav-link {{ $current === 'eo.status' ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4"/>
                        <path d="M21 12c0 5-4 9-9 9S3 17 3 12 7 3 12 3s9 4 9 9z"/>
                    </svg>
                    Status Persetujuan
                </a>
                <a href="{{ route('eo.merch.index') }}"
                   class="nav-link {{ $current === 'eo.merch.index' ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4"/>
                        <path d="M21 12c0 5-4 9-9 9S3 17 3 12 7 3 12 3s9 4 9 9z"/>
                    </svg>
                    Merch
                </a>

            </div>

            {{-- Penjualan --}}
            <div class="nav-group">
                <div class="nav-group-label">Penjualan</div>
                <!-- <a href="#" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Peserta
                </a> -->
 <a href="{{ route('eo.transactions') }}"
   class="nav-link {{ request()->routeIs('eo.transactions') ? 'active' : '' }}">
    
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="1" y="4" width="22" height="16" rx="2"/>
        <line x1="1" y1="10" x2="23" y2="10"/>
    </svg>

    Transaksi Tiket
</a>

<a href="{{ route('eo.merch.transactions') }}"
   class="nav-link {{ request()->routeIs('eo.merch.transactions') ? 'active' : '' }}">
    
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M6 2l1.5 4h9L18 2"/>
        <path d="M3 6h18l-2 14H5L3 6z"/>
        <path d="M9 10v6"/>
        <path d="M15 10v6"/>
    </svg>

    Transaksi Merch
</a>
                <!-- <a href="#" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                        <polyline points="3 20 21 20"/>
                    </svg>
                    Laporan Penjualan
                </a> -->
            </div>

            {{-- Keuangan --}}
            <div class="nav-group">
                <div class="nav-group-label">Keuangan</div>
                    <a href="{{ route('eo.saldo') }}"
                class="nav-link {{ request()->routeIs('eo.saldo') ? 'active' : '' }}">
                    
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    Saldo
                </a>
                <!-- <a href="#" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="17 1 21 5 17 9"/>
                        <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                        <polyline points="7 23 3 19 7 15"/>
                        <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                    </svg>
                    Ajukan Penarikan
                </a>
                <a href="#" class="nav-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 .49-4.95"/>
                    </svg>
                    Riwayat Penarikan
                </a> -->
            </div>

            {{-- Pembatalan & Refund --}}
            <div class="nav-group">
                <div class="nav-group-label">Pembatalan & Refund</div>
                <a href="#" class="nav-link nav-link-danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    Pembatalan Event
                </a>
                <a href="#" class="nav-link nav-link-danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 14 4 9 9 4"/>
                        <path d="M20 20v-7a4 4 0 0 0-4-4H4"/>
                    </svg>
                    Refund Pembeli
                </a>
            </div>

        </nav>

        {{-- Footer: current user --}}
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <img src="{{ $user->avatar }}"
                     class="sidebar-avatar"
                     referrerpolicy="no-referrer"
                     alt="{{ $user->name }}">
                <div style="min-width:0;">
                    <div class="sidebar-user-name">{{ $user->name }}</div>
                    <div class="sidebar-user-email">{{ $user->email }}</div>
                </div>
            </div>
        </div>

    </aside>

    {{-- ════════════════════════════
         MAIN AREA
    ════════════════════════════ --}}
    <div class="eo-main">

        {{-- ─── TOPBAR ─── --}}
        <header class="eo-topbar">

            <div class="topbar-left">
                {{-- Divider accent --}}
                <span style="display:block; width:3px; height:18px; background:var(--rsc-orange); border-radius:2px;"></span>
                <h1 class="topbar-page-title">@yield('title', 'Dashboard')</h1>
            </div>

            <div class="topbar-right">

                {{-- Search --}}
                <div class="topbar-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    Cari event…
                </div>

                {{-- Notification --}}
                <div class="topbar-icon-btn" title="Notifikasi">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span class="notif-dot"></span>
                </div>

                {{-- Avatar + dropdown --}}
                <div class="topbar-avatar-wrap">
                    <button id="topbar-avatar-btn" class="topbar-avatar-btn">
                        <img src="{{ $user->avatar }}"
                             class="topbar-avatar-img"
                             referrerpolicy="no-referrer"
                             alt="{{ $user->name }}">
                        <span class="topbar-avatar-name">{{ explode(' ', $user->name)[0] }}</span>
                        <svg class="topbar-avatar-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>

                    <div id="topbar-dropdown" class="topbar-dropdown">
                        <div class="dropdown-header">
                            <div class="dropdown-name">{{ $user->name }}</div>
                            <div class="dropdown-email">{{ $user->email }}</div>
                        </div>

                        @if($eo && $eo->status === 'approved')
                        <a href="{{ route('eo.dashboard') }}" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7" rx="1"/>
                                <rect x="14" y="3" width="7" height="7" rx="1"/>
                                <rect x="3" y="14" width="7" height="7" rx="1"/>
                                <rect x="14" y="14" width="7" height="7" rx="1"/>
                            </svg>
                            Dashboard EO
                        </a>
                        @endif

                        <a href="{{ route('eo.event.index') }}" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Event Saya
                        </a>

                        <a href="{{ route('eo.status') }}" class="dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4"/>
                                <path d="M21 12c0 5-4 9-9 9S3 17 3 12 7 3 12 3s9 4 9 9z"/>
                            </svg>
                            Status Persetujuan
                        </a>

                        <div class="dropdown-divider"></div>

                        <form method="POST" action="{{ route('user.logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item dropdown-item-danger" style="width:100%; background:none; border:none; text-align:left; cursor:pointer; font-family:var(--font-body);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                    <polyline points="16 17 21 12 16 7"/>
                                    <line x1="21" y1="12" x2="9" y2="12"/>
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </header>

        {{-- ─── PAGE CONTENT ─── --}}
        <main class="eo-content">

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
(function () {
    const btn      = document.getElementById('topbar-avatar-btn');
    const dropdown = document.getElementById('topbar-dropdown');
    if (!btn || !dropdown) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== btn) {
            dropdown.classList.remove('open');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') dropdown.classList.remove('open');
    });
})();
</script>

@stack('scripts')

</body>
</html>