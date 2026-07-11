{{-- POPUP KEPUTUSAN MERCHANDISE JIKA EVENT BATAL --}}
@if($event->status === 'cancelled' && is_null($event->merch_cancel_decision))
<div id="merchDecisionModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100 animate-fade-in-up">
        
        {{-- Header Modal --}}
        <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-5 py-4 text-white">
            <h3 class="text-base font-bold flex items-center gap-2">
                🛍️ Keputusan Batalkan Merchandise
            </h3>
            <p class="text-[11px] text-amber-100 mt-1">Event Anda dibatalkan. Sistem memerlukan instruksi penanganan untuk transaksi merchandise yang sudah dibayar oleh penonton.</p>
        </div>

        {{-- Form Pengisian --}}
        <form action="{{ route('eo.events.merch-decision', $event->id) }}" method="POST" class="p-5">
            @csrf
            
            <div class="space-y-4">
                <p class="text-xs text-gray-600 font-medium leading-relaxed">
                    Bagaimana komoditas merchandise pada event <span class="font-bold text-gray-800">"{{ $event->title }}"</span> ini akan Anda selesaikan?
                </p>

                {{-- Pilihan 1: Alihkan ke Refund Massal --}}
                <label class="block p-3 border rounded-lg border-gray-200 hover:border-amber-500 bg-gray-50 hover:bg-amber-50/30 cursor-pointer transition relative">
                    <input type="radio" name="merch_decision" value="refund" class="absolute top-4 right-4 text-amber-600 focus:ring-amber-500" required>
                    <div class="pr-8">
                        <span class="block text-xs font-bold text-gray-800">💸 Alihkan ke Refund Platform</span>
                        <span class="block text-[11px] text-gray-500 mt-0.5">Seluruh dana merchandise akan dikembalikan penuh ke rekening pembeli via Admin Platform. Saldo Anda akan otomatis terpotong/menjadi utang.</span>
                    </div>
                </label>

                {{-- Pilihan 2: Kirim Mandiri (Gunakan Manifes Absen) --}}
                <label class="block p-3 border rounded-lg border-gray-200 hover:border-amber-500 bg-gray-50 hover:bg-amber-50/30 cursor-pointer transition relative">
                    <input type="radio" name="merch_decision" value="ship_independently" class="absolute top-4 right-4 text-amber-600 focus:ring-amber-500">
                    <div class="pr-8">
                        <span class="block text-xs font-bold text-gray-800">📦 Produksi & Kirim Mandiri</span>
                        <span class="block text-[11px] text-gray-500 mt-0.5">Anda tetap membuat & mendistribusikan merchandise ke alamat pembeli secara mandiri. Sistem akan menyediakan checklist manifes serah-terima/absen merch di dashboard Anda.</span>
                    </div>
                </label>
            </div>

            {{-- Footer Aksi --}}
            <div class="mt-5 pt-3 border-t flex items-center justify-end gap-2">
                <button type="submit" class="w-full bg-gray-900 hover:bg-black text-white text-xs font-bold py-2.5 px-4 rounded-lg shadow transition tracking-wide">
                    Simpan Keputusan Permanen 🔒
                </button>
            </div>
        </form>
    </div>
</div>
@endif