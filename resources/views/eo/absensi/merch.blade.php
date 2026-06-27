@extends('layouts.eo')

@section('title', 'Pantauan Penukaran Merchandise')

@section('content')
<div class="container-fluid py-4">
    <h2 class="h4 fw-bold text-dark mb-4">Pantauan Penukaran Merchandise</h2>

    {{-- 🔹 Ringkasan Penukaran Merchandise --}}
    @php
        $totalSudahMerch = $merchTransactions->filter(fn($tx) => $tx->is_absen)->count();
        $totalBelumMerch = $merchTransactions->filter(fn($tx) => !$tx->is_absen)->count();
    @endphp
    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card border-0 bg-success bg-opacity-10 p-3 rounded-4 shadow-sm h-100 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <p class="small text-success fw-bold text-uppercase mb-1">Sudah Serah Terima</p>
                    <h3 class="fw-bold text-success mb-0">{{ $totalSudahMerch }} Transaksi</h3>
                </div>
                <div class="bg-success text-white p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card border-0 bg-warning bg-opacity-10 p-3 rounded-4 shadow-sm h-100 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <p class="small text-warning fw-bold text-uppercase mb-1">Belum Diambil</p>
                    <h3 class="fw-bold text-warning mb-0">{{ $totalBelumMerch }} Transaksi</h3>
                </div>
                <div class="bg-warning text-white p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- 🔹 Form Filter & Sort --}}
    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
        <form method="GET" action="{{ route('eo.absensi.merch') }}" class="row g-3 align-items-end">
            {{-- Pilih Event --}}
            <div class="col-12 col-md-3">
                <label for="event_id" class="form-label small fw-medium text-secondary mb-1">Pilih Event</label>
                <select id="event_id" name="event_id" class="form-select form-select-sm">
                    <option value="">-- Semua Event Anda --</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                            {{ $event->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Pencarian --}}
            <div class="col-12 col-md-3">
                <label for="search" class="form-label small fw-medium text-secondary mb-1">Cari Transaksi Merch</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Cari Email atau Kode Unik" class="form-control form-control-sm">
            </div>

            {{-- Status --}}
            <div class="col-12 col-md-3">
                <label for="status" class="form-label small fw-medium text-secondary mb-1">Status Penukaran</label>
                <select id="status" name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="sudah" {{ request('status') === 'sudah' ? 'selected' : '' }}>Sudah Ditukarkan</option>
                    <option value="belum" {{ request('status') === 'belum' ? 'selected' : '' }}>Belum Diambil</option>
                </select>
            </div>

            {{-- Tombol Aksi --}}
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold w-100">
                    Filter
                </button>
                <a href="{{ route('eo.absensi.merch') }}" class="btn btn-light btn-sm px-4 fw-semibold text-secondary border w-100">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- 🔹 Tabel Transaksi Merchandise --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0 text-sm">
                <thead class="table-light text-secondary fw-semibold">
                    <tr>
                        <th class="px-4 py-3 text-center" style="width: 70px;">No</th>
                        <th class="px-4 py-3">Nama Event</th>
                        <th class="px-4 py-3">Kode Unik & Kontak</th>
                        <th class="px-4 py-3">Item Merchandise Terbeli</th>
                        <th class="px-4 py-3 text-center">Status Ambil</th>
                        <th class="px-4 py-3 text-center" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($merchTransactions as $index => $tx)
                        <tr class="border-bottom">
                            <td class="px-4 py-3 text-center fw-medium text-secondary">
                                {{ ($merchTransactions->currentPage() - 1) * $merchTransactions->perPage() + $index + 1 }}
                            </td>
                            <td class="px-4 py-3 text-dark fw-bold">{{ $tx->event->title ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="fw-bold font-mono text-primary mb-1">{{ $tx->kode_unik }}</div>
                                <small class="text-muted d-block" style="font-size: 0.8rem;">{{ $tx->email }}</small>
                            </td>
                            <td class="px-4 py-3">
                                <ul class="list-unstyled mb-0 bg-light p-2 rounded-3 border border-dashed text-dark" style="font-size: 0.85rem;">
                                    @foreach ($tx->details as $detail)
                                        <li class="mb-1">
                                            <i class="fas fa-box text-secondary me-1" style="font-size: 11px;"></i>
                                            {{ $detail->product->name ?? 'Produk Merch' }} 
                                            <span class="badge bg-secondary text-white ms-1">x{{ $detail->quantity }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($tx->is_absen)
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-semibold">
                                        <i class="fas fa-check-circle me-1"></i> Sudah Serah Terima
                                    </span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill fw-semibold">
                                        <i class="fas fa-clock me-1"></i> Belum Diambil
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    {{-- Detail Modal Transaksi Merch --}}
                                    <button onclick="showMerchDetail({{ $tx->id }})"
                                        class="btn btn-sm btn-info bg-info bg-opacity-10 text-info border-0 rounded-circle p-2 d-flex align-items-center justify-content-center"
                                        style="width: 35px; height: 35px;" title="Detail Transaksi">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>

                                    {{-- 🔹 Fitur Aksi Manual: Serah Terima / Batalkan --}}
                                    @if (!$tx->is_absen)
                                        <form method="POST" action="{{ route('eo.absensi.merch.manual', ['id' => $tx->id]) }}" class="m-0">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-sm btn-success bg-success bg-opacity-10 text-success border-0 rounded-circle p-2 d-flex align-items-center justify-content-center"
                                                style="width: 35px; height: 35px;" title="Tandai Sudah Diambil">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('eo.absensi.merch.batal', ['id' => $tx->id]) }}" class="m-0">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-sm btn-danger bg-danger bg-opacity-10 text-danger border-0 rounded-circle p-2 d-flex align-items-center justify-content-center"
                                                style="width: 35px; height: 35px;" title="Batalkan Pengambilan">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                Belum ada data klaim penukaran merchandise pada kriteria ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($merchTransactions->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4">
                {{ $merchTransactions->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

{{-- 🔹 Modal Detail Transaksi Merch --}}
<div class="modal fade" id="merchDetailModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="merchDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="merchDetailLabel">Detail Pembelian Merchandise</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div id="modalMerchContent" class="text-sm"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary w-100 fw-semibold py-2 rounded-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    const merchData = @json($merchTransactions->items());

    function escapeHtml(str) {
        if (!str) return '-';
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }

    function showMerchDetail(txId) {
        const tx = merchData.find(m => m.id === txId);
        const modalContent = document.getElementById('modalMerchContent');

        if (!tx) {
            modalContent.innerHTML = '<p class="text-muted text-center my-3">Data transaksi tidak ditemukan.</p>';
        } else {
            modalContent.innerHTML = `
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="fw-semibold text-secondary py-1" style="width: 35%;">Kode Unik:</td><td class="text-primary font-mono fw-bold py-1">${escapeHtml(tx.kode_unik)}</td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">Email Pembeli:</td><td class="text-dark py-1">${escapeHtml(tx.email)}</td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">Status Pembayaran:</td><td class="text-dark py-1"><span class="badge bg-light text-dark border">${escapeHtml(tx.payment_status || 'PAID')}</span></td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">Status Klaim/Ambil:</td><td class="text-dark py-1">${tx.is_absen ? '<span class="text-success fw-bold">SUDAH DIAMBIL</span>' : '<span class="text-warning fw-bold">BELUM DIAMBIL</span>'}</td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">Waktu Ambil:</td><td class="text-dark py-1">${tx.updated_at && tx.is_absen ? new Date(tx.updated_at).toLocaleString('id-ID') : '-'}</td></tr>
                    </table>
                </div>
            `;
        }

        const myModal = new bootstrap.Modal(document.getElementById('merchDetailModal'));
        myModal.show();
    }
</script>
@endsection