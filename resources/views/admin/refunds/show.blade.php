@extends('layouts.admin')

@section('content')
<div class="w-full p-4 bg-gray-50 text-gray-800 text-sm">
    {{-- NAVIGASI MINI HEADER --}}
    <div class="flex items-center justify-between border-b pb-2 mb-3">
        <div>
            <a href="{{ route('admin.refunds.index', ['tab' => $batch->type]) }}" class="text-xs text-orange-600 hover:underline">← Kembali ke Daftar Batch</a>
            <h1 class="text-lg font-bold text-gray-900 mt-0.5">{{ $batch->name }}</h1>
            <div class="text-[11px] text-gray-500">
                Event: <span class="font-medium text-gray-700">{{ $batch->event->title }}</span> |
                EO: <span class="font-medium text-gray-700">{{ $batch->eo->nama_badan_usaha ?? 'N/A' }}</span>
            </div>
        </div>

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

    <!-- {{-- ALERTS --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-3 py-2 rounded mb-3 text-xs">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded mb-3 text-xs">❌ {{ session('error') }}</div>
    @endif
    @if(session('warning'))
        <div class="bg-amber-50 border border-amber-200 text-amber-700 px-3 py-2 rounded mb-3 text-xs">⚠️ {{ session('warning') }}</div>
    @endif -->

    @php
        $pendingCount     = $refunds->where('status', 'pending')->count();
        $processingCount  = $refunds->where('status', 'processing')->count();
        $failedCount      = $refunds->where('status', 'failed')->count();
        $needsReviewCount = $refunds->where('status', 'needs_review')->count();
        $belumFinalCount  = $pendingCount + $processingCount;
        $siapKirimCount   = $pendingCount + $failedCount;
    @endphp

    @if($needsReviewCount > 0)
        <div class="bg-red-50 border border-red-300 text-red-800 p-2 text-xs rounded mb-3">
            🚨 <strong>Perlu Tinjauan Manual:</strong> ada {{ $needsReviewCount }} refund yang sempat SUKSES lalu di-reversed channel bank (rekening tidak valid/dorman). Tinjau dan tindak lanjuti secara manual, saldo/utang belum otomatis disesuaikan.
        </div>
    @endif

    {{-- KOTAK INFORMASI SALDO & AKSI --}}
    <div class="bg-white border border-gray-200 rounded p-3 shadow-sm mb-3 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-center">

            <div class="border-r border-gray-200 pr-2">
                <span class="text-gray-500 font-medium block mb-0.5 text-[11px] uppercase tracking-wider">Saldo Dompet Digital EO</span>
                <div class="space-y-0.5">
                    <div class="flex justify-between">
                        <span class="text-gray-400 text-[11px]">Available:</span>
                        <span class="font-semibold text-gray-700">Rp{{ number_format($wallet->available_balance ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-0.5">
                        <span class="text-gray-400 text-[11px]">Held (Sanksi):</span>
                        <span class="font-semibold text-gray-700">Rp{{ number_format($wallet->held_balance ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between pt-0.5">
                        <span class="text-gray-600 font-medium text-[11px]">Kapasitas Kas:</span>
                        <span class="font-bold text-orange-700">Rp{{ number_format($availableBalance, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="border-r border-gray-200 px-2 flex flex-col justify-center h-full">
                <span class="text-gray-500 font-medium text-[11px] uppercase tracking-wider block mb-0.5">Service Tax Event Ini</span>
                <span class="text-sm font-bold text-orange-600">Rp{{ number_format($totalServiceTaxEvent, 0, ',', '.') }}</span>
                <span class="text-[10px] text-gray-400 mt-0.5">*Pajak terakumulasi dari tiket penonton</span>
            </div>

            <div class="border-r border-gray-200 px-2 flex flex-col justify-center h-full">
                <span class="text-gray-500 font-medium text-[11px] uppercase tracking-wider block mb-0.5">Beban Pokok Refund</span>
                <span class="text-sm font-extrabold text-red-600">Rp{{ number_format($totalDanaRefund, 0, ',', '.') }}</span>
                <span class="text-[10px] text-gray-400 mt-0.5">*Total harga tiket murni pembeli</span>
            </div>

            <div class="border-r border-gray-200 px-2 flex flex-col justify-center h-full">
                <span class="text-gray-500 font-medium text-[11px] uppercase tracking-wider block mb-0.5">Estimasi Biaya Xendit</span>
                <span class="text-sm font-bold text-amber-600">Rp{{ number_format($estimasiBiayaXendit, 0, ',', '.') }}</span>
                <span class="text-[10px] text-gray-400 mt-0.5">*Rp2.500 × {{ $siapKirimCount }} antrean transfer</span>
            </div>

            <div class="flex flex-col items-stretch justify-center gap-1.5 pl-2 w-full">
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

                <div class="flex flex-col gap-1 w-full">
                    <a href="{{ route('admin.refunds.exportXendit', $batch->id) }}"
                       class="w-full text-center bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-1 px-2 rounded text-[11px] transition inline-flex items-center justify-center gap-1 {{ $batch->status !== 'closed' ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}"
                       title="{{ $batch->status !== 'closed' ? 'Kunci batch terlebih dahulu' : '' }}">
                        📥 Ekspor Xendit (arsip)
                    </a>

                    {{-- 🚀 TOMBOL KIRIM KE XENDIT PAYOUTS API (yang sebelumnya belum ada) --}}
                    @if($batch->status === 'closed' && $siapKirimCount > 0)
                        <form action="{{ route('admin.refunds.sendToXendit', $batch->id) }}" method="POST" class="w-full"
                              onsubmit="return confirm('Kirim {{ $siapKirimCount }} refund ke Xendit Payouts sekarang? Proses ini akan langsung mengeksekusi transfer nyata.')">
                            @csrf
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-2 rounded text-[11px] transition">
                                🚀 Kirim ke Xendit ({{ $siapKirimCount }})
                            </button>
                        </form>
                    @endif

                    @if($processingCount > 0)
                        <span class="w-full text-center block bg-blue-50 border border-blue-200 text-blue-700 text-[10px] font-semibold py-1 px-2 rounded">
                            ⏳ {{ $processingCount }} sedang diproses Xendit (menunggu webhook)
                        </span>
                    @endif

                    {{-- ✔️ TOMBOL SELESAIKAN BATCH — hanya aktif kalau TIDAK ADA lagi pending/processing --}}
                    @if($batch->status === 'closed' && $belumFinalCount === 0 && $refunds->count() > 0)
                        <form action="{{ route('admin.refunds.completeBatch', $batch->id) }}" method="POST" class="w-full"
                              onsubmit="return confirm('Semua refund di batch ini sudah final (refunded/rejected). Selesaikan batch ini?')">
                            @csrf
                            <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-1 px-2 rounded text-[11px] transition">
                                ✔️ Selesaikan Batch
                            </button>
                        </form>
                    @elseif($batch->status === 'closed' && $belumFinalCount > 0)
                        <span class="w-full text-center block bg-gray-100 border border-gray-300 text-gray-500 text-[10px] font-semibold py-1 px-2 rounded">
                            Masih {{ $belumFinalCount }} belum final
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($batch->status === 'open')
        <div class="bg-orange-50 border border-orange-200 text-orange-800 p-2 text-xs rounded mb-3 flex items-center gap-1.5">
            <span>ℹ️</span>
            <span><strong>Informasi Kerja Admin:</strong> Silakan klik tombol <strong>"🔒 Kunci Batch"</strong> di halaman depan terlebih dahulu untuk membekukan pendaftaran pembeli, setelah itu tombol kirim ke Xendit akan aktif.</span>
        </div>
    @endif

    {{-- TABEL RINCIAN REKENING PEMBELI --}}
    <div class="bg-white border border-gray-200 rounded overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b border-gray-200 text-[11px] font-bold uppercase text-gray-600">
                    <th class="p-2 border-r w-10 text-center">No</th>
                    <th class="p-2 border-r w-20">Ref ID Trx</th>
                    <th class="p-2 border-r">Detail Akun Pemilik Rekening</th>
                    <th class="p-2 border-r w-28">Tujuan Bank</th>
                    <th class="p-2 border-r w-36">Nomor Rekening</th>
                    <th class="p-2 border-r w-28 text-right">Nominal Refund</th>
                    <th class="p-2 border-r text-center w-28">Status</th>
                    <th class="p-2 text-center w-32">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-xs">
                @forelse($refunds as $index => $refund)
                @php
                    $relasi = $batch->type === 'ticket' ? $refund->transaction : $refund->transactionMerch;
                    $refCode = $batch->type === 'ticket' ? $refund->transaction_id : $refund->transaction_merch_id;
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="p-2 border-r text-center text-gray-400">{{ $index + 1 }}</td>
                    <td class="p-2 border-r font-mono text-[11px] text-gray-600">#{{ $refCode }}</td>
                    <td class="p-2 border-r">
                        <div class="font-bold text-gray-900 uppercase text-[11px]">{{ $refund->account_name }}</div>
                        <div class="text-[10px] text-gray-400">{{ $relasi->email ?? 'Email N/A' }}</div>
                        @if($refund->status === 'failed' && $refund->failure_message)
                            <div class="text-[10px] text-red-500 mt-0.5" title="{{ $refund->failure_message }}">
                                ⚠️ {{ \Illuminate\Support\Str::limit($refund->failure_message, 40) }}
                            </div>
                        @endif
                    </td>
                    <td class="p-2 border-r font-bold text-gray-700 uppercase">{{ $refund->bank_name }}</td>
                    <td class="p-2 border-r font-mono font-bold text-orange-600 tracking-wider">{{ $refund->account_number }}</td>
                    <td class="p-2 border-r text-right font-bold text-gray-900">
                        Rp{{ number_format($refund->grand_total_refunded, 0, ',', '.') }}
                    </td>
                    <td class="p-2 border-r text-center">
                        @switch($refund->status)
                            @case('waiting')
                                <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 border border-gray-200 text-[10px] font-semibold">Waiting List</span>
                                @break
                            @case('pending')
                                <span class="px-1.5 py-0.5 rounded bg-yellow-50 text-yellow-700 border border-yellow-200 text-[10px] font-semibold">Antrean</span>
                                @break
                            @case('processing')
                                <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-semibold">Diproses Xendit</span>
                                @break
                            @case('refunded')
                                <span class="px-1.5 py-0.5 rounded bg-green-50 text-green-700 border border-green-200 text-[10px] font-semibold">Berhasil</span>
                                @break
                            @case('failed')
                                <span class="px-1.5 py-0.5 rounded bg-red-50 text-red-700 border border-red-200 text-[10px] font-semibold">Gagal</span>
                                @break
                            @case('needs_review')
                                <span class="px-1.5 py-0.5 rounded bg-red-100 text-red-800 border border-red-300 text-[10px] font-semibold">Perlu Tinjau</span>
                                @break
                            @case('rejected')
                                <span class="px-1.5 py-0.5 rounded bg-gray-200 text-gray-600 border border-gray-300 text-[10px] font-semibold">Gagal Permanen</span>
                                @break
                        @endswitch
                    </td>
                    <td class="p-2 text-center">
                        @if($refund->status === 'failed')
                            <form action="{{ route('admin.refunds.item.retry', $refund->id) }}" method="POST" class="mb-1">
                                @csrf
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-semibold py-1 px-2 rounded">
                                    🔁 Kirim Ulang
                                </button>
                            </form>
                            <form action="{{ route('admin.refunds.item.reject', $refund->id) }}" method="POST"
                                  onsubmit="return confirm('Tandai refund #{{ $refund->id }} gagal permanen? Pembeli wajib mengajukan ulang.')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="w-full bg-gray-500 hover:bg-gray-600 text-white text-[10px] font-semibold py-1 px-2 rounded">
                                    ❌ Gagal Permanen
                                </button>
                            </form>
                        @elseif($refund->status === 'processing')
                            <form action="{{ route('admin.refunds.item.sync', $refund->id) }}" method="POST"
                                  onsubmit="return confirm('Cek & sinkronkan status refund #{{ $refund->id }} langsung dari Xendit? Berguna bila webhook belum masuk.')">
                                @csrf
                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-semibold py-1 px-2 rounded" title="Tarik status terkini dari Xendit tanpa menunggu webhook">
                                    🔄 Sinkronkan Status
                                </button>
                            </form>
                        @else
                            <span class="text-gray-300 text-[10px]">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-4 text-center text-gray-400">Belum ada pengajuan rekening pembeli masuk di dalam batch ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection