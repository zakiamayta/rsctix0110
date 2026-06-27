@extends('layouts.eo')

@section('title', 'Dashboard Saldo Merchandise')

@section('content')

<style>
.wallet-card{
    border-radius:20px;
    overflow:hidden;
    border:none;
    box-shadow:0 8px 30px rgba(0,0,0,.06);
}

.wallet-header{
    background:linear-gradient(135deg,#ff7a00,#ff9b3d);
    color:white;
}

.wallet-info{
    background:white;
}

.badge-h10{
    background:#e8f1ff;
    color:#2563eb;
    border:1px solid #bfdbfe;
}

.badge-finished{
    background:#eafaf1;
    color:#16a34a;
    border:1px solid #bbf7d0;
}

.badge-normal{
    background:#fff7ed;
    color:#ea580c;
    border:1px solid #fed7aa;
}

.event-card{
    border:none;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 4px 20px rgba(0,0,0,.04);
}

.withdraw-box{
    border-radius:12px;
}

.fin-row{
    font-size:14px;
    margin-bottom:6px;
}
</style>

<div class="container py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">
            Dashboard Saldo Merchandise
        </h3>
        <small class="text-muted">
            Monitoring pencairan dana merchandise event
        </small>
    </div>

    <a href="{{ route('eo.merch-withdraw.history') }}" class="btn btn-outline-secondary">
        <i class="fas fa-history"></i>
        Lihat Riwayat Withdraw Merch
    </a>
</div>

{{-- Sesi Alert Flash Message --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- CARD ORANGE UTAMA --}}
<div class="card wallet-card mb-4">
    <div class="wallet-header p-4">
        <div class="text-uppercase small fw-bold opacity-75">
            Total Global Hak Tarik (Merch)
        </div>
        <h1 class="fw-bold mb-3">
            Rp {{ number_format($totalGlobalAvailable, 0, ',', '.') }}
        </h1>
        <div>
            <i class="fas fa-university"></i>
            {{ $bank_name }} | {{ $account_number }} a/n {{ $account_name }}
        </div>
    </div>

    <div class="wallet-info p-4">
        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">Total Saldo Ditahan (Merch)</small>
                <div class="fw-bold text-warning fs-5">
                    Rp {{ number_format($totalGlobalHeld ?? 0, 0, ',', '.') }}
                </div>
            </div>
            <div class="col-md-6">
                <small class="text-muted">Akumulasi Omset Merchandise</small>
                <div class="fw-bold text-primary fs-5">
                    Rp {{ number_format($totalGlobalSales ?? 0, 0, ',', '.') }}
                </div>
            </div>
        </div>

        @if(($totalGlobalNegative ?? 0) > 0)
        <hr>
        <div class="text-danger fw-bold">
            <i class="fas fa-exclamation-triangle"></i>
            Total Kewajiban Refund / Minus Merch : Rp {{ number_format($totalGlobalNegative, 0, ',', '.') }}
        </div>
        @endif
    </div>
</div>

{{-- REGULATION INFO BOX --}}
<div class="alert alert-primary border-0 shadow-sm mb-4">
    <h6 class="fw-bold">Sistem Plafon & Regulasi Keuangan Merchandise</h6>
    <ol class="small mb-0">
        <li>Event berjalan hanya dapat menarik maksimal batas plafon omset merchandise terhitung.</li>
        <li>H-10 menggunakan status bypass sesuai kebijakan sistem pengelolaan merchandise.</li>
        <li>Event selesai dapat menarik seluruh sisa dana merchandise.</li>
        <li>Jika memiliki saldo minus/potongan, pengajuan pencairan dibekukan sementara.</li>
    </ol>
</div>

<div class="mb-3">
    <h5 class="fw-bold">Detail Keuangan Tiap Event (Merchandise)</h5>
</div>

@forelse($wallets as $wallet)
<div class="card event-card mb-4">
    <div class="bg-light p-3 d-flex justify-content-between align-items-center">
        <strong>{{ $wallet['event_name'] }}</strong>
        
        @if(!empty($wallet['is_event_finished']))
            <span class="badge badge-finished">Selesai (100%)</span>
        @elseif(!empty($wallet['is_h_minus_10']))
            <span class="badge badge-h10">Plafon (H-10 Bypass)</span>
        @else
            <span class="badge badge-normal">Plafon 50%</span>
        @endif
    </div>

    <div class="card-body">
        @if($wallet['negative_balance'] > 0)
            <div class="withdraw-box p-3 bg-danger bg-opacity-10 mb-3">
                <div class="d-flex justify-content-between">
                    <span class="fw-semibold text-danger">Status Penarikan</span>
                    <span class="fw-bold text-danger">DIBEKUKAN</span>
                </div>
            </div>
        @else
            <div class="withdraw-box p-3 bg-success bg-opacity-10 mb-3">
                <div class="d-flex justify-content-between">
                    <span class="fw-semibold text-success">Maksimal Hak Tarik Merch Saat Ini</span>
                    <span class="fw-bold text-success">Rp {{ number_format($wallet['available_balance'], 0, ',', '.') }}</span>
                </div>
            </div>
        @endif

        <div class="fin-row d-flex justify-content-between">
            <span>1. Total Omset Penjualan Merchandise</span>
            <strong>Rp {{ number_format($wallet['total_sales'], 0, ',', '.') }}</strong>
        </div>

        <div class="fin-row d-flex justify-content-between">
            <span>2. Dana Merch Sudah Dicairkan / Diproses (-)</span>
            <strong class="text-primary">Rp {{ number_format($wallet['already_withdrawn'], 0, ',', '.') }}</strong>
        </div>

        <div class="fin-row d-flex justify-content-between">
            <span>3. Dana Merch Tertahan Batas Plafon (=)</span>
            <strong class="text-warning">Rp {{ number_format($wallet['held_balance'], 0, ',', '.') }}</strong>
        </div>

        @if($wallet['negative_balance'] > 0)
        <div class="fin-row d-flex justify-content-between">
            <span>4. Potongan / Minus Dana Merch (-)</span>
            <strong class="text-danger">Rp {{ number_format($wallet['negative_balance'], 0, ',', '.') }}</strong>
        </div>
        @endif

        <hr>

        <div class="mt-2 mb-2">
            <button class="btn btn-outline-dark w-100" onclick="showProducts({{ $wallet['event_id'] }})">
                <i class="fas fa-eye me-1"></i> Lihat Produk Terjual
            </button>
        </div>

        <div class="row g-2">
            <div class="col-12">
                @if(!$wallet['withdraw_locked'] && $wallet['available_balance'] > 0 && $wallet['can_withdraw'] && $wallet['negative_balance'] <= 0)
                    <a href="{{ route('eo.merch-withdrawal.create', $wallet['event_id']) }}" class="btn btn-warning w-100 fw-bold">
                        Ajukan Pencairan Dana Merch
                    </a>
                @else
                    <button class="btn btn-secondary w-100" disabled>
                        @if($wallet['withdraw_locked']) Penarikan Dikunci Admin
                        @elseif($wallet['negative_balance'] > 0) Selesaikan Minus Event
                        @else Limit Tarik Habis @endif
                    </button>
                    @if(!empty($wallet['system_reason']))
                    <div class="text-center text-danger small mt-2">
                        {{ $wallet['system_reason'] }}
                    </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@empty
<div class="alert alert-light text-center">
    Belum ada data saldo merchandise event.
</div>
@endforelse
</div>

{{-- MODAL BOOTSTRAP DETAIL PRODUK TERJUAL --}}
<div class="modal fade" id="produkModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="ticketDetailsModalLabel">Rincian Penjualan Merchandise</h5>
                    <small class="text-muted">Data produk merchandise berbayar</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div id="produkBody">
                    <div class="text-center py-3">
                        <span class="spinner-border spinner-border-sm text-secondary me-2"></span> Loading...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function showProducts(eventId) {
    let body = document.getElementById('produkBody');
    body.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm text-secondary me-2"></span> Loading...</div>';

    let modalElement = document.getElementById('produkModal');
    let modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    modalInstance.show();

    try {
        let res = await fetch(`/eo/merch-withdraw/products/${eventId}`);
        let json = await res.json();

        if (!json.success) {
            body.innerHTML = `
            <div class="text-danger text-center py-2">
                <i class="fas fa-exclamation-circle fs-4 mb-2"></i><br>
                <strong>Gagal memuat data keuangan produk</strong><br>
                <small class="text-muted d-block mt-1">${json.message || 'Terjadi kesalahan sistem'}</small>
            </div>`;
            return;
        }

        if (json.data.length === 0) {
            body.innerHTML = '<div class="text-muted text-center py-4">Belum ada produk merchandise yang terjual untuk event ini.</div>';
            return;
        }

        body.innerHTML = '';
        json.data.forEach(p => {
            body.innerHTML += `
            <div class="border-bottom py-2 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold">${p.nama_produk}</div>
                    <small class="text-muted">Harga Satuan: Rp ${Number(p.harga).toLocaleString('id-ID')}</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-secondary text-white mb-1">Terjual: ${p.total_terjual} pcs</span>
                    <div class="fw-bold text-success">Rp ${Number(p.total_omset).toLocaleString('id-ID')}</div>
                </div>
            </div>
            `;
        });

    } catch (e) {
        console.error(e);
        body.innerHTML = '<div class="text-danger text-center py-2"><i class="fas fa-wifi text-danger mb-2"></i><br>Gagal terhubung ke web server.</div>';
    }
}
</script>

@endsection