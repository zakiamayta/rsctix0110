@extends('layouts.admin')

@section('content')
<div class="w-full p-4 bg-gray-50 text-gray-800 text-sm">
    {{-- NAVIGASI MINI HEADER --}}
    <div class="flex items-center justify-between border-b pb-2 mb-3">
        <div>
            <a href="{{ route('admin.refunds.index') }}" class="text-xs text-orange-600 hover:underline">← Kembali ke Daftar Batch</a>
            <h1 class="text-lg font-bold text-gray-900 mt-0.5">{{ $batch->name }}</h1>
            <div class="text-[11px] text-gray-500">
                Event: <span class="font-medium text-gray-700">{{ $batch->event->title }}</span> | 
                EO: <span class="font-medium text-gray-700">{{ $batch->eo->nama_badan_usaha ?? 'N/A' }}</span>
            </div>
        </div>
        
        {{-- BANNER STATUS MINI --}}
        <div>
            @if($batch->status === 'open')
                <span class="px-2.5 py-1 rounded bg-green-50 border border-green-200 text-green-700 text-xs font-bold">STATUS BATCH: TERBUKA (OPEN)</span>
            @elseif($batch->status === 'closed')
                <span class="px-2.5 py-1 rounded bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold">STATUS BATCH: TERKUNCI (CLOSED)</span>
            @else
                <span class="px-2.5 py-1 rounded bg-gray-200 border border-gray-300 text-gray-600 text-xs font-bold">STATUS BATCH: SELESAI</span>
            @endif
        </div>
    </div>

    {{-- KOTAK INFORMASI SALDO & AKSI (GRID 5 KOLOM) --}}
    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm mb-3 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-center">
            
            {{-- 1. KOLOM SALDO DIGITAL EO --}}
            <div class="border-r border-gray-200 pr-2">
                <span class="text-gray-500 font-medium block mb-0.5 text-[11px] uppercase tracking-wider">Saldo Dompet Digital EO</span>
                <div class="space-y-0.5">
                    <div class="flex justify-between">
                        <span class="text-gray-400 text-[11px]">Available:</span>
                        {{-- 🎯 DIUBAH AGAR DINAMIS MENGIKUTI WALLET DARI CONTROLLER --}}
                        <span class="font-semibold text-gray-700">Rp{{ number_format($wallet->available_balance ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-0.5">
                        <span class="text-gray-400 text-[11px]">Held (Sanksi):</span>
                        {{-- 🎯 DIUBAH AGAR DINAMIS MENGIKUTI WALLET DARI CONTROLLER --}}
                        <span class="font-semibold text-gray-700">Rp{{ number_format($wallet->held_balance ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between pt-0.5">
                        <span class="text-gray-600 font-medium text-[11px]">Kapasitas Kas:</span>
                        <span class="font-bold text-orange-700">Rp{{ number_format($availableBalance, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- 2. KOLOM SERVICE TAX EVENT --}}
            <div class="border-r border-gray-200 px-2 flex flex-col justify-center h-full">
                <span class="text-gray-500 font-medium text-[11px] uppercase tracking-wider block mb-0.5">Service Tax Event Ini</span>
                <span class="text-sm font-bold text-orange-600">Rp{{ number_format($totalServiceTaxEvent, 0, ',', '.') }}</span>
                <span class="text-[10px] text-gray-400 mt-0.5">*Pajak terakumulasi dari tiket penonton</span>
            </div>

            {{-- 3. KOLOM TOTAL BEBAN REFUND MURNI --}}
            <div class="border-r border-gray-200 px-2 flex flex-col justify-center h-full">
                <span class="text-gray-500 font-medium text-[11px] uppercase tracking-wider block mb-0.5">Beban Pokok Refund</span>
                <span class="text-sm font-extrabold text-red-600">Rp{{ number_format($totalDanaRefund, 0, ',', '.') }}</span>
                <span class="text-[10px] text-gray-400 mt-0.5">*Total harga tiket murni pembeli</span>
            </div>

            {{-- 4. KOLOM ESTIMASI BIAYA MASS TRANSFER XENDIT --}}
            <div class="border-r border-gray-200 px-2 flex flex-col justify-center h-full">
                <span class="text-gray-500 font-medium text-[11px] uppercase tracking-wider block mb-0.5">Estimasi Biaya Xendit</span>
                <span class="text-sm font-bold text-amber-600">Rp{{ number_format($estimasiBiayaXendit, 0, ',', '.') }}</span>
                <span class="text-[10px] text-gray-400 mt-0.5">*Rp2.500 × {{ $refunds->where('status', 'pending')->count() }} antrean transfer</span>
            </div>

            {{-- 5. KOLOM AKSI MANAJEMEN & STATUS KECUKUPAN --}}
            <div class="flex flex-col items-stretch justify-center gap-1.5 pl-2 w-full">
                {{-- Badge Kecukupan Saldo Internal --}}
                <div class="mb-0.5 text-center">
                    @if($availableBalance >= $totalDanaRefund)
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-green-50 border border-green-200 text-green-700 block w-full">
                            🟢 Kas EO Mencukupi
                        </span>
                    @else
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-red-50 border border-red-200 text-red-700 block w-full" title="Defisit: -Rp{{ number_format($totalDanaRefund - $availableBalance, 0, ',', '.') }}">
                            🔴 Minus Rp{{ number_format($totalDanaRefund - $availableBalance, 0, ',', '.') }}
                        </span>
                    @endif
                </div>

                <div class="flex gap-1 w-full justify-center">
                    {{-- Tombol Download Template Excel Xendit --}}
                    <a href="{{ route('admin.refunds.exportXendit', $batch->id) }}" 
                       class="w-full text-center bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-1 px-2 rounded text-[11px] transition inline-flex items-center justify-center gap-1 {{ $batch->status !== 'closed' ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}"
                       title="{{ $batch->status !== 'closed' ? 'Kunci batch terlebih dahulu' : '' }}">
                        📥 Ekspor Xendit
                    </a>

                    {{-- Tombol Eksekusi Tutup Permanen --}}
                    @if($batch->status === 'closed' && $refunds->where('status', 'pending')->count() > 0)
                        <form action="{{ route('admin.refunds.completeBatch', $batch->id) }}" method="POST" class="w-full" onsubmit="return confirm('PENTING: Pastikan Anda sudah mentransfer sukses via file Xendit tadi. Selesaikan batch ini?')">
                            @csrf
                            <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-1 px-2 rounded text-[11px] transition whitespace-nowrap text-center">
                                ✔️ Selesai & Potong
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- NOTIFIKASI REGULASI OPERASIONAL JIKA BELUM DIKUNCI --}}
    @if($batch->status === 'open')
        <div class="bg-orange-50 border border-orange-200 text-orange-800 p-2 text-xs rounded mb-3 flex items-center gap-1.5">
            <span>ℹ️</span> 
            <span><strong>Informasi Kerja Admin:</strong> Silakan klik tombol <strong>"🔒 Kunci Batch"</strong> di halaman depan terlebih dahulu untuk membekukan pendaftaran pembeli, setelah itu tombol ekspor Xendit akan aktif otomatis.</span>
        </div>
    @endif

    {{-- TABEL RINCIAN REKENING PEMBELI --}}
    <div class="bg-white border border-gray-200 rounded overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b border-gray-200 text-[11px] font-bold uppercase text-gray-600">
                    <th class="p-2 border-r w-12 text-center">No</th>
                    <th class="p-2 border-r w-24">Ref ID Trx</th>
                    <th class="p-2 border-r">Detail Akun Pemilik Rekening</th>
                    <th class="p-2 border-r w-32">Tujuan Bank</th>
                    <th class="p-2 border-r w-40">Nomor Rekening</th>
                    <th class="p-2 border-r w-32 text-right">Nominal Refund</th>
                    <th class="p-2 text-center w-24">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-xs">
                @forelse($refunds as $index => $refund)
                <tr class="hover:bg-gray-50 transition">
                    <td class="p-2 border-r text-center text-gray-400">{{ $index + 1 }}</td>
                    <td class="p-2 border-r font-mono text-[11px] text-gray-600">#{{ $refund->transaction_id }}</td>
                    <td class="p-2 border-r">
                        <div class="font-bold text-gray-900 uppercase text-[11px]">{{ $refund->account_name }}</div>
                        <div class="text-[10px] text-gray-400">{{ $refund->transaction->email ?? 'Email N/A' }}</div>
                    </td>
                    <td class="p-2 border-r font-bold text-gray-700 uppercase">{{ $refund->bank_name }}</td>
                    <td class="p-2 border-r font-mono font-bold text-orange-600 tracking-wider">{{ $refund->account_number }}</td>
                    <td class="p-2 border-r text-right font-bold text-gray-900">
                        Rp{{ number_format($refund->grand_total_refunded, 0, ',', '.') }}
                    </td>
                    <td class="p-2 text-center">
                        @if($refund->status === 'pending')
                            <span class="px-1.5 py-0.5 rounded bg-yellow-50 text-yellow-700 border border-yellow-200 text-[10px] font-semibold">Antrean</span>
                        @elseif($refund->status === 'refunded')
                            <span class="px-1.5 py-0.5 rounded bg-green-50 text-green-700 border border-green-200 text-[10px] font-semibold">Berhasil</span>
                        @else
                            <span class="px-1.5 py-0.5 rounded bg-red-50 text-red-700 border border-red-200 text-[10px] font-semibold">Ditolak</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-4 text-center text-gray-400">Belum ada pengajuan rekening pembeli masuk di dalam batch ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection