@extends('layouts.admin')

@section('content')
<div class="w-full p-4 bg-gray-50 text-gray-800 text-sm">
    <div class="mb-4 border-b pb-3">
        <h1 class="text-xl font-bold tracking-tight">Pusat Kendali Finansial & Dompet EO</h1>
        <p class="text-xs text-gray-500">Pantau akumulasi omset bersih, status pembekuan akun, dan manajemen utang saldo per Event Organizer.</p>
    </div>

    {{-- ALERTS ALUR SISTEM --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-3 py-2 rounded mb-3 text-xs flex items-center justify-between shadow-sm">
            <span>✅ {{ session('success') }}</span>
            <button class="text-green-500 hover:text-green-700 font-bold" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded mb-3 text-xs flex items-center justify-between shadow-sm">
            <span>❌ {{ session('error') }}</span>
            <button class="text-red-500 hover:text-red-700 font-bold" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif

    {{-- CDN Tom Select --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    {{-- 🔍 FILTER UTAMA: PILIH EVENT ORGANIZER (EO) --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px;">
        <div class="card-body p-3">
            <form id="filterForm" action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-sm-auto" style="min-width: 320px;">
                    <label class="form-label text-muted small text-uppercase tracking-wider mb-1" style="font-weight: 600; font-size: 0.75rem;">
                        Pilih Perusahaan / Mitra EO:
                    </label>
                    <select id="searchable-select" name="eo_id" placeholder="Cari & Pilih Nama EO...">
                        <option value="">-- Cari & Pilih Nama EO --</option>
                        @foreach($allEo as $eo)
                            <option value="{{ $eo->id }}" {{ $selectedEoId == $eo->id ? 'selected' : '' }}>
                                🏢 {{ $eo->nama_badan_usaha }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                @if($selectedEoId)
                    <div class="col-12 col-sm-auto ps-sm-2 pb-1">
                        <a href="{{ url()->current() }}" class="text-danger small text-decoration-none" style="font-weight: 500;">
                            <i class="fas fa-times me-1"></i> Reset Pencarian
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- JIKA BELUM MEMILIH EO --}}
    @if(!$selectedEoId)
        <div class="bg-white border border-gray-200 rounded p-8 text-center text-gray-400 shadow-sm">
            <div class="text-3xl mb-1">📊</div>
            <p class="text-xs font-medium text-gray-500">Silakan pilih salah satu Event Organizer pada dropdown di atas untuk memuat laporan neraca saldo keuangan.</p>
        </div>
    @else

        {{-- 💳 CARD BLOCK KINERJA RINGKASAN KEUANGAN EO --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
            {{-- Card 1: Akumulasi Saldo Gabungan --}}
            <div class="bg-white border border-gray-200 rounded p-4 shadow-sm relative overflow-hidden">
                <div class="text-[11px] font-bold uppercase text-gray-400 tracking-wider mb-1">Total Saldo Bersih Gabungan (Total Balance)</div>
                <div class="text-xl font-bold tracking-tight {{ $eoDetails->total_balance < 0 ? 'text-red-600' : 'text-indigo-600' }}">
                    Rp {{ number_format($eoDetails->total_balance, 0, ',', '.') }}
                </div>
                <p class="text-[10px] text-gray-400 mt-1">Akumulasi likuiditas gabungan (Available + Held) dari semua event.</p>
                <div class="absolute right-3 bottom-2 text-2xl text-gray-100 font-bold pointer-events-none">🪙</div>
            </div>

            {{-- Card 2: Tagihan Utang Berjalan --}}
            <div class="bg-white border border-gray-200 rounded p-4 shadow-sm relative overflow-hidden">
                <div class="text-[11px] font-bold uppercase text-gray-400 tracking-wider mb-1">Total Sisa Utang (Debts)</div>
                <div class="text-xl font-bold tracking-tight {{ $eoDetails->total_debt > 0 ? 'text-amber-600' : 'text-green-600' }}">
                    Rp {{ number_format($eoDetails->total_debt, 0, ',', '.') }}
                </div>
                @if($eoDetails->total_debt > 0)
                    <p class="text-[10px] text-amber-600 font-semibold mt-1">⚠️ Terdeteksi penunggakan dana talangan refund.</p>
                @else
                    <p class="text-[10px] text-green-600 font-semibold mt-1">✅ Bersih dari catatan utang platform.</p>
                @endif
                <div class="absolute right-3 bottom-2 text-2xl text-gray-100 font-bold pointer-events-none">📉</div>
            </div>

            {{-- Card 3: Status Gerbang Penarikan Uang --}}
            <div class="bg-white border border-gray-200 rounded p-4 shadow-sm relative overflow-hidden">
                <div class="text-[11px] font-bold uppercase text-gray-400 tracking-wider mb-1">Status Proteksi Payout</div>
                <div class="mt-1">
                    @if($eoDetails->is_locked)
                        <span class="px-2.5 py-1 rounded bg-red-50 border border-red-200 text-red-700 text-xs font-bold inline-block animate-pulse">
                            🔒 WITHDRAW LOCKED
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded bg-green-50 border border-green-200 text-green-700 text-xs font-bold inline-block">
                            🔓 TERBUKA (NORMAL)
                        </span>
                    @endif
                </div>
                <p class="text-[10px] text-gray-400 mt-1.5">Jika terkunci, sistem memblokir pencairan dana EO ini.</p>
                <div class="absolute right-3 bottom-2 text-2xl text-gray-100 font-bold pointer-events-none">🛠️</div>
            </div>
        </div>

        {{-- 📋 TABEL DAFTAR EVENT & SALDO DETIL --}}
        <div class="bg-white border border-gray-200 rounded overflow-hidden shadow-sm">
            <div class="bg-gray-100 px-3 py-2.5 border-b border-gray-200 font-bold text-gray-700 uppercase text-xs tracking-wider">
                🎭 Daftar Event & Rincian Saldo Dompet Milik {{ $eoDetails->nama_badan_usaha }}
            </div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold uppercase text-gray-500">
                        <th class="p-3 border-r">Judul Event</th>
                        <th class="p-3 border-r text-center w-40">Status Event</th>
                        <th class="p-3 border-r text-right w-52">Total Saldo (Available + Held)</th>
                        <th class="p-3 border-r text-center w-40">Status Proteksi</th>
                        <th class="p-3 text-center w-44">Aksi Kontrol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($events as $ev)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="p-3 border-r">
                            <div class="font-bold text-gray-900">{{ $ev->title }}</div>
                            <div class="text-gray-400 font-mono text-[10px]">ID Event: #{{ $ev->id }}</div>
                        </td>
                        <td class="p-3 border-r text-center">
                            @if($ev->event_status === 'cancelled')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">🚨 CANCELLED</span>
                            @elseif($ev->event_status === 'approved' && $ev->is_rescheduled > 0)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">🔄 ACTIVE/RESCHEDULED</span>
                            @elseif($ev->event_status === 'approved' && $ev->is_rescheduled == 0)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 border border-green-200">🟢 ACTIVE</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200">{{ strtoupper($ev->event_status) }}</span>
                            @endif
                        </td>
                        
                        <td class="p-3 border-r text-right font-mono {{ ($ev->available_balance + $ev->held_balance) < 0 ? 'text-red-600 bg-red-50/30' : 'text-indigo-600' }}">
                            <div class="font-bold tracking-tight text-sm">
                                Rp {{ number_format(($ev->available_balance + $ev->held_balance), 0, ',', '.') }}
                            </div>
                            <div class="text-[9px] text-gray-400 flex justify-end gap-2 mt-0.5">
                                <span>Avail: {{ number_format($ev->available_balance, 0, ',', '.') }}</span>
                                <span>|</span>
                                <span>Held: {{ number_format($ev->held_balance, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        
                        <td class="p-3 border-r text-center">
                            @if($ev->withdraw_locked == 1)
                                <span class="text-red-600 font-bold flex items-center justify-center gap-1">
                                    🛑 Terkunci
                                </span>
                            @else
                                <span class="text-green-600 font-medium flex items-center justify-center gap-1">
                                    ✅ Aman
                                </span>
                            @endif
                        </td>
                        <td class="p-3 text-center">
                            <a href="{{ route('admin.finance.manageEvent', $ev->id) }}" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1 px-3 rounded text-[11px] tracking-wide transition shadow-sm shadow-indigo-100">
                                ⚙️ Kelola Finansial
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-5 text-center text-gray-400 italic">Event Organizer ini belum tercatat memiliki history data event apapun di dalam sistem platform.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var ts = new TomSelect("#searchable-select", {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            },
            controlInput: '<input>', 
            render: {
                option: function(data, escape) {
                    return '<div>' + data.text + '</div>';
                },
                item: function(data, escape) {
                    return '<div>' + data.text + '</div>';
                }
            },
            onChange: function(value) {
                ts.close(); 
                ts.blur();  
                
                setTimeout(function() {
                    document.getElementById('filterForm').submit();
                }, 50);
            }
        });
    });
</script>
@endsection