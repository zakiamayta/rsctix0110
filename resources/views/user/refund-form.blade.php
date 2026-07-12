@extends('layouts.app')

@section('title', 'Pengajuan Refund Tiket')

@section('content')
<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 py-8 bg-light">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            {{-- TOMBOL KEMBALI --}}
            <a href="{{ route('user.tickets') }}" class="btn btn-sm btn-white border rounded-pill px-3 mb-4 shadow-sm text-dark">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Riwayat
            </a>

            {{-- KARTU FORMULIR --}}
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                
                {{-- TOP DECORATION BAR --}}
                <div style="height: 6px; background: linear-gradient(to right, #f97316, #fbbf24);"></div>

                <div class="card-body p-4 p-md-5">
                    
                    {{-- HEADER FORM --}}
                    <div class="text-center mb-4">
                        <div class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2 mb-2">
                            <i class="bi bi-shield-exclamation me-1"></i> Form Refund Resmi
                        </div>
                        <h2 class="h4 fw-bold text-dark mb-1">Pengajuan Pengembalian Dana</h2>
                        <p class="text-muted small">Mohon isi data rekening Anda dengan teliti untuk memproses transfer balik.</p>
                    </div>

                    {{-- DETAIL EVENT YANG DI-REFUND --}}
                    <div class="p-3 rounded-4 bg-light mb-4 border border-light">
                        <small class="text-muted d-block uppercase tracking-wider font-semibold small mb-1">Event Terkait</small>
                        <h5 class="fw-bold text-dark mb-1">{{ $transaction->event_title }}</h5>
                        <div class="d-flex justify-content-between text-muted small mt-2 pt-2 border-top">
                            <span>Invoice: <strong>{{ $transaction->kode_unik }}</strong></span>
                            <span>Total Bayar: <strong class="text-orange">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</strong></span>
                        </div>
                    </div>

                    {{-- ALERT ATURAN BISNIS REFUND SYSTEM --}}
                    <div class="alert alert-warning border-0 rounded-4 p-3 mb-4 small text-dark d-flex gap-2">
                        <i class="bi bi-info-circle-fill text-orange h5 mb-0 mt-0.5"></i>
                        <div>
                            <strong>Ketentuan Pengembalian Dana:</strong>
                            <ul class="mb-0 ps-3 mt-1">
                                <li>Dana akan dikembalikan <strong>100% UTUH</strong> senilai <span class="fw-bold">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>.</li>
                                <li>Proses pencairan dana dilakukan secara massal oleh Admin Utama setelah antrean diverifikasi.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- ALUR INPUT FORM --}}
                    <form action="{{ route('buyer.refund.store', $transaction->id) }}" method="POST" id="refundForm">
                        @csrf

                        {{-- NAMA BANK (KLIK UNTUK BUKA PENCARIAN) --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Nama Bank Tujuan</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 border-0 rounded-start-3"><i class="bi bi-bank text-muted"></i></span>
                                <input type="text"
                                       id="bankPickerTrigger"
                                       class="form-control bg-light border-0 rounded-end-3 py-2 @error('bank_name') is-invalid @enderror"
                                       placeholder="Klik untuk pilih bank..."
                                       style="cursor: pointer;"
                                       data-bs-toggle="modal"
                                       data-bs-target="#bankPickerModal"
                                       readonly
                                       required>
                            </div>
                            <input type="hidden" name="bank_name" id="bank_name" value="{{ old('bank_name') }}">
                            @error('bank_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- NOMOR REKENING --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Nomor Rekening</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 border-0 rounded-start-3"><i class="bi bi-credit-card-2-front text-muted"></i></span>
                                <input type="text" 
                                       name="account_number" 
                                       class="form-control bg-light border-0 rounded-end-3 py-2 @error('account_number') is-invalid @enderror" 
                                       placeholder="Masukkan angka nomor rekening" 
                                       value="{{ old('account_number') }}"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                       required>
                            </div>
                            @error('account_number')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- NAMA PEMILIK REKENING --}}
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Nama Pemilik Rekening (Sesuai Buku Tabungan)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 border-0 rounded-start-3"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" 
                                       name="account_name" 
                                       class="form-control bg-light border-0 rounded-end-3 py-2 @error('account_name') is-invalid @enderror" 
                                       placeholder="Contoh: SUPARMAN" 
                                       value="{{ old('account_name') }}"
                                       required>
                            </div>
                            @error('account_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- SUBMIT BUTTON --}}
                        <div class="d-grid gap-2">
                            <button type="submit" 
                                    class="btn btn-warning py-2.5 rounded-pill text-white fw-semibold shadow-sm"
                                    onclick="return confirm('PENTING: Pastikan Nama Bank, Nomor Rekening, dan Nama Pemilik sudah 100% Benar. Kesalahan input data di luar tanggung jawab sistem. Lanjutkan pengajuan?')">
                                <i class="bi bi-send me-1"></i> Kirim Formulir Pengajuan
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

{{-- ==================== MODAL PENCARIAN BANK (RESMI XENDIT) ==================== --}}
<div class="modal fade" id="bankPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-dark">Pilih Bank Tujuan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-light border-end-0 border-0 rounded-start-3"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="bankSearchInput" autocomplete="off"
                           class="form-control bg-light border-0 rounded-end-3 py-2"
                           placeholder="Ketik nama bank... (contoh: BCA, Mandiri, BRI)">
                </div>

                <ul class="list-group list-group-flush" id="bankResultList" style="max-height: 320px; overflow-y: auto;"></ul>

                <p id="bankNoResult" class="text-center text-muted small py-4 d-none mb-0">
                    Bank tidak ditemukan di daftar resmi Xendit.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // Daftar bank resmi Xendit dikirim dari backend (single source of truth)
    const XENDIT_BANKS = @json($banks);

    const bankModalEl   = document.getElementById('bankPickerModal');
    const trigger        = document.getElementById('bankPickerTrigger');
    const hiddenInput    = document.getElementById('bank_name');
    const searchInput    = document.getElementById('bankSearchInput');
    const resultList     = document.getElementById('bankResultList');
    const noResult       = document.getElementById('bankNoResult');

    function renderBankList(keyword = '') {
        const q = keyword.trim().toLowerCase();
        const filtered = XENDIT_BANKS.filter(b =>
            b.name.toLowerCase().includes(q) || b.code.toLowerCase().includes(q)
        );

        resultList.innerHTML = '';

        if (filtered.length === 0) {
            noResult.classList.remove('d-none');
            return;
        }
        noResult.classList.add('d-none');

        filtered.forEach(bank => {
            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 border-bottom rounded-3 mb-1';
            li.style.cursor = 'pointer';
            li.innerHTML = `
                <span class="text-dark">${bank.name}</span>
                <span class="badge bg-light text-muted border">${bank.code}</span>
            `;
            li.addEventListener('click', () => selectBank(bank));
            resultList.appendChild(li);
        });
    }

    function selectBank(bank) {
        hiddenInput.value = bank.code;
        trigger.value = bank.name;
        trigger.classList.remove('is-invalid');

        const modalInstance = bootstrap.Modal.getInstance(bankModalEl) || new bootstrap.Modal(bankModalEl);
        modalInstance.hide();
    }

    bankModalEl.addEventListener('show.bs.modal', () => {
        searchInput.value = '';
        renderBankList('');
        setTimeout(() => searchInput.focus(), 150);
    });

    searchInput.addEventListener('input', (e) => renderBankList(e.target.value));

    // Kalau ada old('bank_name') dari validasi gagal sebelumnya, tampilkan nama banknya kembali
    document.addEventListener('DOMContentLoaded', () => {
        if (hiddenInput.value) {
            const existing = XENDIT_BANKS.find(b => b.code === hiddenInput.value);
            if (existing) trigger.value = existing.name;
        }
    });
</script>
@endsection