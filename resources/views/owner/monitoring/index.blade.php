@extends('layouts.owner')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6" style="font-family:'Poppins',sans-serif;">
    
    {{-- HEADER --}}
    <div class="flex items-center gap-3 mb-5">
        <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-md" style="background: linear-gradient(135deg, #0ea5e9, #2563eb);">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6m-9 0h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>
        <div>
            <h1 class="text-lg font-bold leading-tight" style="color:#111827;">Owner Board: Executive Dashboard & Live Logs</h1>
            <p class="text-xs" style="color:#6b7280;">Pantau performa menyeluruh platform beserta seluruh timeline aktivitas krusial.</p>
        </div>
    </div>

    {{-- STATISTIK PLATFORM --}}
    {{-- ... (Gunakan struktur baris statistik eksis dari Admin, sesuaikan variabel $platformStats) ... --}}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {{-- BLOK KIRI: GRID MONITORING EO --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- SEARCH & FILTER FORM --}}
            <form method="GET" class="card rounded-xl p-3 flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama EO..." class="form-control flex-1 min-w-[150px] text-sm py-2 px-4 rounded-full" style="border: 1px solid #e5e7eb;">
                <select name="status" onchange="this.form.submit()" class="form-control py-2 text-sm w-auto rounded-full">
                    <option value="">Semua Status</option>
                    <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Approved</option>
                </select>
                <select name="sort" onchange="this.form.submit()" class="form-control py-2 text-sm w-auto rounded-full">
                    <option value="name" {{ $sortBy === 'name' ? 'selected' : '' }}>Nama A-Z</option>
                    <option value="gmv" {{ $sortBy === 'gmv' ? 'selected' : '' }}>GMV Terbesar</option>
                </select>
                <button type="submit" class="btn bg-blue-600 text-white rounded-full text-xs px-4 py-2 hover:bg-blue-700">Terapkan</button>
            </form>

            {{-- GRID CARD EO --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($eoList as $eo)
                    @php $gmv = $eo->ticket_gmv + $eo->merch_gmv; @endphp
                    <a href="{{ route('owner.monitoring.eo.show', $eo->id) }}" class="card rounded-xl p-4 hover:shadow-md transition-all border border-gray-100 block">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-8 h-8 rounded bg-gray-50 flex items-center justify-center font-bold text-xs text-blue-600 border">
                                {{ strtoupper(substr($eo->nama_badan_usaha, 0, 2)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-gray-900 truncate">{{ $eo->nama_badan_usaha }}</h4>
                                <span class="text-[9px] px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-semibold">{{ $eo->status }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-dashed border-gray-100 text-[11px]">
                            <div><span class="text-gray-400 block">GMV</span><strong>Rp {{ number_format($gmv, 0, ',', '.') }}</strong></div>
                            <div><span class="text-gray-400 block">Saldo Wallet</span><strong class="text-blue-600">Rp {{ number_format($eo->wallet_balance, 0, ',', '.') }}</strong></div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-4">{{ $eoList->links() }}</div>
        </div>

        {{-- BLOK KANAN: LIVE ACTIVITY LOGS / BERITA --}}
        <div class="card rounded-xl p-4 border border-gray-200 bg-white shadow-sm flex flex-col h-fit">
            <h3 class="text-xs font-bold text-gray-900 mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                Log Aktivitas & Berita System
            </h3>
            
            <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
                @forelse($activities as $activity)
                    @php
                        $color = match($activity->type) {
                            'event_done' => ['bg-gray-100', 'text-gray-800', '🏁 DONE'],
                            'withdrawal', 'withdrawal_merch' => ['bg-amber-50', 'text-amber-800', '💰 WITHDRAW'],
                            'topup' => ['bg-blue-50', 'text-blue-800', '📥 TOPUP'],
                            'refund_batch' => ['bg-purple-50', 'text-purple-800', '📦 BATCH'],
                            'user_refund' => ['bg-rose-50', 'text-rose-800', '🙋‍♂️ REFUND'],
                        };
                    @endphp
                    <div class="p-2.5 rounded-lg border border-gray-100 {{ $color[0] }} transition-all hover:border-gray-300 text-[11px]">
                        <div class="flex items-center justify-between font-semibold mb-1">
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-white shadow-sm">{{ $color[2] }}</span>
                            <span class="text-gray-400 text-[10px]">{{ \Carbon\Carbon::parse($activity->event_time)->diffForHumans() }}</span>
                        </div>
                        <p class="text-gray-900 font-bold mb-0.5">{{ $activity->eo_name }}</p>
                        <p class="text-gray-600 leading-relaxed">{{ $activity->message }}</p>
                    </div>
                @empty
                    <p class="text-center text-xs text-gray-400 py-6">Belum ditemukan rekam jejak berita hari ini.</p>
                @endforelse
            </div>
            <div class="mt-3 text-xs">{{ $activities->links() }}</div>
        </div>
    </div>
</div>
@endsection