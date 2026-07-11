{{-- resources/views/admin/monitoring/index.blade.php --}}
@extends('layouts.admin') {{-- sesuaikan dengan layout admin yang sudah kamu pakai --}}

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6" style="font-family:'Poppins',sans-serif;">

    {{-- ===================== HEADER ===================== --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-5">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-md"
                 style="background: linear-gradient(135deg, #f97316, #facc15);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01M9 14h.01M15 14h.01" />
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-bold leading-tight" style="color:#111827;">Monitoring Event Organizer</h1>
                <p class="text-xs" style="color:#6b7280;">Pantau aktivitas, saldo, dan utang setiap EO di platform.</p>
            </div>
        </div>
    </div>

    {{-- ===================== STATISTIK PLATFORM ===================== --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">

        <div class="card rounded-xl p-3.5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background:#fff7ed;">
                <svg class="w-4 h-4" style="color:#f97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-wide truncate" style="color:#9ca3af;">Total EO</p>
                <p class="text-base font-bold leading-tight" style="color:#111827;">{{ $platformStats['total_eo'] }}
                    <span class="text-[11px] font-normal" style="color:#9ca3af;">({{ $platformStats['total_eo_approved'] }} apv)</span>
                </p>
            </div>
        </div>

        <div class="card rounded-xl p-3.5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background:#fff7ed;">
                <svg class="w-4 h-4" style="color:#f97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m0-2c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-wide truncate" style="color:#9ca3af;">Total GMV</p>
                <p class="text-base font-bold leading-tight truncate" style="color:#111827;">Rp {{ number_format($platformStats['total_gmv'], 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="card rounded-xl p-3.5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background:#eff6ff;">
                <svg class="w-4 h-4" style="color:#3b82f6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-wide truncate" style="color:#9ca3af;">Saldo Aktif</p>
                <p class="text-base font-bold leading-tight truncate" style="color:#3b82f6;">Rp {{ number_format($platformStats['total_wallet_balance'], 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="card rounded-xl p-3.5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background:#fef2f2;">
                <svg class="w-4 h-4" style="color:#ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-wide truncate" style="color:#9ca3af;">Utang</p>
                <p class="text-base font-bold leading-tight truncate" style="color:#ef4444;">Rp {{ number_format($platformStats['total_debt'], 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="card rounded-xl p-3.5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background:#fef2f2;">
                <svg class="w-4 h-4" style="color:#ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-wide truncate" style="color:#9ca3af;">EO Terkunci</p>
                <p class="text-base font-bold leading-tight" style="color:#111827;">{{ $platformStats['total_locked'] }}</p>
            </div>
        </div>

    </div>

    {{-- ===================== FILTER & SEARCH BAR ===================== --}}
    <form method="GET" class="card rounded-xl p-3 mb-5 flex flex-wrap items-center gap-2">

        <div class="relative flex-1 min-w-[180px]">
            <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2" style="color:#9ca3af;"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
            </svg>
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari nama EO..."
                class="form-control w-full pl-10 pr-3 py-2 text-sm"
                style="border-radius: 9999px;"
            >
        </div>

        <select name="status" onchange="this.form.submit()"
                class="form-control py-2 text-sm w-auto" style="border-radius: 9999px;">
            <option value="">Semua Status</option>
            <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>

        <select name="sort" onchange="this.form.submit()"
                class="form-control py-2 text-sm w-auto" style="border-radius: 9999px;">
            <option value="name" {{ $sortBy === 'name' ? 'selected' : '' }}>Urutkan: Nama A-Z</option>
            <option value="gmv" {{ $sortBy === 'gmv' ? 'selected' : '' }}>Urutkan: GMV Tertinggi</option>
            <option value="balance" {{ $sortBy === 'balance' ? 'selected' : '' }}>Urutkan: Saldo Tertinggi</option>
            <option value="debt" {{ $sortBy === 'debt' ? 'selected' : '' }}>Urutkan: Utang Tertinggi</option>
        </select>

        <button type="submit" class="btn-orange-pill shrink-0" style="padding: 8px 18px;">Terapkan</button>

        @if ($search || $statusFilter || $sortBy !== 'name')
            <a href="{{ route('admin.monitoring.index') }}" class="text-xs font-semibold ml-1" style="color:#9ca3af;">
                Reset
            </a>
        @endif
    </form>

    {{-- ===================== GRID EO ===================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse ($eoList as $eo)
            @php
                $statusStyle = match ($eo->status) {
                    'approved' => ['bg-green-100', 'text-green-700', 'Approved'],
                    'pending'  => ['bg-yellow-100', 'text-yellow-700', 'Pending'],
                    'rejected' => ['bg-red-100', 'text-red-700', 'Rejected'],
                    default    => ['bg-gray-100', 'text-gray-600', ucfirst($eo->status ?? '-')],
                };
                $gmv = $eo->ticket_gmv + $eo->merch_gmv;
            @endphp

            <a href="{{ route('admin.monitoring.eo.show', $eo->id) }}"
               class="group relative card rounded-xl p-4 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 overflow-hidden flex flex-col">

                <div class="absolute top-0 left-0 right-0 h-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                     style="background: linear-gradient(to right, #f97316, #facc15);"></div>

                {{-- HEADER CARD --}}
                <div class="flex items-start gap-2.5 mb-3">
                    <div class="w-10 h-10 rounded-lg overflow-hidden shrink-0 flex items-center justify-center"
                         style="background:#fff7ed; border:1.5px solid #fed7aa;">
                        @if ($eo->logo)
                            <img src="{{ asset($eo->logo) }}" alt="{{ $eo->nama_badan_usaha }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-[11px] font-bold" style="color:#f97316;">
                                {{ strtoupper(substr($eo->nama_badan_usaha, 0, 2)) }}
                            </span>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-sm truncate" style="color:#111827;">{{ $eo->nama_badan_usaha }}</p>
                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                            <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full {{ $statusStyle[0] }} {{ $statusStyle[1] }}">
                                {{ $statusStyle[2] }}
                            </span>
                            @if ($eo->is_locked)
                                <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full bg-red-100 text-red-600">🔒 Locked</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- MINI PROGRESS BAR --}}
                @if ($gmv > 0)
                    @php $debtRatio = min(100, round(($eo->outstanding_debt / max($gmv, 1)) * 100)); @endphp
                    <div class="w-full h-1.5 rounded-full overflow-hidden mb-3" style="background:#f3f4f6;">
                        <div class="h-full rounded-full" style="width: {{ 100 - $debtRatio }}%; background: linear-gradient(to right, #f97316, #facc15);"></div>
                    </div>
                @endif

                {{-- METRICS --}}
                <div class="grid grid-cols-2 gap-x-3 gap-y-2 pt-2 mt-auto" style="border-top:1px dashed #e5e7eb;">
                    <div>
                        <p class="text-[9px] uppercase tracking-wide font-semibold" style="color:#9ca3af;">GMV</p>
                        <p class="font-bold text-xs truncate" style="color:#111827;">Rp {{ number_format($gmv, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] uppercase tracking-wide font-semibold" style="color:#9ca3af;">Saldo</p>
                        <p class="font-bold text-xs truncate" style="color:#f97316;">Rp {{ number_format($eo->wallet_balance, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] uppercase tracking-wide font-semibold" style="color:#9ca3af;">Utang</p>
                        <p class="font-bold text-xs truncate" style="color: {{ $eo->outstanding_debt > 0 ? '#ef4444' : '#111827' }};">
                            Rp {{ number_format($eo->outstanding_debt, 0, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[9px] uppercase tracking-wide font-semibold" style="color:#9ca3af;">Event</p>
                        <p class="font-bold text-xs truncate" style="color:#111827;">{{ $eo->total_event }} ({{ $eo->total_event_approved }} apv)</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full">
                <div class="card rounded-xl py-12 flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-3" style="background:#fff7ed;">
                        <svg class="w-6 h-6" style="color:#f97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium" style="color:#374151;">Belum ada data EO yang cocok.</p>
                    <p class="text-xs mt-1" style="color:#9ca3af;">Coba ubah kata kunci pencarian atau filter status.</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- ===================== PAGINATION ===================== --}}
    <div class="mt-6 pagination-wrapper flex justify-center">
        {{ $eoList->links() }}
    </div>
</div>

@push('styles')
<style>
    .pagination-wrapper nav [aria-current="page"] span {
        background-color: #f97316 !important;
        border-color: #f97316 !important;
    }
    .pagination-wrapper nav a:hover { color: #f97316 !important; }
    .pagination-wrapper nav a:focus { box-shadow: 0 0 0 0.2rem rgba(249, 115, 22, 0.25) !important; }
</style>
@endpush
@endsection