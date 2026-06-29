@extends('layouts.admin')

@section('content')
<div class="w-full p-4 bg-gray-50 text-gray-800 text-sm">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 border-b pb-3 gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight">Manajemen Batch Refund</h1>
            <p class="text-xs text-gray-500">Kelola pembukuan pengembalian dana tiket massal per event.</p>
        </div>
        
        {{-- AKSI MINI: BUKA GERBANG REFUND --}}
        <form action="{{ route('admin.refunds.storeBatch') }}" method="POST" class="flex items-center gap-2 self-start md:self-auto">
            @csrf
            <select name="event_id" required class="rounded border border-gray-300 p-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-white max-w-xs">
                <option value="">-- Pilih Event Bermasalah --</option>
                @foreach($eligibleEvents as $event)
                    <option value="{{ $event->id }}">
                        {{ $event->title }} 
                        (@if($event->status === 'cancelled') Batal / Cancelled @else Reschedule @endif)
                    </option>
                @endforeach
            </select>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-3 py-1.5 rounded text-xs transition shrink-0">
                + Buka Batch Baru
            </button>
        </form>
    </div>

    {{-- ALERTS RINGKAS --}}
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

    <div class="flex border-b border-gray-200 mb-5">
        <a href="{{ route('admin.refunds.index', ['tab' => 'ticket']) }}" 
           class="py-2.5 px-5 text-xs font-bold border-b-2 transition flex items-center gap-2 {{ $activeTab === 'ticket' ? 'border-indigo-600 text-indigo-600 bg-indigo-50/40' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            🎫 Refund Tiket Penonton
        </a>
        <a href="{{ route('admin.refunds.index', ['tab' => 'merch']) }}" 
           class="py-2.5 px-5 text-xs font-bold border-b-2 transition flex items-center gap-2 {{ $activeTab === 'merch' ? 'border-amber-600 text-amber-600 bg-amber-50/40' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            🛍️ Refund Merchandise Event
        </a>
    </div>

    {{-- 📢 TABEL RIWAYAT / BERITA PROSES EVENT TERKINI (DENGAN MAX-HEIGHT & SCROLL VERTICAL) --}}
    <div class="bg-white border border-gray-200 rounded overflow-hidden shadow-sm mb-3">
        <div class="bg-gray-100 px-3 py-2 border-b border-gray-200 flex items-center justify-between sticky top-0 z-10">
            <span class="text-xs font-bold uppercase text-gray-700 tracking-wider flex items-center gap-1.5">
                📢 Riwayat Log Perubahan Status Kebijakan Event (Persetujuan Owner)
            </span>
            <span class="text-[11px] text-gray-400 italic">Scroll ke bawah jika data lebih dari 3</span>
        </div>
        
        {{-- 🎛️ CONTAINER PENGUNCI TINGGI MAKSIMAL (± 3 BARIS DATA) LENGKAP DENGAN SCROLLBAR --}}
        <div class="max-h-[200px] overflow-y-auto text-xs scrollbar-thin">
            <table class="w-full text-left border-collapse table-fixed">
                <thead class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold uppercase text-gray-500 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="p-2 w-40 bg-gray-50">Waktu Keputusan</th>
                        <th class="p-2 w-48 bg-gray-50">Nama Event Organizer</th>
                        <th class="p-2 bg-gray-50">Berita Aktivitas Kebijakan</th>
                        <th class="p-2 text-center w-32 bg-gray-50">Status Saat Ini</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($eventNewsLogs as $log)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="p-2 text-gray-500 font-mono text-[11px] whitespace-nowrap overflow-hidden text-ellipsis">
                            {{ date('d M Y - H:i', strtotime($log->updated_at)) }} WIB
                        </td>
                        <td class="p-2 font-medium text-gray-700 truncate">
                            {{ $log->eo->nama_badan_usaha ?? 'N/A' }}
                        </td>
                        <td class="p-2 text-gray-900 break-words line-clamp-2 md:line-clamp-none">
                            @if($log->status === 'cancelled')
                                🚨 Event <span class="font-bold text-red-700">"{{ $log->title }}"</span> telah resmi melakukan <span class="underline font-semibold">Cancel acara</span> melalui persetujuan penuh oleh System Owner.
                            @else
                                🔄 Event <span class="font-bold text-amber-700">"{{ $log->title }}"</span> telah berhasil melakukan <span class="underline font-semibold">Reschedule (ke-{{ $log->is_rescheduled }})</span> melalui persetujuan resmi oleh System Owner.
                            @endif
                        </td>
                        <td class="p-2 text-center whitespace-nowrap">
                            @if($log->status === 'cancelled')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">CANCELLED</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">RESCHEDULED</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center text-gray-400 italic">Belum ada riwayat aktivitas pembatalan atau reschedule event akhir-akhir ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 🔍 PANEL FILTER BERDASARKAN PILIHAN EVENT --}}
    <div class="bg-white border border-gray-200 rounded p-3 mb-4 shadow-sm">
        <form action="{{ url()->current() }}" method="GET" class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <div class="flex items-center gap-1.5 w-full sm:w-auto">
                    <label class="text-[11px] font-bold uppercase text-gray-500 tracking-wider shrink-0">Filter Event:</label>
                    <select name="filter_event_id" onchange="this.form.submit()" class="w-full sm:w-72 rounded border border-gray-300 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-gray-50 font-medium truncate">
                        <option value="">📋 Tampilkan Semua Event</option>
                        @foreach($allEventsWithBatches as $ev)
                            <option value="{{ $ev->id }}" {{ request('filter_event_id') == $ev->id ? 'selected' : '' }}>
                                🎭 {{ $ev->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(request('filter_event_id'))
                    <a href="{{ url()->current() }}" class="text-xs text-red-600 hover:underline font-semibold ml-1 shrink-0">
                        Reset Filter
                    </a>
                @endif
            </div>
            
            <div class="text-[11px] text-gray-400 italic">
                Menampilkan {{ $batches->count() }} data batch.
            </div>
        </form>
    </div>

    {{-- TABEL UTAMA FULL WIDTH --}}
    <div class="bg-white border border-gray-200 rounded overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b border-gray-200 text-xs font-bold uppercase text-gray-600">
                    <th class="p-2 border-r">Nama Batch / Judul Event</th>
                    <th class="p-2 border-r text-center w-32">Status Gerbang</th>
                    <th class="p-2 border-r text-center w-24">Total Pengajuan</th>
                    <th class="p-2 border-r text-center w-40">Periode Sistem</th>
                    <th class="p-2 text-center w-48">Aksi Manajemen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-xs">
                @forelse($batches as $b)
                <tr class="hover:bg-gray-50 transition">
                    <td class="p-2 border-r">
                        <div class="font-bold text-gray-900">{{ $b->name }}</div>
                        <div class="text-gray-500 text-[11px]">EO: {{ $b->eo->nama_badan_usaha ?? 'N/A' }}</div>
                    </td>
                    <td class="p-2 border-r text-center">
                        @if($b->status === 'open')
                            <span class="px-2 py-0.5 rounded-full bg-green-50 border border-green-200 text-green-700 text-[11px] font-semibold">Terbuka (Open)</span>
                        @elseif($b->status === 'closed')
                            <span class="px-2 py-0.5 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-[11px] font-semibold">Terkunci (Closed)</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full bg-gray-100 border border-gray-300 text-gray-500 text-[11px]">Selesai (Completed)</span>
                        @endif
                    </td>
                    <td class="p-2 border-r text-center font-bold text-sm text-indigo-600">
                        {{ $b->total_pengajuan }} Trx
                    </td>
                    <td class="p-2 border-r text-center text-gray-500 text-[11px]">
                        {{ date('d/m/Y', strtotime($b->start_date)) }} s/d {{ date('d/m/Y', strtotime($b->end_date)) }}
                    </td>
                    <td class="p-2 text-center flex items-center justify-center gap-1.5">
                        {{-- Tombol Detail --}}
                        <a href="{{ route('admin.refunds.show', $b->id) }}" class="bg-gray-800 hover:bg-black text-white px-2 py-1 rounded text-[11px] font-medium transition">
                            👁️ Periksa Data
                        </a>

                        {{-- Tombol Toggle Pintu Gerbang --}}
                        @if($b->status !== 'completed')
                            <form action="{{ route('admin.refunds.toggleStatus', $b->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                
                                @if($b->status === 'open')
                                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-1 px-3 rounded text-xs transition">
                                        🔒 Kunci Batch
                                    </button>
                                @else
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-xs transition">
                                        🔓 Buka Kembali
                                    </button>
                                @endif
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-4 text-center text-gray-400">
                        Tidak ada data batch refund untuk kriteria filter event ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection