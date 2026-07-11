@extends('layouts.owner')

@section('title', 'Detail Withdrawal')

@section('content')
<div class="max-w-6xl mx-auto">
    
    {{-- ─── HEADER SECTION (LEBIH RINGKAS) ─── --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-gray-900">Detail Pengajuan Withdrawal</h1>
            <p class="text-gray-500 text-xs mt-0.5">Review dan approval pencairan dana milik mitra EO.</p>
        </div>

        <div>
            @if($withdrawal->status == 'pending')
                <span class="inline-flex items-center px-3 py-1 rounded-md bg-yellow-50 text-yellow-700 border border-yellow-200 font-semibold text-xs tracking-wider">
                    PENDING
                </span>
            @elseif($withdrawal->status == 'approved')
                <span class="inline-flex items-center px-3 py-1 rounded-md bg-green-50 text-green-700 border border-green-200 font-semibold text-xs tracking-wider">
                    APPROVED
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-md bg-red-50 text-red-700 border border-red-200 font-semibold text-xs tracking-wider">
                    REJECTED
                </span>
            @endif
        </div>
    </div>

    {{-- ─── MAIN TWO-COLUMN GRID ─── --}}
    <div class="grid lg:grid-cols-3 gap-4 items-start">

        {{-- LEFT COLUMN: INFORMASI UTAMA & LAMPIRAN BERKAS --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl border shadow-sm p-4 space-y-4">
                
                <div class="border-b pb-2">
                    <h2 class="font-bold text-sm text-gray-800">Informasi Utama Pengajuan</h2>
                </div>

                {{-- Grid Data Manifes --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <span class="block text-xs font-semibold uppercase text-gray-400">Nama Event</span>
                        <span class="font-bold text-gray-900">{{ $withdrawal->event->title ?? '-' }}</span>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold uppercase text-gray-400">Event Organizer (Mitra)</span>
                        <span class="font-semibold text-gray-700">{{ $withdrawal->eo->nama_badan_usaha ?? '-' }}</span>
                    </div>

                    <div class="sm:col-span-2 bg-orange-50/60 border border-orange-100 rounded-lg p-3 my-1 flex items-center justify-between">
                        <div>
                            <span class="block text-xs font-bold uppercase text-orange-600/80">Nominal Penarikan (Withdrawal)</span>
                            <span class="text-2xl font-black text-orange-600">Rp {{ number_format($withdrawal->amount,0,',','.') }}</span>
                        </div>
                        <div class="text-right text-xs text-gray-400">
                            <span class="block font-medium">Diajukan Pada:</span>
                            <span class="font-semibold text-gray-600">{{ $withdrawal->created_at->format('d M Y, H:i') }}</span>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <span class="block text-xs font-semibold uppercase text-gray-400 mb-1">Catatan Pengajuan (Dari EO)</span>
                        <div class="bg-gray-50 border p-3 rounded-lg text-xs text-gray-600 italic">
                            "{!! $withdrawal->note ? e($withdrawal->note) : 'Tidak ada catatan dari mitra EO.' !!}"
                        </div>
                    </div>

                    @if($withdrawal->owner_note)
                    <div class="sm:col-span-2">
                        <span class="block text-xs font-semibold uppercase text-gray-400 mb-1">Catatan Aksi (Dari Owner)</span>
                        <div class="bg-yellow-50/50 border border-yellow-200 p-3 rounded-lg text-xs text-gray-700">
                            {{ $withdrawal->owner_note }}
                        </div>
                    </div>
                    @endif

                    @if($withdrawal->approved_at)
                    <div>
                        <span class="block text-xs font-semibold uppercase text-gray-400">Waktu Validasi Eksekusi</span>
                        <span class="font-medium text-gray-700 text-xs">{{ \Carbon\Carbon::parse($withdrawal->approved_at)->format('d M Y - H:i') }}</span>
                    </div>
                    @endif
                </div>

                {{-- ─── SUB-SECTION: DOKUMEN & BERKAS DIGITAL ─── --}}
                <div class="border-t pt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- Invoice --}}
                    <div class="p-3 border rounded-lg flex items-center justify-between bg-gray-50/30">
                        <div class="min-w-0">
                            <span class="block text-xs font-bold text-gray-700">Berkas Invoice Tagihan</span>
                            <span class="text-[10px] text-gray-400 block truncate">Diupload oleh mitra EO</span>
                        </div>
                        @if($withdrawal->invoice_file)
                            <button 
                                type="button" 
                                onclick="openOwnerModal('{{ asset($withdrawal->invoice_file) }}', 'Berkas Invoice Tagihan')" 
                                class="inline-flex items-center px-3 py-1.5 bg-gray-800 hover:bg-gray-900 text-white rounded-md font-medium text-xs transition cursor-pointer"
                            >
                                Open
                            </button>
                        @else
                            <span class="text-xs font-semibold text-red-500">Kosong</span>
                        @endif
                    </div>

                    {{-- Bukti Transfer --}}
                    @if($withdrawal->transfer_proof)
                    <div class="p-3 border border-green-200 rounded-lg flex items-center justify-between bg-green-50/10">
                        <div class="min-w-0">
                            <span class="block text-xs font-bold text-green-700">Bukti Transfer Resmi</span>
                            <span class="text-[10px] text-gray-400 block truncate">Arsip validasi sistem</span>
                        </div>
                        <button 
                            type="button" 
                            onclick="openOwnerModal('{{ asset($withdrawal->invoice_file) }}', 'Bukti Transfer Resmi')" 
                            class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-md font-medium text-xs transition cursor-pointer"
                        >
                            Lihat Gambar
                        </button>
                    </div>
                    @endif
                </div>

            </div>
        </div>

        {{-- RIGHT COLUMN: KEUANGAN EVENT & REKENING BANK (MERGED PANEL) --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl border shadow-sm p-4 divide-y divide-gray-100 space-y-4">
                
                {{-- Modul Wallet --}}
                <div class="space-y-2.5">
                    <h3 class="font-bold text-sm text-gray-800">Ringkasan Finansial Event</h3>
                    <div class="grid grid-cols-1 gap-2 text-xs">
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                            <span class="text-gray-500 font-medium">Available Balance</span>
                            <span class="font-bold text-green-600">Rp {{ number_format($wallet->available_balance ?? 0,0,',','.') }}</span>
                        </div>
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                            <span class="text-gray-500 font-medium">Held Balance</span>
                            <span class="font-bold text-orange-500">Rp {{ number_format($wallet->held_balance ?? 0,0,',','.') }}</span>
                        </div>
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                            <span class="text-gray-500 font-medium">Negative Balance</span>
                            <span class="font-bold text-red-500">Rp {{ number_format($wallet->negative_balance ?? 0,0,',','.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Modul Rekening Bank --}}
                <div class="pt-3 space-y-2">
                    <h3 class="font-bold text-sm text-gray-800">Rekening Tujuan Pencairan</h3>
                    <div class="bg-gray-50/50 border border-dashed rounded-lg p-3 text-xs space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-gray-400 font-medium">Nama Bank:</span>
                            <span class="font-bold text-gray-700">{{ $withdrawal->eo->bank_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400 font-medium">Pemilik:</span>
                            <span class="font-semibold text-gray-700 truncate max-w-[150px] text-right" title="{{ $withdrawal->eo->account_name ?? '-' }}">
                                {{ $withdrawal->eo->account_name ?? '-' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center pt-1 border-t border-gray-200 border-dashed">
                            <span class="text-gray-400 font-medium">No. Rekening:</span>
                            <span class="font-mono font-bold text-sm text-gray-900 tracking-wider">{{ $withdrawal->eo->account_number ?? '-' }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    {{-- ─── BOTTOM SECTION: AKSI FORM (HANYA MUNCUL JIKA PENDING) ─── --}}
    @if($withdrawal->status == 'pending')
    <div class="grid md:grid-cols-2 gap-4 mt-4">

        {{-- FORM PERSETUJUAN (APPROVE) --}}
        <form action="{{ route('owner.withdrawals.approve',$withdrawal->id) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl border p-4 flex flex-col justify-between">
            @csrf
            <div>
                <h2 class="font-bold text-green-700 text-sm mb-3 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    Konfirmasi Aksi Setujui (Approve)
                </h2>

                <div class="mb-3">
                    <label class="block mb-1 text-xs font-semibold text-gray-600">Upload Bukti Transfer <span class="text-red-500">*</span></label>
                    <input type="file" name="transfer_proof" required class="w-full text-xs border rounded-lg p-2 focus:ring-1 focus:ring-green-500 outline-none bg-gray-50 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                </div>

                <div class="mb-4">
                    <label class="block mb-1 text-xs font-semibold text-gray-600">Catatan Tambahan Owner <span class="text-gray-400 font-normal">(Opsional)</span></label>
                    <textarea name="owner_note" rows="2" placeholder="Masukkan detail transaksi, nomor referensi, dll..." class="w-full border text-xs rounded-lg p-2 focus:ring-1 focus:ring-green-500 outline-none resize-none"></textarea>
                </div>
            </div>

            <button class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-bold text-xs shadow-sm transition">
                Eksekusi Pencairan & Approve
            </button>
        </form>

        {{-- FORM PENOLAKAN (REJECT) --}}
        <form action="{{ route('owner.withdrawals.reject',$withdrawal->id) }}" method="POST" class="bg-white rounded-xl border p-4 flex flex-col justify-between">
            @csrf
            <div>
                <h2 class="font-bold text-red-700 text-sm mb-3 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    Konfirmasi Aksi Tolak (Reject)
                </h2>

                <div class="mb-4">
                    <label class="block mb-1 text-xs font-semibold text-gray-600">Alasan Penolakan <span class="text-red-500">*</span></label>
                    <textarea name="owner_note" required rows="5" placeholder="Tulis alasan penolakan secara jelas agar dapat dievaluasi kembali oleh mitra EO..." class="w-full border text-xs rounded-lg p-2 focus:ring-1 focus:ring-red-500 outline-none resize-none"></textarea>
                </div>
            </div>

            <button class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg font-bold text-xs shadow-sm transition">
                Tolak Pengajuan Penarikan
            </button>
        </form>

    </div>
    @endif

</div>

{{-- ─── TAILWIND BASED MODAL CONTAINER ─── --}}
<div id="ownerModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 opacity-0 pointer-events-none transition-opacity duration-200">
    <div class="bg-white rounded-xl border shadow-xl w-[90%] max-w-4xl max-h-[85vh] flex flex-col overflow-hidden transform scale-95 transition-transform duration-200" id="ownerModalContainer">
        
        {{-- Modal Header --}}
        <div class="px-5 py-3.5 bg-gray-50 border-b flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-800" id="ownerModalTitle">Pratinjau Berkas</h3>
            <button type="button" onclick="closeOwnerModal(true)" class="text-gray-400 hover:text-gray-600 text-xl font-bold line-height-1">&times;</button>
        </div>
        
        {{-- Modal Body --}}
        <div class="p-4 overflow-y-auto bg-gray-100 flex items-center justify-center min-h-[350px]" id="ownerModalBody">
            <!-- Rendered by JS -->
        </div>
    </div>
</div>

<script>
    function openOwnerModal(fileUrl, title) {
        const modal = document.getElementById('ownerModal');
        const container = document.getElementById('ownerModalContainer');
        const modalTitle = document.getElementById('ownerModalTitle');
        const modalBody = document.getElementById('ownerModalBody');
        
        modalTitle.innerText = title;
        modalBody.innerHTML = ''; // Clear content
        
        const extension = fileUrl.split('.').pop().toLowerCase();
        
        if (extension === 'pdf') {
            modalBody.innerHTML = `<iframe src="${fileUrl}" class="w-full h-[70vh] rounded-lg border-0 bg-white"></iframe>`;
        } else if (['jpg', 'jpeg', 'png', 'webp'].includes(extension)) {
            modalBody.innerHTML = `<img src="${fileUrl}" class="max-w-full max-h-[70vh] rounded-lg object-contain shadow-sm" alt="${title}">`;
        } else {
            modalBody.innerHTML = `
                <div class="text-center p-6 bg-white rounded-xl border">
                    <p class="text-xs font-medium text-gray-500 mb-3">Format file ini tidak mendukung pratinjau langsung.</p>
                    <a href="${fileUrl}" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-lg font-bold text-xs transition">
                        Buka / Download Berkas
                    </a>
                </div>`;
        }
        
        // Show modal animation
        modal.classList.remove('opacity-0', 'pointer-events-none');
        container.classList.remove('scale-95');
        container.classList.add('scale-100');
        document.body.classList.add('overflow-hidden');
    }

    function closeOwnerModal(force = false) {
        const modal = document.getElementById('ownerModal');
        const container = document.getElementById('ownerModalContainer');
        
        if (force === true || event.target.id === 'ownerModal') {
            modal.classList.add('opacity-0', 'pointer-events-none');
            container.classList.remove('scale-100');
            container.classList.add('scale-95');
            document.body.classList.remove('overflow-hidden');
            
            setTimeout(() => {
                document.getElementById('ownerModalBody').innerHTML = '';
            }, 200);
        }
    }

    // Close on overlay click
    document.getElementById('ownerModal').addEventListener('click', function(e) {
        if(e.target === this) closeOwnerModal(true);
    });

    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeOwnerModal(true);
    });
</script>
@endsection