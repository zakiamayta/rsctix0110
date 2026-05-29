@extends('layouts.eo')

@section('title', 'Dashboard EO')

@section('content')

{{-- HEADER --}}
<div class="mb-8">

    <h1 class="text-2xl font-bold"
        style="font-family:'Sora',sans-serif; color:#1A1208;">

        Dashboard EO

    </h1>

    <p class="text-sm mt-1 text-[#7A6E66]">
        Ringkasan performa bisnis event organizer
    </p>

</div>

{{-- MAIN STATS --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

    {{-- TOTAL TIKET --}}
    <div class="bg-white rounded-2xl p-5 border border-[#EDE8E3]">

        <p class="text-xs uppercase font-bold tracking-wider text-gray-400 mb-2">
            Total Tiket Terjual
        </p>

        <h2 class="text-3xl font-bold text-[#1A1208]">
            {{ number_format($totalTicketsSold) }}
        </h2>

    </div>

    {{-- TOTAL MERCH --}}
    <div class="bg-white rounded-2xl p-5 border border-[#EDE8E3]">

        <p class="text-xs uppercase font-bold tracking-wider text-gray-400 mb-2">
            Total Merch Terjual
        </p>

        <h2 class="text-3xl font-bold text-[#1A1208]">
            {{ number_format($totalMerchSold) }}
        </h2>

    </div>

    {{-- TOTAL REVENUE --}}
    <div class="bg-[#E8470A] text-white rounded-2xl p-5">

        <p class="text-xs uppercase font-bold tracking-wider opacity-70 mb-2">
            Total Pendapatan
        </p>

        <h2 class="text-3xl font-bold">
            Rp {{ number_format($totalRevenue) }}
        </h2>

        {{-- BREAKDOWN --}}
        <div class="mt-4 space-y-2 text-xs">

            <div class="flex justify-between opacity-80">
                <span>Pendapatan Tiket</span>
                <span>
                    Rp {{ number_format($ticketRevenue) }}
                </span>
            </div>

            <div class="flex justify-between opacity-80">
                <span>Pendapatan Merch</span>
                <span>
                    Rp {{ number_format($merchRevenue) }}
                </span>
            </div>

        </div>

    </div>

    {{-- EVENT AKTIF --}}
    <div class="bg-white rounded-2xl p-5 border border-[#EDE8E3]">

        <p class="text-xs uppercase font-bold tracking-wider text-gray-400 mb-2">
            Event Aktif
        </p>

        <h2 class="text-3xl font-bold text-[#1A1208]">
            {{ $activeEvents }}
        </h2>

    </div>

</div>

{{-- SECOND STATS --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

    {{-- TODAY REVENUE --}}
    <div class="bg-white rounded-2xl p-5 border border-[#EDE8E3]">

        <p class="text-xs uppercase font-bold tracking-wider text-gray-400 mb-2">
            Pendapatan Hari Ini
        </p>

        <h2 class="text-2xl font-bold text-[#1A1208]">
            Rp {{ number_format($todayRevenue) }}
        </h2>

    </div>

    {{-- SUCCESS --}}
    <div class="bg-white rounded-2xl p-5 border border-[#EDE8E3]">

        <p class="text-xs uppercase font-bold tracking-wider text-gray-400 mb-2">
            Transaksi Berhasil
        </p>

        <h2 class="text-2xl font-bold text-green-600">
            {{ $successTransactions }}
        </h2>

    </div>

    {{-- PENDING --}}
    <div class="bg-white rounded-2xl p-5 border border-[#EDE8E3]">

        <p class="text-xs uppercase font-bold tracking-wider text-gray-400 mb-2">
            Pending / Belum Bayar
        </p>

        <h2 class="text-2xl font-bold text-yellow-500">
            {{ $pendingTransactions }}
        </h2>

    </div>

    {{-- TODAY SALES --}}
    <div class="bg-white rounded-2xl p-5 border border-[#EDE8E3]">

        <p class="text-xs uppercase font-bold tracking-wider text-gray-400 mb-2">
            Penjualan Hari Ini
        </p>

        <h2 class="text-2xl font-bold text-[#E8470A]">
            {{ $todaySales }}
        </h2>

    </div>

</div>

{{-- SALES PERFORMANCE --}}
<div class="bg-white rounded-2xl border border-[#EDE8E3] p-6 mb-8">

    <div class="flex justify-between items-center mb-6">

        <div>

            <h2 class="text-lg font-bold"
                style="font-family:'Sora',sans-serif; color:#1A1208;">

                Performa Penjualan

            </h2>

            <p class="text-xs text-gray-500 mt-1">
                Pendapatan tiket + merch 7 hari terakhir
            </p>

        </div>

    </div>

    <div class="space-y-4">

        @foreach($salesChart as $chart)

            @php

                $maxChart = max(array_column($salesChart, 'total'));

                $width = $maxChart > 0
                    ? ($chart['total'] / $maxChart) * 100
                    : 0;

            @endphp

            <div>

                <div class="flex justify-between text-xs mb-1">

                    <span>
                        {{ $chart['date'] }}
                    </span>

                    <span>
                        Rp {{ number_format($chart['total']) }}
                    </span>

                </div>

                <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">

                    <div class="h-full rounded-full bg-[#E8470A]"
                         style="width: {{ $width }}%">
                    </div>

                </div>

            </div>

        @endforeach

    </div>

</div>

{{-- RECENT TRANSACTIONS --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- TRANSAKSI TIKET --}}
    <div class="bg-white rounded-2xl border border-[#EDE8E3] overflow-hidden">

        <div class="p-5 border-b border-[#EDE8E3]">

            <h2 class="font-bold text-lg"
                style="font-family:'Sora',sans-serif; color:#1A1208;">

                Transaksi Tiket Terbaru

            </h2>

        </div>

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-[#FAF8F6]">

                    <tr class="text-xs uppercase tracking-wider text-gray-500">

                        <th class="px-5 py-3 text-left">
                            Email
                        </th>

                        <th class="px-5 py-3 text-left">
                            Event
                        </th>

                        <th class="px-5 py-3 text-center">
                            Total
                        </th>

                        <th class="px-5 py-3 text-center">
                            Status
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($recentTicketTransactions as $trx)

                    <tr class="border-t border-[#F1ECE8]">

                        <td class="px-5 py-4 text-sm">
                            {{ $trx->email }}
                        </td>

                        <td class="px-5 py-4 text-sm">
                            {{ $trx->event->title ?? '-' }}
                        </td>

                        <td class="px-5 py-4 text-center text-sm font-bold">
                            Rp {{ number_format($trx->total_amount) }}
                        </td>

                        <td class="px-5 py-4 text-center">

                            @if($trx->payment_status == 'paid')

                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                    PAID
                                </span>

                            @else

                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">
                                    UNPAID
                                </span>

                            @endif

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="4"
                            class="text-center py-10 text-gray-400">

                            Belum ada transaksi tiket

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    {{-- TRANSAKSI MERCH --}}
    <div class="bg-white rounded-2xl border border-[#EDE8E3] overflow-hidden">

        <div class="p-5 border-b border-[#EDE8E3]">

            <h2 class="font-bold text-lg"
                style="font-family:'Sora',sans-serif; color:#1A1208;">

                Transaksi Merch Terbaru

            </h2>

        </div>

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-[#FAF8F6]">

                    <tr class="text-xs uppercase tracking-wider text-gray-500">

                        <th class="px-5 py-3 text-left">
                            Email
                        </th>

                        <th class="px-5 py-3 text-left">
                            Produk
                        </th>

                        <th class="px-5 py-3 text-center">
                            Total
                        </th>

                        <th class="px-5 py-3 text-center">
                            Status
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($recentMerchTransactions as $trx)

                    <tr class="border-t border-[#F1ECE8]">

                        <td class="px-5 py-4 text-sm">
                            {{ $trx->email }}
                        </td>

                        <td class="px-5 py-4 text-sm">

                            {{
                                optional(
                                    optional($trx->details->first())->product
                                )->name
                                ?? '-'
                            }}

                        </td>

                        <td class="px-5 py-4 text-center text-sm font-bold">
                            Rp {{ number_format($trx->total_amount) }}
                        </td>

                        <td class="px-5 py-4 text-center">

                            @if($trx->payment_status == 'paid')

                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                    PAID
                                </span>

                            @else

                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">
                                    UNPAID
                                </span>

                            @endif

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td colspan="4"
                            class="text-center py-10 text-gray-400">

                            Belum ada transaksi merch

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection