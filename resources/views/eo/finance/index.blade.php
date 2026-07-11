@extends('layouts.eo') {{-- Sesuaikan dengan nama master layout akun EO Anda --}}

@section('content')
<div class="w-full p-4 bg-gray-50 text-gray-800 text-sm">
    {{-- HEADER HALAMAN --}}
    <div class="mb-5 border-b pb-3">
        <h1 class="text-xl font-bold tracking-tight">Pusat Keuangan & Tagihan Top-Up</h1>
        <p class="text-xs text-gray-500">Pantau kewajiban penyesuaian dana talangan refund dan upload bukti transfer untuk memulihkan gerbang penarikan (payout).</p>
    </div>

    {{-- ALERTS NOTIFIKASI SISTEM --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-3 py-2 rounded mb-4 text-xs flex items-center justify-between shadow-sm">
            <span>✅ {{ session('success') }}</span>
            <button class="text-green-500 hover:text-green-700 font-bold" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded mb-4 text-xs flex items-center justify-between shadow-sm">
            <span>❌ {{ session('error') }}</span>
            <button class="text-red-500 hover:text-red-700 font-bold" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif

    {{-- SUMMARY CARD BLOCK --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
        {{-- Card 1: Sisa Kewajiban Utang --}}
        <div class="bg-white border border-gray-200 rounded p-4 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div>
                <div class="text-[11px] font-bold uppercase text-gray-400 tracking-wider mb-1">Total Sisa Tagihan Berjalan</div>
                <div class="text-xl font-mono font-bold {{ $totalRemainingDebt > 0 ? 'text-amber-600' : 'text-green-600' }}">
                    Rp {{ number_format($totalRemainingDebt, 0, ',', '.') }}
                </div>
            </div>
            <div class="mt-2 pt-2 border-t border-dashed">
                @if($totalRemainingDebt > 0)
                    <p class="text-[11px] text-amber-700 leading-tight">
                        ⚠️ Akun Anda mendapati penangguhan pencairan uang (Withdraw Locked) pada event terkait hingga akumulasi tagihan ini diselesaikan.
                    </p>
                @else
                    <p class="text-[11px] text-green-600 font-medium">
                        ✅ Selamat! Anda tidak memiliki tunggakan dana talangan kas di platform.
                    </p>
                @endif
            </div>
            <div class="absolute right-3 top-3 text-2xl text-gray-100 font-bold pointer-events-none">📉</div>
        </div>

        {{-- Card 2: Metode Pembayaran Pembukuan --}}
        <div class="bg-white border border-gray-200 rounded p-4 shadow-sm relative overflow-hidden">
            <div class="text-[11px] font-bold uppercase text-gray-400 tracking-wider mb-1">Gerbang Transfer Rekening Platform</div>
            <div class="text-xs text-gray-700 space-y-1 mt-1 font-medium">
                <div>🏦 Bank Central Asia (BCA): <span class="font-mono font-bold text-gray-900">123-4567-890</span></div>
                <div>🏢 Atas Nama: <span class="font-bold text-gray-900">PT Platform Digital Karcis</span></div>
                <div class="text-[10px] text-gray-400 font-normal italic mt-1">*Mohon lakukan transfer sesuai nominal tagihan instruksi di bawah ini.</div>
            </div>
            <div class="absolute right-3 top-3 text-2xl text-gray-100 font-bold pointer-events-none">💳</div>
        </div>
    </div>

    {{-- GRID SEKSYEN BAWAH --}}
    <div class="space-y-5">
        


        {{-- BAGIAN 2: LOG TOP UP DAN UPLOAD BUKTI TRANSFER --}}
        <div class="bg-white border border-gray-200 rounded overflow-hidden shadow-sm">
            <div class="bg-gray-100 px-3 py-2.5 border-b border-gray-200 font-bold text-gray-700 uppercase text-xs tracking-wider">
                📥 Log Instruksi Pembayaran & Unggah Struk Struk ATM / M-Banking
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold uppercase text-gray-500">
                            <th class="p-3 border-r w-40">Tanggal Instruksi</th>
                            <th class="p-3 border-r text-right w-44">Nominal Pengembalian</th>
                            <th class="p-3 border-r text-center w-40">Status Verifikasi</th>
                            <th class="p-3 border-r">Catatan Petunjuk Admin</th>
                            <th class="p-3 text-center w-48">Aksi Berkas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        @forelse($topups as $t)
                        <tr class="hover:bg-gray-50/40">
                            <td class="p-3 border-r text-gray-500 font-mono text-[11px]">
                                {{ date('d M Y - H:i', strtotime($t->created_at)) }}
                            </td>
                            <td class="p-3 border-r text-right font-bold text-gray-900 font-mono text-sm">
                                Rp {{ number_format($t->amount_requested, 0, ',', '.') }}
                            </td>
                            <td class="p-3 border-r text-center">
                                @if($t->status === 'approved')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 border border-green-200">✅ DISETUJUI</span>
                                @elseif($t->status === 'rejected')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200 cursor-pointer underline" onclick="alert('Alasan Penolakan Admin:\n{{ $t->admin_note ?? 'Tidak ada catatan tambahan.' }}')">
                                        ❌ DITOLAK (KLIK DETAIL)
                                    </span>
                                @elseif($t->status === 'pending_verification')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">⏳ SEDANG DIPERIKSA</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">📋 MENUNGGU TRANSFER</span>
                                @endif
                            </td>
                            <td class="p-3 border-r text-gray-600 text-xs" style="max-w: 240px; word-wrap: break-word; white-space: normal;">
                                {{ $t->admin_note ?? '-' }}
                            </td>
                            <td class="p-3 text-center">
                                @if(in_array($t->status, ['requested', 'rejected']))
                                    <button type="button" onclick="openUploadModal('{{ $t->id }}', '{{ $t->amount_requested }}')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1 px-3 rounded text-[11px] transition shadow-sm">
                                        📤 Lakukan Pembayaran
                                    </button>
                                @elseif($t->status === 'pending_verification')
                                    <span class="text-amber-600 font-medium italic text-[11px]">Menunggu Review Finansial</span>
                                @else
                                    <span class="text-gray-400 italic text-[11px]">Selesai / Rekonsiliasi Berhasil</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-5 text-center text-gray-400 italic">Belum ada catatan log tagihan instruksi penyesuaian saldo dari admin platform.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
                {{-- BAGIAN 1: DETAIL TUNGGAKAN PER EVENT --}}
        <div class="bg-white border border-gray-200 rounded overflow-hidden shadow-sm">
            <div class="bg-gray-100 px-3 py-2.5 border-b border-gray-200 font-bold text-gray-700 uppercase text-xs tracking-wider">
                📊 Rincian Catatan Piutang Berjalan per Event
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold uppercase text-gray-500">
                            <th class="p-3 border-r">Nama Event Terkait</th>
                            <th class="p-3 border-r text-right w-44">Total Utang Awal</th>
                            <th class="p-3 border-r text-right w-44">Sisa Kewajiban</th>
                            <th class="p-3 text-center w-36">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs">
                        @forelse($debts as $d)
                        <tr class="hover:bg-gray-50/40">
                            <td class="p-3 border-r">
                                <div class="font-bold text-gray-900">{{ $d->event_title }}</div>
                                <div class="text-[10px] text-gray-400 font-mono">ID Nota Tagihan: #{{ $d->id }}</div>
                            </td>
                            <td class="p-3 border-r text-right font-mono text-gray-600">
                                Rp {{ number_format($d->total_debt, 0, ',', '.') }}
                            </td>
                            <td class="p-3 border-r text-right font-mono font-bold {{ $d->remaining_debt > 0 ? 'text-amber-600' : 'text-green-600' }}">
                                Rp {{ number_format($d->remaining_debt, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center">
                                @if($d->debt_status === 'unpaid')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">BELUM LUNAS</span>
                                @elseif($d->debt_status === 'partially_paid')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">CICIL / SEBAGIAN</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 border border-green-200">LUNAS</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-5 text-center text-gray-400 italic">Selamat! Akun Anda bersih dari seluruh catatan riwayat tagihan kas platform.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- 📜 MODAL POP-UP: UPLOAD BUKTI TRANSFER --}}
{{-- 📜 MODAL POP-UP: UPLOAD BUKTI TRANSFER --}}
<div id="uploadModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded overflow-hidden max-w-md w-full shadow-2xl relative">
        <div class="bg-gray-900 text-white p-3 font-bold text-xs flex justify-between items-center">
            <span>Kirim Bukti Transfer Pengembalian Dana</span>
            <button type="button" onclick="closeUploadModal()" class="text-gray-400 hover:text-white font-bold text-lg">×</button>
        </div>
        
        {{-- Form Action diubah dinamis via JS --}}
        <form id="uploadForm" action="" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="p-4 bg-gray-50/50">
                <div class="mb-3 bg-amber-50 border border-amber-200 p-2.5 rounded text-xs text-amber-900">
                    <div>Nilai Tagihan Yang Harus Ditransfer:</div>
                    <div class="font-mono font-bold text-sm text-amber-700 mt-0.5" id="modalAmountLabel">Rp 0</div>
                </div>

                {{-- KUSTOMISASI TEKS INSTRUKSI REKENING --}}
                <div class="mb-4 text-xs text-gray-700 leading-relaxed font-medium bg-blue-50 border border-blue-200 p-2.5 rounded">
                    ℹ️ Silakan transfer pada Nomor Rekening <span class="font-mono font-bold text-blue-900 bg-blue-100 px-1 rounded">079798707984</span> lalu upload bukti transfer pada form dibawah ini.
                </div>

                <div class="mb-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Pilih File Gambar Struk / Bukti Transfer:</label>
                    <input type="file" name="proof_of_transfer" required accept="image/*" class="w-full rounded border border-gray-300 p-2 text-xs bg-white focus:outline-none">
                    <span class="text-[10px] text-gray-400 block mt-1 leading-normal">
                        * Format berkas wajib berupa gambar (.jpg, .jpeg, .png) dengan kapasitas file maksimal 2 MB. Pastikan tulisan nominal, tanggal, dan nomor rekening tujuan pada struk terlihat jelas.
                    </span>
                </div>
            </div>
            
            <div class="p-3 bg-gray-100 border-t flex justify-end gap-2">
                <button type="button" onclick="closeUploadModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-1.5 rounded text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded text-xs font-bold transition shadow-sm">
                    Kirim Konfirmasi Pembayaran
                </button>
            </div>
        </form>
    </div>
</div>

{{-- JAVASCRIPT LOGIC INTERAKTIF MODAL --}}
<script>
    function openUploadModal(topupId, amount) {
        // Konfigurasi dinamis target endpoint route update data
        const form = document.getElementById('uploadForm');
        form.action = `{{ url('/eo/finance/upload-proof') }}/${topupId}`;
        
        // Format nominal Rupiah ke teks di dalam modal label
        const formattedAmount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
        document.getElementById('modalAmountLabel').innerText = formattedAmount;

        // Buka modal pop up
        document.getElementById('uploadModal').classList.remove('hidden');
    }

    function closeUploadModal() {
        document.getElementById('uploadModal').classList.add('hidden');
        document.getElementById('uploadForm').reset();
    }
</script>
@endsection