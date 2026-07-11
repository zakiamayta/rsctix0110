@extends('layouts.eo')

@section('title', 'Manajemen Refund & Keuangan')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap');

    .rsc-wrap * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
    .rsc-title { font-family: 'Sora', sans-serif; color: #1A1208; }
    
    .rsc-card {
        background: #FFFFFF;
        border: 1px solid #EDE8E3;
        border-radius: 14px;
    }
    .rsc-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .rsc-table th {
        background: #F2EEE9;
        color: #7A6E66;
        font-family: 'Sora', sans-serif;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 12px 16px;
        border-bottom: 1px solid #E2DBD4;
    }
    .rsc-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #EDE8E3;
        color: #1A1208;
        font-size: 13px;
    }
    .rsc-table tr:last-child td {
        border-bottom: none;
    }
    .btn-rsc-outline {
        border: 1px solid #E2DBD4;
        color: #5A4F46;
        font-size: 12px;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.2s;
        background: #FFFFFF;
    }
    .btn-rsc-outline:hover {
        background: #FDF5F2;
        border-color: #F97040;
        color: #f97316;
    }
</style>

<div class="rsc-wrap">
    {{-- HEADER --}}
    <div class="mb-5">
        <h1 class="text-xl font-bold rsc-title">
            Manajemen Refund & Keuangan
        </h1>
        <p class="text-xs mt-0.5 text-[#7A6E66]">
            Pantau transparansi pemrosesan kloter pengembalian dana tiket penonton serta laporan finansial
        </p>
    </div>

    {{-- STATS GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {{-- CARD 1: TOTAL BATCH --}}
        <div class="rsc-card p-4 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold tracking-wider text-gray-400 mb-1">Total Kloter Refund</p>
                <h2 class="text-2xl font-bold text-[#1A1208] leading-none">{{ $batches->count() }} Kloter</h2>
            </div>
            <div class="p-3 bg-gray-50 rounded-xl text-[#7A6E66]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v6a2 2 0 012-2m14-8V9a2 2 0 00-2-2H5a2 2 0 00-2 2v2"></path></svg>
            </div>
        </div>

        {{-- CARD 2: UTANG EO (SINKRON DENGAN KOLOM remaining_debt & status DATABASE) --}}
        <div class="rsc-card p-4 flex items-center justify-between border-l-4" style="border-l-color: #f97316;">
            <div>
                <p class="text-[10px] uppercase font-bold tracking-wider text-gray-400 mb-1">Minus Saldo Belum Lunas (Utang)</p>
                <h2 class="text-2xl font-bold text-[#f97316] leading-none">
                    Rp {{ number_format($myDebts->filter(function($debt) { 
                        return strtolower($debt->status) === 'unpaid' || strtolower($debt->status) === 'partially_paid'; 
                    })->sum('remaining_debt'), 0, ',', '.') }}
                </h2>
            </div>
            <div class="p-3 bg-red-50 rounded-xl text-[#f97316]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
        </div>
    </div>

    {{-- SECTION 1: BATCH TABLE --}}
    <div class="rsc-card mb-6 overflow-hidden">
        <div class="p-4 border-b border-[#EDE8E3] bg-[#FDF5F2]">
            <h3 class="text-sm font-bold text-[#1A1208] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Antrean Kloter Batch Refund
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="rsc-table">
                <thead>
                    <tr>
                        <th class="text-left">Nama Kloter</th>
                        <th class="text-left">Event Terkait</th>
                        <th class="text-center">Total Pengajuan</th>
                        <th class="text-left">Status Pendaftaran</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="font-bold text-[#1A1208]">{{ $batch->name }}</td>
                        <td class="text-gray-600 font-medium">{{ $batch->event->title ?? '-' }}</td>
                        <td class="text-center font-semibold">{{ $batch->total_pengajuan ?? 0 }} Tiket</td>
                        <td>
                            @if(strtolower($batch->status) === 'active')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-[#f97316]">TERBUKA</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-500">DITUTUP</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('eo.refunds.show', $batch->id) }}" class="btn-rsc-outline px-3 py-1.5 inline-block text-center">
                                Detail Transparansi
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-8 text-xs text-gray-400">Belum tersedia data kloter refund aktif untuk saat ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- SECTION 2: DEBT TABLE (SINKRON DATA KOLOM SKELETON DB) --}}
    <div class="rsc-card overflow-hidden">
        <div class="p-4 border-b border-[#EDE8E3] bg-gray-50">
            <h3 class="text-sm font-bold text-[#1A1208] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500"></span> Log Kompensasi Pemotongan & Utang Minus Saldo
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="rsc-table">
                <thead>
                    <tr>
                        <th class="text-left">ID Tagihan</th>
                        <th class="text-left">Nama Event</th>
                        <th class="text-left">Total Utang Awal</th>
                        <th class="text-left">Sisa Tertunggak</th>
                        <th class="text-center">Status Keuangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($myDebts as $debt)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="font-mono text-xs text-gray-500">#DEBT-{{ $debt->id }}</td>
                        <td class="font-medium">{{ $debt->event->title ?? '-' }}</td>
                        <td class="font-semibold text-gray-700">Rp {{ number_format($debt->total_debt, 0, ',', '.') }}</td>
                        <td class="font-bold text-red-600">Rp {{ number_format($debt->remaining_debt, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if(strtolower($debt->status) === 'paid')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700">LUNAS</span>
                            @elseif(strtolower($debt->status) === 'partially_paid')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-700">CICILAN</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">TERTUNGGAK</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-8 text-xs text-gray-400">Selamat! Tidak ditemukan riwayat tagihan minus saldo pada akun Anda.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection