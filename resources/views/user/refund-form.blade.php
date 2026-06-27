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
                    <form action="{{ route('buyer.refund.store', $transaction->id) }}" method="POST">
                        @csrf

                        {{-- NAMA BANK --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Nama Bank Tujuan</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 border-0 rounded-start-3"><i class="bi bi-bank text-muted"></i></span>
                                <input type="text" 
                                       name="bank_name" 
                                       class="form-control bg-light border-0 rounded-end-3 py-2 @error('bank_name') is-invalid @enderror" 
                                       placeholder="Contoh: BCA, Mandiri, BRI, BNI" 
                                       value="{{ old('bank_name') }}"
                                       required>
                            </div>
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
                        <div class="mb-3">
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

                        {{-- ALASAN REFUND --}}
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Alasan Refund <span class="text-muted fw-normal">(Opsional)</span></label>
                            <textarea name="refund_reason" 
                                      class="form-control bg-light border-0 rounded-3" 
                                      rows="3" 
                                      placeholder="Tulis alasan pembatalan tiket jika diperlukan...">{{ old('refund_reason') }}</textarea>
                            @error('refund_reason')
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
@endsection