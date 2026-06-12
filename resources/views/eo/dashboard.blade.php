@extends('layouts.eo')

@section('title', 'Dashboard EO')

@section('content')

{{-- HEADER --}}
<div class="mb-4">
    <h1 class="text-xl font-bold" style="font-family:'Sora',sans-serif; color:#1A1208;">
        Dashboard EO
    </h1>
    <p class="text-xs mt-0.5 text-[#7A6E66]">
        Ringkasan performa bisnis event organizer
    </p>
</div>

{{-- ALL STATS (Digabungkan dalam 1 grid yang padat) --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">

    {{-- TOTAL TIKET --}}
    <div class="bg-white rounded-xl p-3.5 border border-[#EDE8E3] flex flex-col justify-center">
        <p class="text-[10px] uppercase font-bold tracking-wider text-gray-400 mb-1">
            Total Tiket Terjual
        </p>
        <h2 class="text-2xl font-bold text-[#1A1208] leading-none">
            {{ number_format($totalTicketsSold) }}
        </h2>
    </div>

    {{-- TOTAL MERCH --}}
    <div class="bg-white rounded-xl p-3.5 border border-[#EDE8E3] flex flex-col justify-center">
        <p class="text-[10px] uppercase font-bold tracking-wider text-gray-400 mb-1">
            Total Merch Terjual
        </p>
        <h2 class="text-2xl font-bold text-[#1A1208] leading-none">
            {{ number_format($totalMerchSold) }}
        </h2>
    </div>

    {{-- TOTAL REVENUE --}}
    <div class="bg-[#E8470A] text-white rounded-xl p-3.5 row-span-2 flex flex-col justify-between">
        <div>
            <p class="text-[10px] uppercase font-bold tracking-wider opacity-80 mb-1">
                Total Pendapatan
            </p>
            <h2 class="text-2xl font-bold leading-none">
                Rp {{ number_format($totalRevenue) }}
            </h2>
        </div>
        {{-- BREAKDOWN --}}
        <div class="mt-3 space-y-1.5 text-[10px] border-t border-white/20 pt-2">
            <div class="flex justify-between opacity-90">
                <span>Tiket</span>
                <span class="font-semibold">Rp {{ number_format($ticketRevenue) }}</span>
            </div>
            <div class="flex justify-between opacity-90">
                <span>Merch</span>
                <span class="font-semibold">Rp {{ number_format($merchRevenue) }}</span>
            </div>
        </div>
    </div>

    {{-- EVENT AKTIF --}}
    <div class="bg-white rounded-xl p-3.5 border border-[#EDE8E3] flex flex-col justify-center">
        <p class="text-[10px] uppercase font-bold tracking-wider text-gray-400 mb-1">
            Event Aktif
        </p>
        <h2 class="text-2xl font-bold text-[#1A1208] leading-none">
            {{ $activeEvents }}
        </h2>
    </div>

    {{-- TODAY REVENUE --}}
    <div class="bg-white rounded-xl p-3.5 border border-[#EDE8E3] flex flex-col justify-center">
        <p class="text-[10px] uppercase font-bold tracking-wider text-gray-400 mb-1">
            Pendapatan Hari Ini
        </p>
        <h2 class="text-xl font-bold text-[#1A1208] leading-none">
            Rp {{ number_format($todayRevenue) }}
        </h2>
    </div>

    {{-- SUCCESS --}}
    <div class="bg-white rounded-xl p-3.5 border border-[#EDE8E3] flex flex-col justify-center">
        <p class="text-[10px] uppercase font-bold tracking-wider text-gray-400 mb-1">
            Transaksi Berhasil
        </p>
        <h2 class="text-xl font-bold text-green-600 leading-none">
            {{ $successTransactions }}
        </h2>
    </div>

    {{-- PENDING --}}
    <div class="bg-white rounded-xl p-3.5 border border-[#EDE8E3] flex flex-col justify-center">
        <p class="text-[10px] uppercase font-bold tracking-wider text-gray-400 mb-1">
            Pending / Unpaid
        </p>
        <h2 class="text-xl font-bold text-yellow-500 leading-none">
            {{ $pendingTransactions }}
        </h2>
    </div>

    {{-- TODAY SALES --}}
    <div class="bg-white rounded-xl p-3.5 border border-[#EDE8E3] flex flex-col justify-center">
        <p class="text-[10px] uppercase font-bold tracking-wider text-gray-400 mb-1">
            Penjualan Hari Ini
        </p>
        <h2 class="text-xl font-bold text-[#E8470A] leading-none">
            {{ $todaySales }}
        </h2>
    </div>

</div>

{{-- LAYOUT BAWAH: CHART & TRANSAKSI --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-5">

    {{-- SALES PERFORMANCE (Dikompakkan ke sisi kiri jika di layar besar) --}}
    <div class="bg-white rounded-xl border border-[#EDE8E3] p-4 xl:col-span-1">
        <div class="mb-4">
            <h2 class="text-sm font-bold" style="font-family:'Sora',sans-serif; color:#1A1208;">
                Performa Penjualan
            </h2>
            <p class="text-[10px] text-gray-500 mt-0.5">
                Pendapatan tiket + merch 7 hari terakhir
            </p>
        </div>

        <div class="space-y-2.5">
            @foreach($salesChart as $chart)
                @php
                    $maxChart = max(array_column($salesChart, 'total'));
                    $width = $maxChart > 0 ? ($chart['total'] / $maxChart) * 100 : 0;
                @endphp
                <div>
                    <div class="flex justify-between text-[10px] mb-1 font-medium">
                        <span class="text-gray-600">{{ $chart['date'] }}</span>
                        <span class="text-[#1A1208]">Rp {{ number_format($chart['total']) }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                        <div class="h-full rounded-full bg-[#E8470A]" style="width: {{ $width }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- RECENT TRANSACTIONS (Digabung ke 1 kolom dengan grid di dalamnya) --}}
    <div class="xl:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
        
        {{-- TRANSAKSI TIKET --}}
        <div class="bg-white rounded-xl border border-[#EDE8E3] overflow-hidden flex flex-col">
            <div class="px-4 py-3 border-b border-[#EDE8E3] bg-[#FAF8F6]">
                <h2 class="font-bold text-sm" style="font-family:'Sora',sans-serif; color:#1A1208;">
                    Transaksi Tiket Terbaru
                </h2>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="w-full">
                    <thead class="bg-white border-b border-[#EDE8E3]">
                        <tr class="text-[10px] uppercase tracking-wide text-gray-400">
                            <th class="px-3 py-2 text-left font-semibold">Email & Event</th>
                            <th class="px-3 py-2 text-right font-semibold">Total</th>
                            <th class="px-3 py-2 text-center font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTicketTransactions as $trx)
                        <tr class="border-b border-[#F9F7F5] last:border-0 hover:bg-[#FAF8F6] transition-colors">
                            <td class="px-3 py-2 text-xs">
                                <div class="font-medium text-[#1A1208]">{{ $trx->email }}</div>
                                <div class="text-[10px] text-gray-500 truncate max-w-[120px]">{{ $trx->event->title ?? '-' }}</div>
                            </td>
                            <td class="px-3 py-2 text-right text-xs font-bold text-[#1A1208]">
                                Rp {{ number_format($trx->total_amount) }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if($trx->payment_status == 'paid')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700">PAID</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-700">UNPAID</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-6 text-xs text-gray-400">Belum ada transaksi tiket</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TRANSAKSI MERCH --}}
        <div class="bg-white rounded-xl border border-[#EDE8E3] overflow-hidden flex flex-col">
            <div class="px-4 py-3 border-b border-[#EDE8E3] bg-[#FAF8F6]">
                <h2 class="font-bold text-sm" style="font-family:'Sora',sans-serif; color:#1A1208;">
                    Transaksi Merch Terbaru
                </h2>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="w-full">
                    <thead class="bg-white border-b border-[#EDE8E3]">
                        <tr class="text-[10px] uppercase tracking-wide text-gray-400">
                            <th class="px-3 py-2 text-left font-semibold">Email & Produk</th>
                            <th class="px-3 py-2 text-right font-semibold">Total</th>
                            <th class="px-3 py-2 text-center font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMerchTransactions as $trx)
                        <tr class="border-b border-[#F9F7F5] last:border-0 hover:bg-[#FAF8F6] transition-colors">
                            <td class="px-3 py-2 text-xs">
                                <div class="font-medium text-[#1A1208]">{{ $trx->email }}</div>
                                <div class="text-[10px] text-gray-500 truncate max-w-[120px]">
                                    {{ optional(optional($trx->details->first())->product)->name ?? '-' }}
                                </div>
                            </td>
                            <td class="px-3 py-2 text-right text-xs font-bold text-[#1A1208]">
                                Rp {{ number_format($trx->total_amount) }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if($trx->payment_status == 'paid')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700">PAID</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-700">UNPAID</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-6 text-xs text-gray-400">Belum ada transaksi merch</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

@endsection