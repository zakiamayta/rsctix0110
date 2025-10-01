@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="{{ asset('css/admin_dashboard.css') }}" rel="stylesheet">

    <style>

    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 min-h-screen">

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header Section -->
    <div class="mb-8 animate-fade-in-up">
        <h2 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 mb-2">
            Dashboard Transaksi
        </h2>
        <p class="text-gray-600 text-sm">Kelola dan monitoring seluruh transaksi tiket event</p>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Total Uang Masuk -----}}
        <div class="glass-effect rounded-2xl p-6 hover-lift card-shadow-lg animate-fade-in-up" style="animation-delay: 0.1s">
            <div class="flex items-center justify-between">
                <div class="flex-1"> 
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-2 h-2 rounded-full bg-blue-500 badge-pulse"></div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Uang Masuk</h3>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 mb-1">
                        Rp{{ number_format($totalPaidAmount, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-500">Dari pembayaran berhasil</p>
                </div>
                <div class="gradient-blue w-16 h-16 flex items-center justify-center rounded-2xl shadow-lg transform rotate-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.592 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.592-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Paid --}}
        <div class="glass-effect rounded-2xl p-6 hover-lift card-shadow-lg animate-fade-in-up" style="animation-delay: 0.2s">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-2 h-2 rounded-full bg-green-500 badge-pulse"></div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pembayaran Berhasil</h3>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 mb-1">{{ $totalPaidCount }}</p>
                    <p class="text-xs text-gray-500">Transaksi selesai</p>
                </div>
                <div class="gradient-green w-16 h-16 flex items-center justify-center rounded-2xl shadow-lg transform -rotate-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Unpaid --}}
        <div class="glass-effect rounded-2xl p-6 hover-lift card-shadow-lg animate-fade-in-up" style="animation-delay: 0.3s">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-2 h-2 rounded-full bg-red-500 badge-pulse"></div>
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Belum Dibayar</h3>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 mb-1">{{ $totalUnpaidCount }}</p>
                    <p class="text-xs text-gray-500">Menunggu pembayaran</p>
                </div>
                <div class="gradient-red w-16 h-16 flex items-center justify-center rounded-2xl shadow-lg transform rotate-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="glass-effect rounded-2xl p-6 card-shadow-lg mb-6 animate-slide-in">
        <div class="flex items-center gap-2 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            <h3 class="text-lg font-bold text-gray-900">Filter & Pencarian</h3>
        </div>
        
        <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            {{-- Pilih Event --}}
            <div>
                <label for="event_id" class="block text-xs font-semibold text-gray-700 mb-1.5">Pilih Event</label>
                <select id="event_id" name="event_id"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    <option value="">Semua Event</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                            {{ $event->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Tanggal Mulai --}}
            <div>
                <label for="start_date" class="block text-xs font-semibold text-gray-700 mb-1.5">Tanggal Mulai</label>
                <input type="date" id="start_date" name="start_date"
                       value="{{ request('start_date') }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            </div>

            {{-- Tanggal Selesai --}}
            <div>
                <label for="end_date" class="block text-xs font-semibold text-gray-700 mb-1.5">Tanggal Selesai</label>
                <input type="date" id="end_date" name="end_date"
                       value="{{ request('end_date') }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            </div>

            {{-- Status Pembayaran --}}
            <div>
                <label for="payment_status" class="block text-xs font-semibold text-gray-700 mb-1.5">Status Pembayaran</label>
                <select id="payment_status" name="payment_status"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    <option value="">Semua Status</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>

            {{-- Urutkan Berdasarkan --}}
            <div>
                <label for="sort_by" class="block text-xs font-semibold text-gray-700 mb-1.5">Urutkan Berdasarkan</label>
                <select id="sort_by" name="sort_by"
                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    <option value="">Urutkan</option>
                    <option value="event_title" {{ request('sort_by') === 'event_title' ? 'selected' : '' }}>Judul Acara</option>
                    <option value="email" {{ request('sort_by') === 'email' ? 'selected' : '' }}>Email</option>
                    <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Nama</option>
                    <option value="payment_status" {{ request('sort_by') === 'payment_status' ? 'selected' : '' }}>Status</option>
                    <option value="checkout_time" {{ request('sort_by') === 'checkout_time' ? 'selected' : '' }}>Waktu Checkout</option>
                </select>
            </div>

            {{-- Pencarian --}}
            <div>
                <label for="q" class="block text-xs font-semibold text-gray-700 mb-1.5">Pencarian</label>
                <input type="text" id="q" name="q" placeholder="Cari email/nama"
                       value="{{ request('q') }}"
                       class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"/>
            </div>

            {{-- Tombol --}}
            <div class="lg:col-span-6 flex flex-wrap gap-3 pt-2">
                <button type="submit"
                        class="btn-ripple px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-semibold text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Terapkan Filter
                </button>
                <a href="{{ route('admin.dashboard') }}"
                   class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold text-sm transition-all duration-300 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Action Buttons --}}
    <div class="flex flex-wrap gap-3 mb-6 animate-slide-in">
        <a href="{{ route('admin.dashboard.export.excel', request()->query()) }}" 
           class="btn-ripple px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg font-semibold text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export Excel
        </a>
        <a href="{{ route('admin.dashboard.export.pdf', request()->query()) }}" 
           class="btn-ripple px-5 py-2.5 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-lg font-semibold text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            Export PDF
        </a>
        <form action="{{ route('admin.transactions.regenerate-qr') }}" method="POST"
              onsubmit="return confirm('Apakah Anda yakin ingin regenerate semua QR Code? Ini akan replace file lama.')" class="inline-block">
            @csrf
            <button type="submit"
                    class="btn-ripple px-5 py-2.5 gradient-purple hover:opacity-90 text-white rounded-lg font-semibold text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Regenerate Semua QR
            </button>
        </form>
    </div>

    {{-- Table Section --}}
    <div class="glass-effect rounded-2xl overflow-hidden card-shadow-lg animate-scale-in">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">No.</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Judul Acara</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Email</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Waktu Checkout</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Waktu Pembayaran</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Total</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">QR Code</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Jumlah Tiket</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse ($transactions as $transaction)
                        <tr class="table-row-hover">
                            <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-700 font-medium">{{ $transaction->event->title ?? '-' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-600">{{ $transaction->email }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-600">{{ $transaction->checkout_time }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-600">{{ $transaction->paid_time ?? '-' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                    {{ $transaction->payment_status === 'paid' ? 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-700' : 'bg-gradient-to-r from-red-100 to-pink-100 text-red-700' }}">
                                    @if($transaction->payment_status === 'paid')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @endif
                                    {{ ucfirst($transaction->payment_status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm font-bold text-gray-900">Rp{{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                @if($transaction->qr_code)
                                    <a href="{{ route('guests.qr', $transaction->kode_unik) }}" target="_blank"
                                       class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg text-xs font-semibold shadow-md hover:shadow-lg transition-all duration-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                        </svg>
                                        Lihat QR
                                    </a>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold">
                                    {{ $transaction->attendees->count() }} tiket
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <div class="flex flex-col space-y-2">
                                    <button onclick="showDetail({{ $transaction->id }})"
                                            class="inline-flex items-center justify-center px-3 py-1.5 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white rounded-lg text-xs font-semibold shadow-md hover:shadow-lg transition-all duration-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Detail
                                    </button>
                                    <form id="regenerate-form-{{ $transaction->id }}"
                                          action="{{ route('admin.transactions.regenerateQR', $transaction->id) }}"
                                          method="POST" class="inline-block">
                                        @csrf
                                        <button type="button"
                                                onclick="openConfirmModal({{ $transaction->id }})"
                                                class="w-full inline-flex items-center justify-center px-3 py-1.5 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white rounded-lg text-xs font-semibold shadow-md hover:shadow-lg transition-all duration-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Regenerate QR
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-gray-500 font-medium">Tidak ada transaksi yang ditemukan</p>
                                    <p class="text-gray-400 text-sm mt-1">Coba ubah filter atau reset pencarian</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>

{{-- Modal Detail --}}
<div id="detailModal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="glass-effect rounded-2xl shadow-2xl max-w-3xl w-full transform transition-all duration-300 scale-95 opacity-0" id="modalContentWrapper">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 rounded-t-2xl">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Detail Pembeli Tiket
            </h3>
        </div>
        <div id="modalContent" class="p-6 max-h-96 overflow-y-auto"></div>
        <div class="px-6 pb-6">
            <button onclick="closeModal()" 
                    class="w-full bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl">
                Tutup
            </button>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi --}}
<div id="confirmModal"
     class="hidden fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center z-50 opacity-0 transition-opacity duration-300 p-4">
    <div class="glass-effect rounded-2xl shadow-2xl p-8 max-w-md w-full transform scale-95 transition-transform duration-300">
        <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2 text-center">Konfirmasi</h2>
        <p class="text-gray-600 mb-6 text-center">Yakin mau generate ulang QR untuk transaksi ini? QR code lama akan diganti.</p>
        <div class="flex gap-3">
            <button onclick="closeConfirmModal()"
                    class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-all duration-300">
                Batal
            </button>
            <button id="confirmButton"
                    class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300">
                Ya, Generate
            </button>
        </div>
    </div>
</div>

{{-- Popup Notifikasi Sukses / Error --}}
@if(session('success') || session('error'))
    <div id="notificationPopup"
         class="fixed top-5 right-5 z-50 max-w-md
                {{ session('success') ? 'bg-gradient-to-r from-green-500 to-emerald-500' : 'bg-gradient-to-r from-red-500 to-pink-500' }}
                text-white px-6 py-4 rounded-xl shadow-2xl opacity-0 transition-all duration-500 transform translate-x-full">
        <div class="flex items-center gap-3">
            @if(session('success'))
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            @endif
            <span class="font-semibold">{{ session('success') ?? session('error') }}</span>
        </div>
    </div>
@endif

<script>
    function showDetail(transactionId) {
        const transactions = @json($transactions);
        const transaction = transactions.find(t => t.id === transactionId);

        if (!transaction) return;

        let html = '';
        if (transaction.attendees.length === 0) {
            html = `
                <div class="flex flex-col items-center justify-center py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="text-gray-500 font-medium">Tidak ada data pembeli tiket</p>
                </div>
            `;
        } else {
            html = `
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="px-4 py-3 font-bold text-gray-700 text-left text-xs uppercase tracking-wider">Nama</th>
                            <th class="px-4 py-3 font-bold text-gray-700 text-left text-xs uppercase tracking-wider">Nomor HP</th>
                            <th class="px-4 py-3 font-bold text-gray-700 text-left text-xs uppercase tracking-wider">ID Tiket</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        ${transaction.attendees.map((a, index) => `
                            <tr class="hover:bg-blue-50 transition-colors duration-150">
                                <td class="px-4 py-3 font-medium text-gray-900">${a.name}</td>
                                <td class="px-4 py-3 text-gray-600">${a.phone_number ?? '-'}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 rounded-md text-xs font-semibold">
                                        ${a.ticket_id}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            `;
        }

        document.getElementById('modalContent').innerHTML = html;
        const modal = document.getElementById('detailModal');
        const modalContent = document.getElementById('modalContentWrapper');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 50);
    }

    function closeModal() {
        const modal = document.getElementById('detailModal');
        const modalContent = document.getElementById('modalContentWrapper');
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    let selectedFormId = null;
    const confirmModal = document.getElementById("confirmModal");
    const confirmModalContent = confirmModal.querySelector("div");

    function openConfirmModal(transactionId) {
        selectedFormId = "regenerate-form-" + transactionId;
        confirmModal.classList.remove("hidden");
        confirmModal.classList.add("flex");

        setTimeout(() => {
            confirmModal.classList.remove("opacity-0");
            confirmModalContent.classList.remove("scale-95");
            confirmModalContent.classList.add("scale-100");
        }, 10);
    }

    function closeConfirmModal() {
        confirmModal.classList.add("opacity-0");
        confirmModalContent.classList.remove("scale-100");
        confirmModalContent.classList.add("scale-95");

        setTimeout(() => {
            confirmModal.classList.add("hidden");
            confirmModal.classList.remove("flex");
            selectedFormId = null;
        }, 300);
    }

    document.getElementById("confirmButton").addEventListener("click", function () {
        if (selectedFormId) {
            document.getElementById(selectedFormId).submit();
        }
    });

    confirmModal.addEventListener("click", function(e) {
        if (e.target === confirmModal) {
            closeConfirmModal();
        }
    });

    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            closeConfirmModal();
            closeModal();
        }
    });

    // Popup notifikasi dengan animasi slide-in
    window.addEventListener("DOMContentLoaded", () => {
        const notif = document.getElementById("notificationPopup");
        if (notif) {
            setTimeout(() => {
                notif.classList.remove("opacity-0", "translate-x-full");
                notif.classList.add("opacity-100", "translate-x-0");
            }, 100);
            
            setTimeout(() => {
                notif.classList.remove("opacity-100", "translate-x-0");
                notif.classList.add("opacity-0", "translate-x-full");
            }, 4000);
            
            setTimeout(() => notif.remove(), 4500);
        }
    });
</script>

</body>
@endsection
</html>