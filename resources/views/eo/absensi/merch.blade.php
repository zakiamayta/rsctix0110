@extends('layouts.eo')

@push('styles')
<link href="{{ asset('css/admin_dashboard.css') }}" rel="stylesheet">
@endpush

@section('title', 'Pantauan Penukaran Merchandise')

@section('content')

<style>
  @import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500&display=swap');

  :root {
    --rsc-bg: #F7F4F1;
    --rsc-surface: #FFFFFF;
    --rsc-surface2: #F2EEE9;
    --rsc-border: #E2DBD4;
    --rsc-accent: #f97316;
    --rsc-accent-dim: rgba(232,71,10,0.08);
    --rsc-text: #1A1208;
    --rsc-muted: #8A7E76;
    --radius: 14px;
  }

  .rsc-wrap * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }

  .rsc-wrap {
    background: var(--rsc-bg);
    min-height: 100vh;
    padding: 28px 24px 60px;
    color: var(--rsc-text);
  }

  /* ── Page header ── */
  .page-header { margin-bottom: 28px; }
  .page-header h2 {
    font-family: 'Sora', sans-serif;
    font-size: 1.6rem; font-weight: 800;
    color: var(--rsc-text); letter-spacing: -.5px; margin: 0 0 4px;
  }
  .page-header p { color: var(--rsc-muted); font-size: .82rem; margin: 0; }
  .accent-dot {
    display: inline-block; width: 8px; height: 8px;
    border-radius: 50%; background: var(--rsc-accent);
    margin-right: 8px; vertical-align: middle;
  }

  /* ── Section title ── */
  .section-title {
    font-family: 'Sora', sans-serif;
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.5px;
    color: var(--rsc-accent);
    margin: 0 0 18px;
    display: flex; align-items: center; gap: 8px;
  }
  .section-title::after {
    content: ''; flex: 1; height: 1px;
    background: linear-gradient(to right, var(--rsc-border), transparent);
  }

  /* ── Cards ── */
  .rsc-card {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    padding: 22px 24px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
  }

  /* ── Stats row ── */
  .stats-row-2 { display: grid; grid-template-columns: repeat(2,1fr); gap: 14px; margin-bottom: 20px; }
  .stat-card {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    padding: 20px 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
  }
  .stat-label {
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.2px;
    color: var(--rsc-muted); margin-bottom: 6px;
    display: flex; align-items: center; gap: 6px;
  }
  .stat-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
  .stat-value {
    font-family: 'Sora', sans-serif;
    font-size: 1.75rem; font-weight: 800;
    color: var(--rsc-text); line-height: 1; margin-bottom: 3px;
  }
  .stat-sub { font-size: .72rem; color: var(--rsc-muted); }
  .stat-icon {
    width: 52px; height: 52px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .stat-icon-green  { background: rgba(26,122,68,.1); }
  .stat-icon-amber  { background: rgba(180,83,9,.1); }

  /* ── Filter form ── */
  .filter-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
  }
  .filter-grid .span-full { grid-column: 1 / -1; }
  .filter-grid .span-2 { grid-column: span 2; }

  .field-group { display: flex; flex-direction: column; gap: 5px; }
  .field-group label {
    font-size: .72rem; font-weight: 700;
    color: var(--rsc-muted); text-transform: uppercase; letter-spacing: .6px;
  }
  .rsc-input {
    background: var(--rsc-surface2);
    border: 1px solid var(--rsc-border);
    border-radius: 9px;
    color: var(--rsc-text);
    padding: 9px 12px;
    font-size: .84rem;
    width: 100%;
    transition: border-color .18s, box-shadow .18s;
    outline: none;
    font-family: 'DM Sans', sans-serif;
  }
  .rsc-input::placeholder { color: #BEB5AD; }
  .rsc-input:focus {
    border-color: var(--rsc-accent);
    box-shadow: 0 0 0 3px var(--rsc-accent-dim);
  }
  select.rsc-input { cursor: pointer; }

  /* ── Buttons ── */
  .btn-primary {
    background: var(--rsc-accent); color: #fff;
    border: none; border-radius: 9px;
    padding: 9px 20px; font-size: .82rem; font-weight: 700;
    cursor: pointer; font-family: 'Sora', sans-serif;
    transition: opacity .15s; letter-spacing: .2px;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-primary:hover { opacity: .85; }

  .btn-ghost {
    background: var(--rsc-surface2); color: var(--rsc-muted);
    border: 1px solid var(--rsc-border); border-radius: 9px;
    padding: 9px 20px; font-size: .82rem; font-weight: 700;
    cursor: pointer; font-family: 'Sora', sans-serif;
    transition: border-color .15s, color .15s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-ghost:hover { border-color: #B5AEA8; color: var(--rsc-text); }

  /* ── Table ── */
  .table-wrap {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
    overflow: hidden;
  }
  .rsc-table { width: 100%; border-collapse: collapse; min-width: 900px; }
  .rsc-table thead tr {
    background: var(--rsc-surface2);
    border-bottom: 1px solid var(--rsc-border);
  }
  .rsc-table th {
    padding: 11px 14px; text-align: left;
    font-size: .65rem; font-weight: 700;
    color: var(--rsc-muted); text-transform: uppercase; letter-spacing: 1px;
    white-space: nowrap;
  }
  .rsc-table tbody tr {
    border-bottom: 1px solid var(--rsc-border);
    transition: background .12s;
  }
  .rsc-table tbody tr:last-child { border-bottom: none; }
  .rsc-table tbody tr:hover { background: #FAFAF8; }
  .rsc-table td {
    padding: 11px 14px; font-size: .82rem;
    color: var(--rsc-text); vertical-align: middle;
  }
  .td-bold { font-weight: 700; }
  .td-muted { color: var(--rsc-muted); }
  .td-mono { font-size: .78rem; color: var(--rsc-accent); font-family: monospace; font-weight: 700; }

  .item-list { list-style: none; margin: 0; padding: 8px 10px; background: var(--rsc-surface2); border: 1px dashed var(--rsc-border); border-radius: 9px; font-size: .78rem; }
  .item-list li { margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
  .item-list li:last-child { margin-bottom: 0; }
  .item-badge {
    background: var(--rsc-text); color: #fff; font-size: .65rem; font-weight: 700;
    padding: 1px 7px; border-radius: 20px; margin-left: 4px;
  }

  /* ── Status badges ── */
  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
    padding: 4px 10px; border-radius: 20px;
  }
  .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
  .badge-paid    { background: #E8F5EE; color: #1A7A44; }
  .badge-pending { background: #FEF3E2; color: #B45309; }

  /* ── Table action buttons ── */
  .btn-sm {
    font-size: .72rem; font-weight: 700;
    width: 33px; height: 33px; border-radius: 50%;
    border: none; cursor: pointer;
    font-family: 'Sora', sans-serif;
    transition: opacity .15s, background .15s;
    display: inline-flex; align-items: center; justify-content: center;
  }
  .btn-sm-indigo { background: #EEF2FF; color: #4338CA; }
  .btn-sm-indigo:hover { background: #E0E7FF; }
  .btn-sm-green  { background: #E8F5EE; color: #1A7A44; }
  .btn-sm-green:hover { background: #D6F0DF; }
  .btn-sm-red    { background: #FEF2F2; color: #B91C1C; }
  .btn-sm-red:hover { background: #FEE2E2; }

  /* ── Empty state ── */
  .empty-cell { padding: 48px 24px; text-align: center; color: var(--rsc-muted); }
  .empty-cell svg { opacity: .18; margin: 0 auto 10px; display: block; }
  .empty-cell p { font-size: .88rem; font-weight: 600; margin: 0; }

  /* ── Pagination footer ── */
  .table-footer {
    background: var(--rsc-surface);
    border-top: 1px solid var(--rsc-border);
    padding: 14px 20px;
  }
  .table-footer nav { font-size: .8rem; }

  /* ── Modal ── */
  .rsc-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(26,18,8,0.45);
    backdrop-filter: blur(4px);
    display: none; align-items: center; justify-content: center;
    z-index: 50; padding: 24px;
  }
  .rsc-modal-backdrop.open { display: flex; }
  .rsc-modal {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    box-shadow: 0 24px 60px rgba(0,0,0,0.14);
    padding: 28px;
    width: 100%; max-width: 520px;
    max-height: 80vh; overflow-y: auto;
    animation: fadeSlide .22s ease;
  }
  @keyframes fadeSlide {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .modal-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 20px;
  }
  .modal-title {
    font-family: 'Sora', sans-serif;
    font-size: 1.05rem; font-weight: 800; color: var(--rsc-text);
    margin: 0;
  }
  .btn-close-modal {
    background: var(--rsc-surface2); border: 1px solid var(--rsc-border);
    border-radius: 8px; width: 34px; height: 34px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 1rem; color: var(--rsc-muted);
    transition: background .15s, color .15s;
  }
  .btn-close-modal:hover { background: #EDE8E3; color: var(--rsc-text); }

  .modal-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
  .modal-table td {
    padding: 9px 12px; color: var(--rsc-text);
    border-bottom: 1px solid var(--rsc-border);
  }
  .modal-table tr:last-child td { border-bottom: none; }
  .modal-table td:first-child {
    width: 40%; font-weight: 700; color: var(--rsc-muted);
  }

  /* ── Responsive ── */
  @media (max-width: 900px) {
    .stats-row-2 { grid-template-columns: 1fr; }
    .filter-grid { grid-template-columns: 1fr 1fr; }
    .filter-grid .span-full, .filter-grid .span-2 { grid-column: 1 / -1; }
  }
  @media (max-width: 560px) {
    .filter-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="rsc-wrap">

  {{-- Header --}}
  <div class="page-header">
    <h2><span class="accent-dot"></span>Pantauan Penukaran Merchandise</h2>
    <p>Monitoring klaim pengambilan merchandise pada event Anda</p>
  </div>

  @php
    $totalSudahMerch = $merchTransactions->filter(fn($tx) => $tx->is_absen)->count();
    $totalBelumMerch = $merchTransactions->filter(fn($tx) => !$tx->is_absen)->count();
  @endphp

  {{-- Stats --}}
  <div class="stats-row-2">

    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#1A7A44;"></span>
          Sudah Serah Terima
        </div>
        <div class="stat-value">{{ $totalSudahMerch }}</div>
        <div class="stat-sub">Transaksi telah diambil</div>
      </div>
      <div class="stat-icon stat-icon-green">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="#1A7A44" stroke-width="2.2">
          <path d="M5 13l4 4L19 7"/>
        </svg>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#B45309;"></span>
          Belum Diambil
        </div>
        <div class="stat-value">{{ $totalBelumMerch }}</div>
        <div class="stat-sub">Menunggu pengambilan</div>
      </div>
      <div class="stat-icon stat-icon-amber">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="#B45309" stroke-width="2.2">
          <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>

  </div>

  {{-- Filter --}}
  <div class="rsc-card" style="margin-bottom:18px;">
    <div class="section-title">Filter Transaksi</div>

    <form method="GET" action="{{ route('eo.absensi.merch') }}" class="filter-grid">

      <div class="field-group span-2">
        <label>Pilih Event</label>
        <select name="event_id" class="rsc-input">
          <option value="">Semua Event Anda</option>
          @foreach ($events as $event)
            <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
              {{ $event->title }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="field-group span-2">
        <label>Pencarian</label>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Cari Email atau Kode Unik" class="rsc-input">
      </div>

      <div class="field-group span-2">
        <label>Status Penukaran</label>
        <select name="status" class="rsc-input">
          <option value="">Semua Status</option>
          <option value="sudah" {{ request('status') === 'sudah' ? 'selected' : '' }}>Sudah Ditukarkan</option>
          <option value="belum" {{ request('status') === 'belum' ? 'selected' : '' }}>Belum Diambil</option>
        </select>
      </div>

      <div class="span-full" style="display:flex; gap:10px; padding-top:4px;">
        <button type="submit" class="btn-primary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2.5">
            <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
          </svg>
          Terapkan Filter
        </button>
        <a href="{{ route('eo.absensi.merch') }}" class="btn-ghost">Reset</a>
      </div>

    </form>
  </div>

  {{-- Table --}}
  <div class="table-wrap">
    <div style="overflow-x:auto;">
      <table class="rsc-table">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Event</th>
            <th>Kode Unik &amp; Kontak</th>
            <th>Item Merchandise Terbeli</th>
            <th>Status Ambil</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($merchTransactions as $index => $tx)
            <tr>
              <td class="td-muted">
                {{ ($merchTransactions->currentPage() - 1) * $merchTransactions->perPage() + $index + 1 }}
              </td>
              <td class="td-bold">{{ $tx->event->title ?? '-' }}</td>
              <td>
                <div class="td-mono">{{ $tx->kode_unik }}</div>
                <div class="td-muted" style="font-size:.75rem;">{{ $tx->email }}</div>
              </td>
              <td>
                <ul class="item-list">
                  @foreach ($tx->details as $detail)
                    <li>
                      {{ $detail->product->name ?? 'Produk Merch' }}
                      <span class="item-badge">x{{ $detail->quantity }}</span>
                    </li>
                  @endforeach
                </ul>
              </td>
              <td>
                @if ($tx->is_absen)
                  <span class="badge badge-paid">
                    <span class="badge-dot"></span> Sudah Serah Terima
                  </span>
                @else
                  <span class="badge badge-pending">
                    <span class="badge-dot"></span> Belum Diambil
                  </span>
                @endif
              </td>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <button onclick="showMerchDetail({{ $tx->id }})" class="btn-sm btn-sm-indigo" title="Detail Transaksi">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </button>

                  @if (!$tx->is_absen)
                    <form method="POST" action="{{ route('eo.absensi.merch.manual', ['id' => $tx->id]) }}" class="m-0">
                      @csrf
                      <button type="submit" class="btn-sm btn-sm-green" title="Tandai Sudah Diambil">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                      </button>
                    </form>
                  @else
                    <form method="POST" action="{{ route('eo.absensi.merch.batal', ['id' => $tx->id]) }}" class="m-0">
                      @csrf
                      <button type="submit" class="btn-sm btn-sm-red" title="Batalkan Pengambilan">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="empty-cell">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
                     stroke="#f97316" stroke-width="1">
                  <rect x="3" y="4" width="18" height="18" rx="2"/>
                  <line x1="16" y1="2" x2="16" y2="6"/>
                  <line x1="8" y1="2" x2="8" y2="6"/>
                  <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <p>Belum ada data klaim penukaran merchandise pada kriteria ini.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($merchTransactions->hasPages())
      <div class="table-footer">
        {{ $merchTransactions->withQueryString()->links() }}
      </div>
    @endif
  </div>

</div>{{-- /rsc-wrap --}}

{{-- Modal Detail Transaksi Merch --}}
<div id="merchDetailModal" class="rsc-modal-backdrop">
  <div class="rsc-modal">
    <div class="modal-header">
      <h3 class="modal-title">Detail Pembelian Merchandise</h3>
      <button onclick="closeMerchModal()" class="btn-close-modal">✕</button>
    </div>
    <div id="modalMerchContent"></div>
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
      modalContent.innerHTML = '<p style="color:#8A7E76; font-size:.84rem;">Data transaksi tidak ditemukan.</p>';
    } else {
      modalContent.innerHTML = `
        <table class="modal-table">
          <tr><td>Kode Unik</td><td style="font-family:monospace; font-weight:700; color:var(--rsc-accent);">${escapeHtml(tx.kode_unik)}</td></tr>
          <tr><td>Email Pembeli</td><td>${escapeHtml(tx.email)}</td></tr>
          <tr><td>Status Pembayaran</td><td><span class="badge badge-paid"><span class="badge-dot"></span>${escapeHtml(tx.payment_status || 'PAID')}</span></td></tr>
          <tr><td>Status Klaim/Ambil</td><td>${tx.is_absen
            ? '<span class="badge badge-paid"><span class="badge-dot"></span>SUDAH DIAMBIL</span>'
            : '<span class="badge badge-pending"><span class="badge-dot"></span>BELUM DIAMBIL</span>'}</td></tr>
          <tr><td>Waktu Ambil</td><td>${tx.updated_at && tx.is_absen ? new Date(tx.updated_at).toLocaleString('id-ID') : '-'}</td></tr>
        </table>
      `;
    }

    document.getElementById('merchDetailModal').classList.add('open');
  }

  function closeMerchModal() {
    document.getElementById('merchDetailModal').classList.remove('open');
  }

  document.getElementById('merchDetailModal').addEventListener('click', function (e) {
    if (e.target === this) closeMerchModal();
  });
</script>

@endsection