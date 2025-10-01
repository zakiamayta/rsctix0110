@extends('layouts.admin')

@section('title', 'Dashboard Merch')
@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Merch</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link href="{{ asset('css/admin_dashboard.css') }}" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 min-h-screen">

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Header -->
<div class="mb-8 animate-fade-in-up">
    <h2 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 mb-2">
        Dashboard Transaksi Merchandise
    </h2>
    <p class="text-gray-600 text-sm">Monitoring transaksi merch, status pembayaran, dan detail pembelian</p>
</div>

{{-- Statistics Cards --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    {{-- Total Uang Masuk --}}
    <div class="glass-effect rounded-2xl p-6 hover-lift card-shadow-lg animate-fade-in-up" style="animation-delay:0.1s">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-2 h-2 rounded-full bg-blue-500 badge-pulse"></div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Uang Masuk</h3>
                </div>
                <p class="text-3xl font-extrabold text-blue-700 mb-1">
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
    <div class="glass-effect rounded-2xl p-6 hover-lift card-shadow-lg animate-fade-in-up" style="animation-delay:0.2s">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-2 h-2 rounded-full bg-green-500 badge-pulse"></div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Paid</h3>
                </div>
                <p class="text-3xl font-extrabold text-green-600 mb-1">{{ $totalPaidCount }}</p>
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
    <div class="glass-effect rounded-2xl p-6 hover-lift card-shadow-lg animate-fade-in-up" style="animation-delay:0.3s">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-2 h-2 rounded-full bg-red-500 badge-pulse"></div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Unpaid</h3>
                </div>
                <p class="text-3xl font-extrabold text-red-600 mb-1">{{ $totalUnpaidCount }}</p>
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


    <!-- Filter -->
<div class="glass-effect rounded-2xl p-6 card-shadow-lg mb-6 animate-scale-in">
    <form method="GET" action="{{ route('admin.merch.dashboard') }}" 
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">

        {{-- Status Pembayaran --}}
        <div>
            <label for="payment_status" class="block text-xs font-semibold text-gray-700 mb-1.5">Status Pembayaran</label>
            <select id="payment_status" name="payment_status"
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                <option value="">-- Semua Status --</option>
                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
            </select>
        </div>

        {{-- Tanggal Mulai --}}
        <div>
            <label for="start_date" class="block text-xs font-semibold text-gray-700 mb-1.5">Tanggal Mulai</label>
            <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}"
                   class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"/>
        </div>

        {{-- Tanggal Selesai --}}
        <div>
            <label for="end_date" class="block text-xs font-semibold text-gray-700 mb-1.5">Tanggal Selesai</label>
            <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}"
                   class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"/>
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

            <a href="{{ route('admin.merch.dashboard') }}"
               class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold text-sm transition-all duration-300 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset
            </a>
        </div>
    </form>
</div>


<div class="flex flex-wrap gap-3 mb-6 animate-slide-in">
    {{-- Export Excel --}}
    <a href="{{ route('admin.merch.dashboard.export.excel', request()->query()) }}" 
       class="btn-ripple px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg font-semibold text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        Export Excel
    </a>

    {{-- Export PDF --}}
    <a href="{{ route('admin.merch.dashboard.export.pdf', request()->query()) }}" 
       class="btn-ripple px-5 py-2.5 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-lg font-semibold text-sm shadow-lg hover:shadow-xl transition-all duration-300 flex items-center gap-2"
       target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
        Export PDF
    </a>
</div>


    <!-- Tabel -->
<div class="glass-effect rounded-2xl overflow-hidden card-shadow-lg animate-scale-in">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">No.</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Judul Merchandise</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Email</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Waktu Checkout</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Waktu Pembayaran</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Total</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">QR Code</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Jumlah Item</th>
                    <th class="px-3 py-2 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse ($transactions as $transaction)
                    <tr class="table-row-hover">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loop->iteration }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">{{ $transaction->details->first()->product->title ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $transaction->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $transaction->checkout_time }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $transaction->paid_time ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                {{ $transaction->payment_status === 'paid' 
                                    ? 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-700' 
                                    : 'bg-gradient-to-r from-red-100 to-pink-100 text-red-700' }}">
                                {{ ucfirst($transaction->payment_status) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm font-bold text-gray-900">Rp{{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            @if($transaction->qr_code)
                                <a href="{{ route('guests.merch.qr', $transaction->kode_unik) }}" target="_blank"
                                   class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg text-xs font-semibold shadow-md hover:shadow-lg transition-all duration-200">
                                    Lihat QR
                                </a>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold">
                                {{ $transaction->details->sum('quantity') }} pcs
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <div class="flex flex-col space-y-2">
                                <button onclick="showDetail({{ $transaction->id }})"
                                        class="inline-flex items-center justify-center px-3 py-1.5 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white rounded-lg text-xs font-semibold shadow-md hover:shadow-lg transition-all duration-200">
                                    Detail
                                </button>
                                <form id="regenerate-form-{{ $transaction->id }}"
                                      action="{{ route('admin.transactions.regenerateQR', $transaction->id) }}"
                                      method="POST" class="inline-block">
                                    @csrf
                                    <button type="button"
                                            onclick="openConfirmModal({{ $transaction->id }})"
                                            class="w-full inline-flex items-center justify-center px-3 py-1.5 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white rounded-lg text-xs font-semibold shadow-md hover:shadow-lg transition-all duration-200">
                                        Regenerate QR
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                            Tidak ada transaksi merchandise
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</main>

<!-- Modal Detail -->
<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4 transition-all duration-300">
    <div class="glass-effect w-full max-w-3xl rounded-3xl shadow-2xl p-8 overflow-y-auto max-h-[90vh] transform transition-all duration-300 scale-95 opacity-0" id="detailModalContent">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-800">Detail Transaksi Merchandise</h3>
            <button type="button" onclick="closeDetailModal()" class="text-gray-500 hover:text-gray-800 transition-colors">
                ✕
            </button>
        </div>
        <div id="detailContent" class="text-sm text-gray-700 space-y-4"></div>
        <div class="flex justify-end mt-6">
            <button type="button" onclick="closeDetailModal()" class="btn-secondary">Tutup</button>
        </div>
    </div>
</div>

<script>
    const transactions = @json($transactions);

    function showDetail(id) {
        const transaction = transactions.find(t => t.id === id);
        if (!transaction) return;

        let html = `<h4 class="font-bold mb-2 text-gray-800 border-b pb-2">Detail Pembelian</h4>`;

        if (transaction.details.length === 0) {
            html += `<p class="text-gray-500">Tidak ada detail</p>`;
        } else {
            transaction.details.forEach(d => {
                html += `
                    <div class="mt-4 border border-gray-200 p-4 rounded-xl bg-gray-50">
                        <div class="flex items-center space-x-4 mb-2">
                            ${d.varian?.image ? `<img src="${d.varian.image}" class="h-20 w-20 object-cover rounded-lg shadow-md">` : ''}
                            <div>
                                <p class="font-bold text-gray-800 text-lg">${d.product?.name ?? '-'}</p>
                                <p class="text-sm text-gray-600">${d.varian?.varian ?? '-'} - ${d.ukuran?.ukuran ?? '-'}</p>
                            </div>
                        </div>
                        <p><strong>Pembeli:</strong> ${d.buyer_name} (${d.buyer_phone ?? '-'})</p>
                        <p><strong>Qty:</strong> ${d.quantity}</p>
                        <p><strong>Subtotal:</strong> Rp${new Intl.NumberFormat('id-ID').format(d.subtotal)}</p>
                    </div>
                `;
            });
        }

        document.getElementById('detailContent').innerHTML = html;
        document.getElementById('detailModal').classList.remove('hidden');
        document.getElementById('detailModalContent').classList.remove('scale-95', 'opacity-0');
        document.getElementById('detailModalContent').classList.add('scale-100', 'opacity-100');
    }

    function closeDetailModal() {
        const wrapper = document.getElementById('detailModalContent');
        wrapper.classList.remove('scale-100','opacity-100');
        wrapper.classList.add('scale-95','opacity-0');
        setTimeout(() => {
            document.getElementById('detailModal').classList.add('hidden');
        }, 300);
    }
</script>
</body>
</html>
@endsection
