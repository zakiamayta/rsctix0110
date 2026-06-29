@extends('layouts.admin')

@section('content')
<div class="w-full p-4 bg-gray-50 text-gray-800 text-sm">
    
    {{-- BAR HEADER ATAS --}}
    <div class="mb-5 border-b pb-3 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-xl font-bold tracking-tight">Ruang Kendali Finansial Event</h1>
            <p class="text-xs text-gray-500">Otoritas audit penyesuaian saldo, instigator rekues top-up dana talangan, dan verifikasi manifest transfer EO.</p>
        </div>
        <a href="{{ route('admin.finance.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-1.5 px-3 rounded text-xs transition inline-flex items-center gap-1">
            ⬅️ Kembali ke List
        </a>
    </div>

    {{-- ALERTS GLOBAL BANNER NOTIFIKASI --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-3 py-2 rounded mb-4 text-xs flex items-center justify-between shadow-sm">
            <span>✅ {{ session('success') }}</span>
            <button class="text-green-500 hover:text-green-700 font-bold" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded mb-4 text-xs flex items-center justify-between shadow-sm">
            <span>❌ {{ session('error') }}</span>
            <button class="text-green-500 hover:text-green-700 font-bold" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif

    {{-- AREA KONTROL UTAMA --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">
        
        {{-- KOTAK I: DETAIL PROTEKSI & RESUME FINANCIAL DOMPET --}}
        <div class="bg-white border border-gray-200 rounded p-4 shadow-sm flex flex-col justify-between">
            <div>
                <div class="text-[10px] font-bold uppercase text-gray-400 tracking-wider mb-2">Informasi Umum Event</div>
                <div class="space-y-2 text-xs">
                    <div><span class="text-gray-400">Judul Event:</span> <strong class="text-gray-900 text-sm block font-sans tracking-tight">{{ $event->title }}</strong></div>
                    <div>
                        <span class="text-gray-400">Status Event Saat Ini:</span> 
                        @if($event->event_status === 'canceled')
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">CANCELED / BATAL</span>
                        @elseif($event->event_status === 'approved' && $event->is_rescheduled > 0)
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">ACTIVE/RESCHEDULED</span>
                        @else
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 border border-green-200">{{ strtoupper($event->event_status) }}</span>
                        @endif
                    </div>
                    <div class="pt-1">
                        <span class="text-gray-400">Status Proteksi Penarikan:</span> 
                        @if(($event->withdraw_locked ?? 0) == 1)
                            <span class="text-red-600 font-bold">🔒 Terkunci (Withdraw Locked)</span>
                        @else
                            <span class="text-green-600 font-bold">✅ Terbuka (Normal)</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100">
                <div class="bg-gray-50 border rounded p-3 text-xs font-mono">
                    <div class="text-[10px] font-bold uppercase text-gray-400 font-sans tracking-wider mb-1">Total Saldo Tersedia (+ Held)</div>
                    <div class="text-lg font-bold text-indigo-700">Rp {{ number_format(($event->available_balance ?? 0) + ($event->held_balance ?? 0), 0, ',', '.') }}</div>
                    <div class="text-[10px] text-gray-500 mt-1 font-sans">
                        Tersedia: Rp {{ number_format($event->available_balance ?? 0, 0, ',', '.') }} | Ditahan (Held): Rp {{ number_format($event->held_balance ?? 0, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- KOTAK II: INSTIGATOR REQUEST TAGIHAN TOP UP KE EO --}}
        <div class="bg-white border border-gray-200 rounded p-4 shadow-sm">
            <div class="text-[10px] font-bold uppercase text-gray-400 tracking-wider mb-3">📌 Kirim Instruksi Tagihan Top-Up</div>
            
            <form action="{{ route('admin.finance.requestTopup', $event->id) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-gray-600 mb-1">Jumlah Dana yang Diminta (Rp):</label>
                    <input type="number" name="amount_requested" required min="1000" placeholder="Contoh: 15000000" class="w-full rounded border border-gray-300 p-2 font-mono text-xs focus:outline-none focus:border-indigo-500 bg-white text-gray-800">
                    <span class="text-[10px] text-gray-400 mt-0.5 block leading-normal">Tagihan ini akan otomatis muncul di dashboard EO terkait.</span>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-gray-600 mb-1">Catatan Instruksi Admin:</label>
                    <textarea name="admin_note" rows="2" placeholder="Contoh: Saldo dompet event Anda minus akibat pengajuan refund massal..." class="w-full rounded border border-gray-300 p-2 text-xs focus:outline-none focus:border-indigo-500 bg-white text-gray-800"></textarea>
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded text-xs transition shadow-sm flex items-center justify-center gap-1.5">
                    🚀 Tembak Tagihan ke EO
                </button>
            </form>
        </div>

    </div>

    {{-- SEKSYEN BAWAH: LOG TABEL TRANSAKSI & VERIFIKASI BUKTI TRANSFER --}}
    <div class="bg-white border border-gray-200 rounded overflow-hidden shadow-sm">
        <div class="bg-gray-100 px-3 py-2.5 border-b border-gray-200 font-bold text-gray-700 uppercase text-xs tracking-wider flex items-center gap-1">
            📋 Log Riwayat Pengajuan & Verifikasi Bukti Transfer Top Up
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-xs font-bold uppercase text-gray-500">
                        <th class="p-3 border-r w-44">Waktu Log</th>
                        <th class="p-3 border-r text-right w-44">Jumlah Request</th>
                        <th class="p-3 border-r text-center w-36">Bukti Transfer</th>
                        <th class="p-3 border-r text-center w-40">Status Verifikasi</th>
                        <th class="p-3 border-r">Catatan Pembukuan</th>
                        <th class="p-3 text-center w-48">Tindakan Persetujuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($topups as $t)
                    <tr class="hover:bg-gray-50/40">
                        <td class="p-3 border-r text-gray-500 font-mono text-[11px]">
                            {{ date('d M Y - H:i', strtotime($t->created_at)) }} WIB
                        </td>
                        <td class="p-3 border-r text-right font-mono font-bold text-gray-900">
                            Rp {{ number_format($t->amount_requested, 0, ',', '.') }}
                        </td>
      <td class="p-3 border-r text-center">
    @if($t->proof_of_transfer)
        <button type="button" 
                data-proof="/{{ $t->proof_of_transfer }}"
                onclick="openProofModal(this)" 
                class="bg-gray-100 hover:bg-gray-200 border text-gray-700 px-2 py-1 rounded text-[11px] font-medium transition inline-flex items-center gap-1 shadow-sm">
            📸 Lihat Gambar
        </button>
    @else
        <span class="text-gray-400 italic text-[11px]">Belum Upload</span>
    @endif
</td>
                        </td>
                        <td class="p-3 border-r text-center">
                            @if($t->status === 'approved')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-800 border border-green-200">✅ APPROVED</span>
                            @elseif($t->status === 'rejected')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">❌ REJECTED</span>
                            @elseif($t->status === 'pending_verification')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">⏳ PENDING VERIFY</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">📋 REQUESTED</span>
                            @endif
                        </td>
                        <td class="p-3 border-r text-gray-600 text-xs" style="max-w: 200px; word-wrap: break-word; white-space: normal;">
                            {{ $t->admin_note ?? '-' }}
                        </td>
                        <td class="p-3 text-center">
                            @if($t->status === 'pending_verification')
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" onclick="openActionModal('{{ $t->id }}', 'approved')" class="bg-green-600 hover:bg-green-700 text-white font-bold py-1 px-2.5 rounded text-[11px] transition shadow-sm">
                                        Setuju
                                    </button>
                                    <button type="button" onclick="openActionModal('{{ $t->id }}', 'rejected')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-2.5 rounded text-[11px] transition shadow-sm">
                                        Tolak
                                    </button>
                                </div>
                            @elseif($t->status === 'approved')
                                <span class="text-green-600 font-medium italic text-[11px]">Selesai & Masuk Saldo</span>
                            @elseif($t->status === 'rejected')
                                <span class="text-red-500 italic text-[11px]">Bukti Ditolak</span>
                            @else
                                <span class="text-gray-400 italic text-[11px]">Menunggu Aksi EO</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-5 text-center text-gray-400 italic">Belum ada riwayat aktivitas top-up untuk entitas event ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- MODAL A: PRATINJAU GAMBAR STRUK BUKTI TRANSFER --}}
<div id="proofModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded overflow-hidden max-w-lg w-full shadow-2xl relative">
        <div class="bg-gray-900 text-white p-3 font-bold text-xs flex justify-between items-center">
            <span>Pratinjau Gambar Bukti Pembayaran EO</span>
            <button type="button" onclick="closeProofModal()" class="text-gray-400 hover:text-white font-bold text-lg">×</button>
        </div>
        <div class="p-4 bg-gray-100 flex justify-center items-center overflow-auto max-h-[70vh]">
            <img id="modalImage" src="" alt="Bukti Transfer" class="max-w-full h-auto rounded shadow border">
        </div>
        <div class="p-3 bg-gray-50 border-t flex justify-end">
            <button type="button" onclick="closeProofModal()" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-1.5 rounded text-xs font-bold transition">
                Tutup Jendela
            </button>
        </div>
    </div>
</div>

{{-- MODAL B: EKSEKUSI PERSETUJUAN / PENOLAKAN VERIFIKASI --}}
<div id="actionModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded overflow-hidden max-w-md w-full shadow-2xl relative">
        <div class="bg-gray-900 text-white p-3 font-bold text-xs flex justify-between items-center">
            <span id="modalActionTitle">Konfirmasi Tindakan Pembukuan</span>
            <button type="button" onclick="closeActionModal()" class="text-gray-400 hover:text-white font-bold text-lg">×</button>
        </div>
        
        <form id="actionForm" method="POST" action="">
            @csrf
            
            {{-- PERBAIKAN UTAMA: Mengubah name="action" menjadi name="status" agar sinkron dengan $request->validate() di Controller --}}
            <input type="hidden" name="status" id="modalStatusInput" value="">

            <div class="p-4 bg-gray-50/50">
                <p class="text-xs text-gray-600 mb-3 leading-relaxed" id="modalActionDesc">
                    Apakah Anda yakin ingin memproses data transfer ini? Berikan catatan pembukuan tambahan jika diperlukan.
                </p>
                <div>
                    <label class="block text-[11px] font-bold text-gray-600 mb-1">Catatan Tambahan Admin (Opsional):</label>
                    <textarea name="admin_note" id="modalAdminNote" rows="3" placeholder="Contoh: Transfer sudah sesuai dan mutasi bank valid / Struk palsu terindikasi blur..." class="w-full rounded border border-gray-300 p-2 text-xs focus:outline-none focus:border-indigo-500 bg-white text-gray-800"></textarea>
                </div>
            </div>
            
            <div class="p-3 bg-gray-100 border-t flex justify-end gap-2">
                <button type="button" onclick="closeActionModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-1.5 rounded text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" id="modalSubmitBtn" class="text-white px-4 py-1.5 rounded text-xs font-bold transition shadow-sm">
                    Konfirmasi Aksi
                </button>
            </div>
        </form>
    </div>
</div>

{{-- JAVASCRIPT LOGIC INTERAKTIF MODAL CONTROL --}}
<script>
    // Fungsi Modal Gambar Struk
function openProofModal(button) {
    // Mengambil path gambar dari atribut data-proof tombol yang diklik
    const imgUrl = button.getAttribute('data-proof');
    
    // Set src pada element image di dalam modal
    document.getElementById('modalImage').src = imgUrl;
    
    // Tampilkan modal
    document.getElementById('proofModal').classList.remove('hidden');
}
    function closeProofModal() {
        document.getElementById('proofModal').classList.add('hidden');
        document.getElementById('modalImage').src = '';
    }

    // Fungsi Modal Eksekusi Verifikasi
    function openActionModal(id, statusType) {
        // Normalisasi string parameter input dari tombol pemicu agar selalu presisi
        if (statusType === 'approve') statusType = 'approved';
        if (statusType === 'reject') statusType = 'rejected';

        // Mengisi action rute pengiriman form dan nilai input hidden secara dinamis
        document.getElementById('actionForm').action = `/admin/finance/topup/${id}/${statusType}`;
        document.getElementById('modalStatusInput').value = statusType;

        const title = document.getElementById('modalActionTitle');
        const desc = document.getElementById('modalActionDesc');
        const btn = document.getElementById('modalSubmitBtn');

        if (statusType === 'approved') { 
            title.innerText = '🟢 Setujui Pembayaran Top-Up';
            desc.innerHTML = 'Dengan menyetujui, <strong>sistem secara otomatis akan menambahkan nominal dana ke saldo dompet event</strong> ini serta melakukan pemotongan sisa kewajiban utang terkait.';
            btn.innerText = 'Ya, Validasi & Tambah Saldo';
            btn.className = 'bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 rounded text-xs font-bold transition shadow-sm';
        } else {
            title.innerText = '🔴 Tolak Bukti Transfer';
            desc.innerHTML = 'Apakah Anda yakin ingin <strong>menolak bukti transfer</strong> ini? EO akan menerima status penolakan dan diminta mengunggah kembali struk pembayarannya.';
            btn.innerText = 'Ya, Tolak Struk';
            btn.className = 'bg-red-600 hover:bg-red-700 text-white px-4 py-1.5 rounded text-xs font-bold transition shadow-sm';
        }

        document.getElementById('actionModal').classList.remove('hidden');
    }

    function closeActionModal() {
        document.getElementById('actionModal').classList.add('hidden');
        document.getElementById('actionForm').action = '';
        document.getElementById('modalStatusInput').value = '';
        document.getElementById('modalAdminNote').value = '';
    }
</script>
@endsection