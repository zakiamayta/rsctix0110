@extends('layouts.eo')

@section('title', 'Detail Transparansi Kloter')

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
</style>

<div class="rsc-wrap">
    {{-- BACK TO LIST NAVIGATION --}}
    <div class="mb-4">
        <a href="{{ route('eo.refunds.index') }}" class="inline-flex items-center text-xs font-bold text-[#7A6E66] hover:text-[#f97316] transition-colors gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Daftar Manajemen Finansial
        </a>
    </div>

    {{-- CLUSTER INFO CARD --}}
    <div class="rsc-card p-5 mb-5 bg-[#FFF0EB] border-[#E2DBD4] flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <span class="text-[9px] font-bold px-2 py-0.5 rounded bg-[#f97316] text-white tracking-widest uppercase mb-1.5 inline-block">Kloter Detail Summary</span>
            <h2 class="text-xl font-bold rsc-title leading-tight">{{ $batch->name }}</h2>
            <p class="text-xs text-[#7A6E66] mt-0.5 font-medium">Event: {{ $batch->event->title ?? '-' }}</p>
        </div>
        <div class="text-left md:text-right">
            <p class="text-[10px] uppercase font-bold tracking-wider text-[#7A6E66] mb-0.5">Total Beban Alokasi</p>
            <h3 class="text-2xl font-extrabold text-[#1A1208]">
                Rp {{ number_format($totalRefundBatch, 0, ',', '.') }}
            </h3>
        </div>
    </div>

    {{-- LIST TABLE AUDIT PRIVACY --}}
    <div class="rsc-card overflow-hidden">
        <div class="p-4 border-b border-[#EDE8E3] bg-white">
            <h3 class="text-sm font-bold text-[#1A1208] flex items-center gap-2">
                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Rincian Riwayat Transparansi Pembeli (Rekening Terproteksi)
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="rsc-table">
                <thead>
                    <tr>
                        <th class="text-center w-12">No</th>
                        <th class="text-left">ID Klaim</th>
                        <th class="text-left">Kode Unik Tiket</th>
                        <th class="text-left">Tujuan Bank</th>
                        <th class="text-left">Nama Pemegang Akun</th>
                        <th class="text-left">Uang Dikembalikan</th>
                        <th class="text-left">Tanggal Log</th>
                        <th class="text-center">Status Realisasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($refunds as $index => $refund)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="text-center text-gray-400 font-bold text-xs">{{ $index + 1 }}</td>
                        <td class="font-bold text-xs">#REF-{{ $refund->id }}</td>
                        <td class="font-mono text-xs text-gray-500">{{ $refund->kode_unik ?? '-' }}</td>
                        <td>
                            <span class="px-1.5 py-0.5 text-[11px] font-bold rounded bg-gray-100 text-gray-700">
                                {{ strtoupper($refund->bank_name) }}
                            </span>
                        </td>
                        <td class="font-medium text-gray-700">{{ $refund->account_name }}</td>
                        <td class="font-bold text-[#1A1208]">Rp {{ number_format($refund->grand_total_refunded) }}</td>
                        <td class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($refund->created_at)->translatedFormat('d M Y, H:i') }}</td>
                        <td class="text-center">
                            @if($refund->status === 'transferred')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700">SUCCESS</span>
                            @elseif($refund->status === 'rejected')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">REJECTED</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-700">PENDING</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-xs text-gray-400">Belum ada penonton yang terdaftar di dalam kloter gelombang ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection