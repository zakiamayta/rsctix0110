@extends('layouts.eo')

@section('title', 'Dashboard Saldo Tiket')

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
            Dashboard Saldo Tiket
        </h3>
        <small class="text-muted">
            Monitoring pencairan dana tiket event
        </small>
    </div>

    <a href="{{ route('eo.ticket-history.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-history"></i>
        Lihat Riwayat Withdraw
    </a>
</div>

{{-- CARD ORANGE --}}
<div class="card wallet-card mb-4">
    <div class="wallet-header p-4">
        <div class="text-uppercase small fw-bold opacity-75">
            Total Saldo Yang Dapat Ditarik
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
                <small class="text-muted">Total Saldo Ditahan</small>
                <div class="fw-bold text-warning fs-5">
                    Rp {{ number_format($totalGlobalHeld, 0, ',', '.') }}
                </div>
            </div>
            <div class="col-md-6">
                <small class="text-muted">Akumulasi Omset Tiket</small>
                <div class="fw-bold text-primary fs-5">
                    Rp {{ number_format($totalGlobalSales, 0, ',', '.') }}
                </div>
            </div>
        </div>

        @if($totalGlobalNegative > 0)
        <hr>
        <div class="text-danger fw-bold">
            <i class="fas fa-exclamation-triangle"></i>
            Total Kewajiban Refund : Rp {{ number_format($totalGlobalNegative, 0, ',', '.') }}
        </div>
        @endif
    </div>
</div>

{{-- INFO BOX --}}
<div class="alert alert-primary border-0 shadow-sm mb-4">
    <h6 class="fw-bold">Sistem Plafon & Regulasi Keuangan</h6>
    <ol class="small mb-0">
        <li>Event berjalan hanya dapat menarik maksimal 50% omset tiket.</li>
        <li>H-10 menggunakan status bypass sesuai kebijakan sistem.</li>
        <li>Event selesai dapat menarik seluruh sisa dana.</li>
        <li>If memiliki saldo refund (minus), pencairan dibekukan.</li>
    </ol>
</div>

<div class="mb-3">
    <h5 class="fw-bold">Detail Keuangan Tiap Event</h5>
</div>

@forelse($wallets as $wallet)
<div class="card event-card mb-4">
    <div class="bg-light p-3 d-flex justify-content-between align-items-center">
        <strong>{{ $wallet['event_name'] }}</strong>
        
        @if($wallet['is_event_finished'])
            <span class="badge badge-finished">Selesai (100%)</span>
        @elseif($wallet['is_h_minus_10'])
            <span class="badge badge-h10">Plafon 50% (H-10 Bypass)</span>
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
                    <span class="fw-semibold text-success">Maksimal Hak Tarik Saat Ini</span>
                    <span class="fw-bold text-success">Rp {{ number_format($wallet['available_balance'], 0, ',', '.') }}</span>
                </div>
            </div>
        @endif

        <div class="fin-row d-flex justify-content-between align-items-center">
            <span>1. Total Omset Penjualan Tiket</span>
            <div>
                <strong class="me-2">Rp {{ number_format($wallet['total_sales'], 0, ',', '.') }}</strong>
            </div>
        </div>

        <div class="fin-row d-flex justify-content-between">
            <span>2. Dana Yang Sudah Dicairkan / Diproses (-)</span>
            <strong class="text-primary">Rp {{ number_format($wallet['already_withdrawn'], 0, ',', '.') }}</strong>
        </div>

        <div class="fin-row d-flex justify-content-between">
            <span>3. Dana Tertahan Batas Plafon (=)</span>
            <strong class="text-warning">Rp {{ number_format($wallet['held_balance'], 0, ',', '.') }}</strong>
        </div>

        @if($wallet['negative_balance'] > 0)
        <div class="fin-row d-flex justify-content-between">
            <span>4. Potongan Refund / Minus (-)</span>
            <strong class="text-danger">Rp {{ number_format($wallet['negative_balance'], 0, ',', '.') }}</strong>
        </div>
        @endif

        <hr>
        
        <div class="mt-2 mb-2">
            <button class="btn btn-outline-dark w-100" onclick="showProducts({{ $wallet['event_id'] }}, '{{ addslashes($wallet['event_name']) }}')">
                <i class="fas fa-eye me-1"></i> Rincian Tiket Terjual
            </button>
        </div>

        <div class="row g-2">
            <div class="col-12">
                @if(!$wallet['withdraw_locked'] && $wallet['available_balance'] > 0 && $wallet['can_withdraw'] && $wallet['negative_balance'] <= 0)
                    <a href="{{ route('eo.ticket-withdraw.create', ['eventId' => $wallet['event_id']]) }}" class="btn btn-warning w-100 fw-bold">
                        Ajukan Pencairan Dana
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
    Belum ada data saldo event.
</div>
@endforelse
</div>

<div class="modal fade" id="ticketDetailsModal" tabindex="-1" aria-labelledby="ticketDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="ticketDetailsModalLabel">Rincian Penjualan Tiket</h5>
                    <small class="text-muted" id="modalEventName"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="ticketsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Tiket</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-center">Jumlah Terjual</th>
                                <th class="text-end">Subtotal Omset</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                </div>
                <div id="modalLoading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted small mt-2 mb-0">Sedang memproses data finansial...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showProducts(eventId, eventName) {
    // Inisialisasi modal objek Bootstrap
    const modalElement = document.getElementById('ticketDetailsModal');
    const modal = new bootstrap.Modal(modalElement);
    
    const tableBody = document.querySelector('#ticketsTable tbody');
    const loadingInfo = document.getElementById('modalLoading');
    const tableContainer = document.getElementById('ticketsTable');
    const modalEventName = document.getElementById('modalEventName');

    // Setup Tampilan Awal
    if (modalEventName) modalEventName.textContent = eventName;
    tableBody.innerHTML = '';
    tableContainer.style.display = 'none';
    loadingInfo.style.display = 'block';
    
    // Tampilkan modal
    modal.show();

    // Jalankan AJAX data tiket
    fetch(`/eo/ticket-withdraw/tickets/${eventId}`)
        .then(response => {
            if (!response.ok) throw new Error('Gagal terhubung ke web server.');
            return response.json();
        })
        .then(result => {
            loadingInfo.style.display = 'none';
            if (result.success && result.data.length > 0) {
                result.data.forEach(item => {
                    const row = `
                        <tr>
                            <td><span class="fw-semibold text-dark">${item.nama_tiket}</span></td>
                            <td class="text-end">Rp ${parseInt(item.harga_satuan).toLocaleString('id-ID')}</td>
                            <td class="text-center"><span class="badge bg-secondary text-white px-2 py-1">${item.total_terjual} Lembar</span></td>
                            <td class="text-end fw-bold text-success">Rp ${parseFloat(item.total_omset).toLocaleString('id-ID')}</td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
                tableContainer.style.display = 'table';
            } else {
                tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Belum ada rincian tiket yang laku untuk event ini.</td></tr>';
                tableContainer.style.display = 'table';
            }
        })
        .catch(error => {
            loadingInfo.style.display = 'none';
            tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4"><i class="fas fa-exclamation-circle"></i> ${error.message}</td></tr>`;
            tableContainer.style.display = 'table';
        });
}
</script>

@endsection
