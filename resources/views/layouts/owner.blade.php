<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Owner Dashboard') — RSC Ticket</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    {{-- Tailwind --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>

    <style>

        :root {
            --rsc-orange:       #E8470A;
            --rsc-orange-dark:  #C03A08;
            --rsc-orange-light: #FFF0EB;

            --rsc-dark:         #1A1208;
            --rsc-muted:        #7A6E66;
            --rsc-subtle:       #ADA49C;

            --rsc-border:       #EDE8E3;
            --rsc-border-med:   #DDD7D0;

            --rsc-bg:           #F9F6F2;
            --rsc-surface:      #FFFFFF;

            --sidebar-width:    230px;

            --font-display: 'Sora', sans-serif;
            --font-body: 'DM Sans', sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--rsc-bg);
            color: var(--rsc-dark);
            font-family: var(--font-body);
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* =========================
           LAYOUT
        ========================= */

        .owner-shell {
            display: flex;
            min-height: 100vh;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .owner-sidebar {
            width: var(--sidebar-width);
            background: var(--rsc-surface);
            border-right: 1px solid var(--rsc-border);
            position: fixed;
            inset-y: 0;
            left: 0;
            z-index: 50;

            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 22px 20px 18px;
            border-bottom: 1px solid var(--rsc-border);
        }

        .sidebar-brand-name {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 17px;
            color: var(--rsc-orange);
            line-height: 1;
        }

        .sidebar-brand-name span {
            color: var(--rsc-dark);
        }

        .sidebar-brand-sub {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
            color: var(--rsc-muted);
            font-weight: 600;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 14px 10px 20px;
        }

        .nav-group {
            margin-top: 18px;
        }

        .nav-group:first-child {
            margin-top: 0;
        }

        .nav-group-label {
            font-size: 9px;
            font-weight: 700;
            color: var(--rsc-subtle);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 0 10px 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 9px;

            padding: 9px 10px;
            border-radius: 8px;

            font-size: 13px;
            font-weight: 500;

            color: var(--rsc-muted);

            transition: .15s;
            position: relative;
        }

        .nav-link:hover {
            background: var(--rsc-orange-light);
            color: var(--rsc-orange);
        }

        .nav-link.active {
            background: var(--rsc-orange-light);
            color: var(--rsc-orange);
            font-weight: 700;
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
            width: 15px;
            height: 15px;
            opacity: .7;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: 14px;
            border-top: 1px solid var(--rsc-border);
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--rsc-border);
        }

        .sidebar-user-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--rsc-dark);
        }

        .sidebar-user-email {
            font-size: 10px;
            color: var(--rsc-muted);
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* =========================
           MAIN
        ========================= */

        .owner-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-width: 0;
        }

        /* =========================
           TOPBAR
        ========================= */

        .owner-topbar {
            height: 58px;
            background: rgba(255,255,255,.92);

            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);

            border-bottom: 1px solid var(--rsc-border);

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 28px;

            position: sticky;
            top: 0;
            z-index: 40;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar-title {
            font-family: var(--font-display);
            font-size: 15px;
            font-weight: 700;
            color: var(--rsc-dark);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-search {
            display: flex;
            align-items: center;
            gap: 8px;

            background: var(--rsc-bg);
            border: 1px solid var(--rsc-border);

            border-radius: 8px;

            padding: 6px 12px;

            width: 220px;

            font-size: 12px;
            color: var(--rsc-muted);
        }

        .topbar-search svg {
            width: 13px;
            height: 13px;
        }

        .topbar-avatar-btn {
            display: flex;
            align-items: center;
            gap: 8px;

            padding: 4px 10px 4px 4px;

            border: 1px solid var(--rsc-border);
            border-radius: 9px;

            background: white;

            cursor: pointer;
        }

        .topbar-avatar-btn:hover {
            background: var(--rsc-orange-light);
        }

        .topbar-avatar {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            object-fit: cover;
        }

        .topbar-avatar-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--rsc-dark);
        }

        /* =========================
           CONTENT
        ========================= */

        .owner-content {
            padding: 28px;
        }

        /* =========================
           FLASH MESSAGE
        ========================= */

        .flash-success {
            background: #E8F5EE;
            border: 1px solid #B7DFC9;
            color: #1A7A44;

            padding: 12px 16px;
            border-radius: 10px;

            font-size: 13px;
            font-weight: 500;

            margin-bottom: 22px;
        }

        .flash-error {
            background: #FDECEC;
            border: 1px solid #F5B8B8;
            color: #9C2222;

            padding: 12px 16px;
            border-radius: 10px;

            font-size: 13px;
            font-weight: 500;

            margin-bottom: 22px;
        }

        /* =========================
           SCROLLBAR
        ========================= */

        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--rsc-border-med);
            border-radius: 999px;
        }

    </style>

    @stack('styles')
</head>

<body>

@php

    $user = auth()->user();

    $current = request()->route()->getName();

@endphp

<div class="owner-shell">

    {{-- SIDEBAR --}}
    <aside class="owner-sidebar">

        {{-- BRAND --}}
        <div class="sidebar-brand">

            <div class="sidebar-brand-name">
                RSC<span>Ticket</span>
            </div>

            <div class="sidebar-brand-sub">
                Owner Panel
            </div>

        </div>

        {{-- NAVIGATION --}}
        <nav class="sidebar-nav">

            {{-- DASHBOARD --}}
            <div class="nav-group">

                <a href="{{ route('owner.dashboard') }}"
                   class="nav-link {{ $current === 'owner.dashboard' ? 'active' : '' }}">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>

                    Dashboard

                </a>

            </div>

            {{-- APPROVAL --}}
            <div class="nav-group">

                <div class="nav-group-label">
                    Approval
                </div>

                <a href="{{ route('owner.eo.index') }}"
                   class="nav-link {{ request()->routeIs('owner.eo.index') ? 'active' : '' }}">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>

                    Approval EO

                </a>

                <a href="{{ route('owner.events.index') }}"
                   class="nav-link {{ request()->routeIs('owner.events.*') ? 'active' : '' }}">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>

                    Approval Event

                </a>

                <a href="{{ route('owner.withdrawals.index') }}"
                class="nav-link {{ request()->routeIs('owner.withdrawals.index', 'owner.withdrawals.show') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    Withdrawal Tiket
                </a>

                <a href="{{ route('owner.withdrawals.merch.index') }}"
                class="nav-link {{ request()->routeIs('owner.withdrawals.merch.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    Withdrawal Merch
                </a>

            </div>

            {{-- MANAGEMENT --}}
            <div class="nav-group">

                <div class="nav-group-label">
                    Management
                </div>

                <a href="#"
                   class="nav-link">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                        <polyline points="3 20 21 20"/>
                    </svg>

                    Statistik Platform

                </a>

            </div>

        </nav>

        {{-- FOOTER --}}
        <div class="sidebar-footer">

            <div class="sidebar-user">

                <img src="{{ $user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}"
                     class="sidebar-avatar"
                     alt="{{ $user->name }}">

                <div style="min-width:0;">

                    <div class="sidebar-user-name">
                        {{ $user->name }}
                    </div>

                    <div class="sidebar-user-email">
                        {{ $user->email }}
                    </div>

                </div>

            </div>

        </div>

    </aside>

    {{-- MAIN --}}
    <div class="owner-main">

        {{-- TOPBAR --}}
        <header class="owner-topbar">

            <div class="topbar-left">

                <span style="display:block;width:3px;height:18px;background:var(--rsc-orange);border-radius:2px;"></span>

                <div class="topbar-title">
                    @yield('title', 'Owner Dashboard')
                </div>

            </div>

            <div class="topbar-right">

                {{-- SEARCH --}}
                <div class="topbar-search">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>

                    Cari data...

                </div>

                {{-- USER --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit"
                            class="topbar-avatar-btn">

                        <img src="{{ $user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}"
                             class="topbar-avatar"
                             alt="{{ $user->name }}">

                        <span class="topbar-avatar-name">
                            {{ explode(' ', $user->name)[0] }}
                        </span>

                    </button>
                </form>

            </div>

        </header>

        {{-- CONTENT --}}
        <main class="owner-content">

            @if(session('success'))

                <div class="flash-success">
                    {{ session('success') }}
                </div>

            @endif

            @if(session('error'))

                <div class="flash-error">
                    {{ session('error') }}
                </div>

            @endif

            @yield('content')

        </main>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@stack('scripts')

</body>
</html>