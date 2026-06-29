@extends('layouts.admin') 

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<div class="container-fluid py-3 px-3" style="font-family: 'Poppins', sans-serif;">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-0" style="font-weight: 700 !important;">📊 Riwayat Kas & Dompet Platform</h3>
            <p class="text-muted small mb-0" style="font-weight: 400;">Aliran dana pajak masuk (gross) dan beban operasional transfer Xendit terpantau langsung.</p>
        </div>
        
        <div>
            @if(auth()->user()->role === 'admin')
                <span class="badge bg-info-subtle text-info border border-info px-3 py-2 rounded-2 fw-semibold w-100 text-center" style="font-weight: 600;">
                    <i class="fas fa-user-shield me-1"></i> Otoritas Admin (Akses Penuh)
                </span>
            @endif
            @if(auth()->user()->role === 'owner')
                <span class="badge bg-warning-subtle text-warning border border-warning px-3 py-2 rounded-2 fw-semibold w-100 text-center" style="font-weight: 600;">
                    <i class="fas fa-eye me-1"></i> Hak Akses Owner (Read-Only)
                </span>
            @endif
        </div>
    </div>
    
    <hr class="text-secondary opacity-25 mb-3">

    {{-- CARD STATISTIK UTAMA --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm" style="border-radius: 8px;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small text-uppercase tracking-wider" style="font-weight: 600; font-size: 0.75rem;">Total Pajak Masuk (Gross)</span>
                        <div class="p-2 bg-success-subtle text-success rounded-2" style="--bs-bg-opacity: 0.12;">
                            <i class="fas fa-arrow-down-long fa-lg"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold text-dark mb-0" style="font-weight: 700;">Rp {{ number_format($wallet->total_service_tax_earned ?? 0, 0, ',', '.') }}</h2>
                    <div class="text-success small mt-1" style="font-size: 0.8rem; font-weight: 500;">
                        <i class="fas fa-check-circle me-1"></i>Akumulasi Service Tax Tiket Paid
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm" style="border-radius: 8px;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small text-uppercase tracking-wider" style="font-weight: 600; font-size: 0.75rem;">Total Biaya Operasional Refund</span>
                        <div class="p-2 bg-danger-subtle text-danger rounded-2" style="--bs-bg-opacity: 0.12;">
                            <i class="fas fa-arrow-up-long fa-lg"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold text-dark mb-0" style="font-weight: 700;">Rp {{ number_format($wallet->total_refund_fees_spent ?? 0, 0, ',', '.') }}</h2>
                    <div class="text-danger small mt-1" style="font-size: 0.8rem; font-weight: 500;">
                        <i class="fas fa-exclamation-triangle me-1"></i>Beban Flat Rp2.500/Transaksi Ter-refund
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 text-white shadow-sm" style="border-radius: 8px; background: linear-gradient(135deg, #0f172a, #1e3a8a);">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-white-50 small text-uppercase tracking-wider" style="font-weight: 600; font-size: 0.75rem;">Saldo Bersih Saat Ini (Net)</span>
                        <div class="p-2 bg-white bg-opacity-10 text-white rounded-2">
                            <i class="fas fa-wallet fa-lg"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-0" style="font-weight: 700;">Rp {{ number_format($wallet->current_balance ?? 0, 0, ',', '.') }}</h2>
                    <div class="text-white-50 small mt-1" style="font-size: 0.8rem; font-weight: 500;">
                        <i class="fas fa-vault me-1"></i>Kas Bersih Tersedia di Platform
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABEL 1: JURNAL MUTASI KAS REAL-TIME --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 8px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 px-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-0" style="font-weight: 600;"><i class="fas fa-exchange-alt text-secondary me-2"></i>Jurnal Mutasi Kas Real-Time</h5>
                <p class="text-muted small mb-0" style="font-weight: 400;">Riwayat kronologis sumber pendapatan masuk dan beban operasional keluar.</p>
            </div>
            <div>
                <button onclick="window.location.reload();" class="btn btn-sm btn-light border text-secondary fw-semibold" style="font-weight: 500;">
                    <i class="fas fa-sync-alt me-1"></i> Refresh Data
                </button>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100">
                    <thead class="table-light text-uppercase small" style="--bs-table-bg: #f8fafc; color: #475569; font-weight: 600; font-size: 0.8rem;">
                        <tr>
                            <th class="py-2.5 px-3" style="width: 18%;">Waktu Jurnal</th>
                            <th class="py-2.5 px-2" style="width: 20%;">ID Referensi / Invoice</th>
                            <th class="py-2.5 px-2">Keterangan Aktivitas Finansial</th>
                            <th class="py-2.5 px-2" style="width: 15%;">Jenis Aliran</th>
                            <th class="py-2.5 px-3 text-end" style="width: 18%;">Nominal Mutasi</th>
                        </tr>
                    </thead>
                    <tbody class="small" style="font-weight: 400;">
                        @if(isset($mutations) && count($mutations) > 0)
                            @foreach($mutations as $log)
                                <tr>
                                    {{-- PERBAIKAN: Menggunakan trx_date sesuai properti database hasil query --}}
                                    <td class="py-2.5 px-3 text-muted">
                                        {{ $log->trx_date ? date('Y-m-d H:i:s', strtotime($log->trx_date)) : '-' }}
                                    </td>
                                    <td class="py-2.5 px-2 fw-bold text-secondary" style="font-weight: 600;">{{ $log->reference_code }}</td>
                                    <td class="py-2.5 px-2 text-dark">{{ $log->description }}</td>
                                    <td class="py-2.5 px-2">
                                        {{-- Menyesuaikan dengan nilai 'income' / 'expense' dari query gabungan --}}
                                        @if(strtolower($log->type) === 'income')
                                            <span class="badge bg-success-subtle text-success px-2 py-1 rounded border border-success border-opacity-10 fw-semibold">MASUK (TAX)</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger px-2 py-1 rounded border border-danger border-opacity-10 fw-semibold">KELUAR (BIAYA)</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-end fw-bold {{ strtolower($log->type) === 'income' ? 'text-success' : 'text-danger' }}" style="font-weight: 600;">
                                        {{ strtolower($log->type) === 'income' ? '+' : '-' }} Rp {{ number_format($log->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="mb-2">
                                        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="text-muted opacity-50 d-inline-block">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.008 1.24l.885 1.77a2.25 2.25 0 002.007 1.24h1.98a2.25 2.25 0 002.007-1.24l.885-1.77a2.25 2.25 0 012.007-1.24h3.86m-18 0h18a2.25 2.25 0 012.25 2.25v4.25A2.25 2.25 0 0118 21H6a2.25 2.25 0 01-2.25-2.25V15.25a2.25 2.25 0 012.25-2.25zm0-4.5h18A2.25 2.25 0 0121 11.25v.75H3v-.75A2.25 2.25 0 015.25 9h13.5zM9 5.25h6M9 2.25h6"></path>
                                        </svg>
                                    </div>
                                    <span class="small d-block text-secondary" style="font-weight: 500;">Belum ada riwayat aktivitas mutasi keuangan (Jurnal Kosong).</span>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TABEL 2: TOP 5 EVENT PENYEBAB REFUND --}}
    <div class="card border-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 px-3">
            <h5 class="fw-bold text-dark mb-0" style="font-weight: 600;"><i class="fas fa-exclamation-triangle text-danger me-2"></i>⚠️ Top 5 Event Pemicu Kebocoran Biaya Administrasi Refund</h5>
            <p class="text-muted small mb-0" style="font-weight: 400;">Analisis event dengan tingkat pembalikan transaksi tertinggi yang memotong kas operasional.</p>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100">
                    <thead class="table-light text-uppercase small" style="--bs-table-bg: #f8fafc; color: #475569; font-weight: 600; font-size: 0.8rem;">
                        <tr>
                            <th class="py-2.5 px-3">Nama Event Resmi</th>
                            <th class="py-2.5 px-2 text-center" style="width: 25%;">Volume Kasus Pengajuan</th>
                            <th class="py-2.5 px-3 text-end" style="width: 30%;">Beban Finansial Ditanggung Platform</th>
                        </tr>
                    </thead>
                    <tbody class="small" style="font-weight: 400;">
                        @if(isset($refundStats) && count($refundStats) > 0)
                            @foreach($refundStats as $stat)
                                <tr>
                                    <td class="py-2.5 px-3 fw-bold text-dark" style="font-weight: 600;">{{ $stat->event_name }}</td>
                                    <td class="py-2.5 px-2 text-center">
                                        <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-10 px-2 py-1 fw-semibold">
                                            {{ $stat->total_kasus_refund }} Kasus Terverifikasi
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-end fw-bold text-danger" style="font-weight: 600;">
                                        Rp {{ number_format($stat->total_biaya_hangus, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    <span class="small d-block text-secondary" style="font-weight: 500; font-style: italic;">Sistem dalam keadaan bersih dari kebocoran/pengeluaran biaya refund.</span>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection