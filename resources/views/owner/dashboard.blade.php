@extends('layouts.owner')

@section('title', 'Dashboard Owner')

@section('content')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-4 text-gray-800">

    {{-- 1. HEADER RINGKAS & FILTER PERIODE WAKTU --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 bg-white p-4 rounded-xl border">
        <div>
            <h1 class="text-xl font-bold tracking-tight">Dashboard Owner</h1>
            <p class="text-xs text-gray-500">Ringkasan analitik keuntungan dan performa finansial platform.</p>
        </div>
        
        <form action="{{ route('owner.dashboard') }}" method="GET" id="filterForm" class="flex flex-wrap items-center gap-2">
            <div class="flex flex-col">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Periode Waktu:</span>
                <select name="period" onchange="toggleCustomDate(this.value); document.getElementById('filterForm').submit();" class="text-xs bg-gray-50 border rounded-lg px-2.5 py-1.5 outline-none focus:ring-2 focus:ring-amber-500 font-medium text-gray-700 min-w-[140px]">
                    <option value="all" {{ $period == 'all' ? 'selected' : '' }}>Semua Waktu</option>
                    <option value="today" {{ $period == 'today' ? 'selected' : '' }}>Hari Ini</option>
                    <option value="7_days" {{ $period == '7_days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                    <option value="30_days" {{ $period == '30_days' ? 'selected' : '' }}>30 Hari Terakhir</option>
                    <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                    <option value="custom" {{ $period == 'custom' ? 'selected' : '' }}>Kustom Tanggal...</option>
                </select>
            </div>

            <div id="customDateInputs" class="{{ $period == 'custom' ? 'flex' : 'hidden' }} items-center gap-1.5 mt-2 sm:mt-0">
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Mulai:</span>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="text-xs bg-gray-50 border rounded-lg px-2 py-1 outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Sampai:</span>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="text-xs bg-gray-50 border rounded-lg px-2 py-1 outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <button type="submit" class="bg-gray-800 text-white rounded-lg px-3 py-1.5 text-[11px] font-bold hover:bg-gray-700 self-end">Cari</button>
            </div>
        </form>
    </div>

    {{-- 2. METRIK FINANSIAL & HIGHLIGHT KEUNTUNGAN PLATFORM --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        {{-- Card 1: Total Omzet Platform (Kotor) --}}
        <div class="bg-gradient-to-br from-gray-800 to-gray-900 text-white rounded-xl p-4 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center">
                    <p class="text-[10px] font-bold uppercase tracking-wider opacity-75">Total Omzet Platform</p>
                    <span class="text-[9px] bg-white/20 text-white font-bold px-1.5 py-0.5 rounded uppercase">Uang Masuk</span>
                </div>
                <h2 class="text-2xl font-black mt-2 tracking-tight">Rp{{ number_format($totalPlatformSales, 0, ',', '.') }}</h2>
            </div>
            <p class="text-[9px] text-gray-300 leading-tight mt-4 pt-2 border-t border-white/10">
                *Akumulasi kotor dari seluruh penjualan sebelum dikurangi penarikan (withdraw) EO & pengembalian dana (refund) pembeli.
            </p>
        </div>

        {{-- Card 2: Keuntungan Bersih Platform (Service Tax) --}}
        <div class="bg-gradient-to-br from-amber-500 to-orange-600 text-white rounded-xl p-4 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center">
                    <p class="text-[10px] font-bold uppercase tracking-wider opacity-90">Keuntungan Platform</p>
                    <span class="text-[9px] bg-black/20 text-white font-bold px-1.5 py-0.5 rounded uppercase font-mono">Service Tax</span>
                </div>
                <h2 class="text-2xl font-black mt-2 tracking-tight">Rp{{ number_format($totalPlatformEarnings, 0, ',', '.') }}</h2>
            </div>
            <p class="text-[9px] text-amber-100 leading-tight mt-4 pt-2 border-t border-white/10">
                ✔ Hak bersih milik platform yang ditarik dari potongan biaya layanan tiap transaksi (Tiket + Merch).
            </p>
        </div>

        {{-- Card 3: Breakdown Komponen Omzet --}}
        <div class="bg-white border rounded-xl p-4 shadow-sm flex flex-col justify-center space-y-3">
            <div class="flex justify-between items-center text-xs">
                <div>
                    <p class="text-gray-400 font-medium text-[10px] uppercase">Total Penjualan Tiket</p>
                    <p class="font-bold text-gray-700 mt-0.5">Rp{{ number_format($totalTicketSales, 0, ',', '.') }}</p>
                </div>
                <span class="text-sm">🎫</span>
            </div>
            <div class="border-t border-dashed my-1"></div>
            <div class="flex justify-between items-center text-xs">
                <div>
                    <p class="text-gray-400 font-medium text-[10px] uppercase">Total Penjualan Merch</p>
                    <p class="font-bold text-gray-700 mt-0.5">Rp{{ number_format($totalMerchSales, 0, ',', '.') }}</p>
                </div>
                <span class="text-sm">👕</span>
            </div>
        </div>

    </div>

    {{-- 3. KARTU MINI OPERASIONAL PLATFORM --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-gray-50 border rounded-xl px-3 py-2.5 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-gray-400 font-bold uppercase">Total User</p>
                <h3 class="text-base font-bold text-gray-800">{{ $totalUsers }}</h3>
            </div>
            <span class="text-base opacity-60">👤</span>
        </div>
        <div class="bg-gray-50 border rounded-xl px-3 py-2.5 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-gray-400 font-bold uppercase">Total EO</p>
                <h3 class="text-base font-bold text-gray-800">{{ $totalEO }}</h3>
            </div>
            <span class="text-base opacity-60">🏢</span>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-2.5 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-yellow-600 font-bold uppercase">Req Register EO</p>
                <h3 class="text-base font-bold text-yellow-700">{{ $pendingEO }}</h3>
            </div>
            <span class="text-base">⏳</span>
        </div>
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2.5 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-emerald-600 font-bold uppercase">Event Berjalan</p>
                <h3 class="text-base font-bold text-emerald-700">{{ $approvedEvents }}</h3>
            </div>
            <span class="text-base">🎫</span>
        </div>
    </div>

    {{-- 4. GRAFIK TREN PENJUALAN --}}
    <div class="bg-white rounded-xl p-4 border shadow-sm">
        <h3 class="text-sm font-bold text-gray-800 mb-2">📈 Tren Penjualan Harian</h3>
        <div class="w-full h-[220px]">
            <canvas id="salesTrendChart"></canvas>
        </div>
    </div>

    {{-- 5. PANEL UTILITY: TOP EO & RECENT EVENTS --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        
        <div class="bg-white border rounded-xl p-4 shadow-sm lg:col-span-2">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-bold text-gray-800">🏆 Top Performa Event Organizer (EO)</h3>
                <span class="text-[10px] bg-gray-100 text-gray-500 font-bold px-2 py-0.5 rounded-full">Urutan Omzet</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b text-gray-400 font-bold text-[10px] uppercase bg-gray-50">
                            <th class="py-2 px-2">Nama EO / Badan Usaha</th>
                            <th class="py-2 px-2 text-center">Event</th>
                            <th class="py-2 px-2 text-center">Tiket</th>
                            <th class="py-2 px-2 text-right">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($eoPerformances as $performance)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-2 px-2 font-semibold text-gray-700">{{ $performance->nama_badan_usaha }}</td>
                            <td class="py-2 px-2 text-center font-medium">{{ $performance->total_events }}</td>
                            <td class="py-2 px-2 text-center font-medium text-amber-600">{{ $performance->tickets_sold }}</td>
                            <td class="py-2 px-2 text-right font-bold text-emerald-600">Rp{{ number_format($performance->total_revenue ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-400">Belum ada data aktivitas performa.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white border rounded-xl p-4 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-bold text-gray-800">📝 Persetujuan Event</h3>
                    <a href="{{ route('owner.events.index') }}" class="text-[11px] font-bold text-amber-600 hover:underline">Lihat Semua</a>
                </div>

                <div class="space-y-1.5">
                    @foreach($recentEvents->take(4) as $event)
                    <div class="border border-gray-100 rounded-lg p-2 bg-gray-50 flex justify-between items-center text-xs">
                        <div class="truncate max-w-[70%]">
                            <h4 class="font-bold text-gray-700 truncate">{{ $event->title }}</h4>
                            <p class="text-[10px] text-gray-400 truncate">EO: {{ $event->eo->nama_badan_usaha ?? '-' }}</p>
                        </div>
                        <div>
                            @if($event->status == 'pending')
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-[9px] font-bold uppercase">Pending</span>
                            @elseif($event->status == 'approved')
                                <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-[9px] font-bold uppercase">Approved</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-[9px] font-bold uppercase">Rejected</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

</div>

{{-- SCRIPT MANAGEMENT CHART --}}
<script>
    function toggleCustomDate(val) {
        var element = document.getElementById('customDateInputs');
        if (val === 'custom') {
            element.classList.remove('hidden');
            element.classList.add('flex');
        } else {
            element.classList.remove('flex');
            element.classList.add('hidden');
        }
    }

    const ctx = document.getElementById('salesTrendChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [
                {
                    label: 'Tiket',
                    data: {!! json_encode($chartTicketData) !!},
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.02)',
                    borderWidth: 2,
                    pointRadius: 2,
                    tension: 0.2,
                    fill: true
                },
                {
                    label: 'Merchandise',
                    data: {!! json_encode($chartMerchData) !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.02)',
                    borderWidth: 2,
                    pointRadius: 2,
                    tension: 0.2,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'top',
                    labels: { boxWidth: 12, font: { size: 11 } }
                }
            },
            scales: {
                x: { ticks: { font: { size: 10 } } },
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 10 },
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
</script>

@endsection