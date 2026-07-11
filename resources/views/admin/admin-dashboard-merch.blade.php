@extends('layouts.admin')

@section('title', 'Dashboard Merch')
@section('content')

<style>
  .rsc-wrap * { font-family: var(--font-main), 'Poppins', sans-serif; box-sizing: border-box; }
  .rsc-wrap { color: var(--rsc-dark); }

  /* ── Page header ── */
  .page-header { margin-bottom: 28px; }
  .page-header h2 {
    font-family: var(--font-main), 'Poppins', sans-serif;
    font-size: 1.6rem; font-weight: 800;
    color: var(--rsc-dark); letter-spacing: -.5px; margin: 0 0 4px;
  }
  .page-header p { color: var(--rsc-muted); font-size: .82rem; margin: 0; }
  .accent-dot {
    display: inline-block; width: 8px; height: 8px;
    border-radius: 50%; background: var(--rsc-orange);
    margin-right: 8px; vertical-align: middle;
  }

  /* ── Section title ── */
  .section-title {
    font-family: var(--font-main), 'Poppins', sans-serif;
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.5px;
    color: var(--rsc-orange);
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
    border-radius: 14px;
    padding: 22px 24px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
  }

  /* ── Stats row ── */
  .stats-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 20px; }
  .stat-card {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
  }
  .stat-card.accent { background: var(--rsc-orange); border-color: var(--rsc-orange); }
  .stat-label {
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.2px;
    color: var(--rsc-muted); margin-bottom: 6px;
    display: flex; align-items: center; gap: 6px;
  }
  .stat-card.accent .stat-label { color: rgba(255,255,255,.7); }
  .stat-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
  .stat-value {
    font-family: var(--font-main), 'Poppins', sans-serif;
    font-size: 1.75rem; font-weight: 800;
    color: var(--rsc-dark); line-height: 1; margin-bottom: 3px;
  }
  .stat-card.accent .stat-value { color: #fff; }
  .stat-sub { font-size: .72rem; color: var(--rsc-muted); }
  .stat-card.accent .stat-sub { color: rgba(255,255,255,.6); }
  .stat-icon {
    width: 52px; height: 52px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .stat-icon-green  { background: rgba(26,122,68,.1); }
  .stat-icon-red    { background: rgba(185,28,28,.08); }
  .stat-card.accent .stat-icon { background: rgba(255,255,255,.2); }

  /* ── Filter form ── */
  .filter-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; align-items: end; }
  .filter-grid .span-full { grid-column: 1 / -1; }

  .field-group { display: flex; flex-direction: column; gap: 5px; }
  .field-group label {
    font-size: .72rem; font-weight: 700;
    color: var(--rsc-muted); text-transform: uppercase; letter-spacing: .6px;
  }
  .rsc-input {
    background: var(--rsc-bg);
    border: 1px solid var(--rsc-border);
    border-radius: 9px;
    color: var(--rsc-dark);
    padding: 9px 12px;
    font-size: .84rem;
    width: 100%;
    transition: border-color .18s, box-shadow .18s;
    outline: none;
    font-family: var(--font-main), 'Poppins', sans-serif;
  }
  .rsc-input::placeholder { color: #BEB5AD; }
  .rsc-input:focus {
    border-color: var(--rsc-orange);
    box-shadow: 0 0 0 3px var(--rsc-orange-light);
  }
  select.rsc-input { cursor: pointer; }

  /* ── Buttons ── */
  .btn-primary {
    background: var(--rsc-orange); color: #fff;
    border: none; border-radius: 9px;
    padding: 9px 20px; font-size: .82rem; font-weight: 700;
    cursor: pointer; font-family: var(--font-main), 'Poppins', sans-serif;
    transition: opacity .15s; letter-spacing: .2px;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-primary:hover { opacity: .85; }

  .btn-ghost {
    background: var(--rsc-bg); color: var(--rsc-muted);
    border: 1px solid var(--rsc-border); border-radius: 9px;
    padding: 9px 20px; font-size: .82rem; font-weight: 700;
    cursor: pointer; font-family: var(--font-main), 'Poppins', sans-serif;
    transition: border-color .15s, color .15s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-ghost:hover { border-color: #B5AEA8; color: var(--rsc-dark); }

  .btn-green {
    background: #15803D; color: #fff;
    border: none; border-radius: 9px;
    padding: 9px 18px; font-size: .82rem; font-weight: 700;
    cursor: pointer; font-family: var(--font-main), 'Poppins', sans-serif;
    transition: opacity .15s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-green:hover { opacity: .87; }

  .btn-red-outline {
    background: #fff; color: #B91C1C;
    border: 1px solid #FECACA; border-radius: 9px;
    padding: 9px 18px; font-size: .82rem; font-weight: 700;
    cursor: pointer; font-family: var(--font-main), 'Poppins', sans-serif;
    transition: background .15s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-red-outline:hover { background: #FEF2F2; }

  .action-row { display: flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; align-items: center; }

  /* ── Table ── */
  .table-wrap {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: 14px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
    overflow: hidden;
  }
  .rsc-table { width: 100%; border-collapse: collapse; min-width: 980px; }
  .rsc-table thead tr { background: var(--rsc-bg); border-bottom: 1px solid var(--rsc-border); }
  .rsc-table th {
    padding: 11px 14px; text-align: left;
    font-size: .65rem; font-weight: 700;
    color: var(--rsc-muted); text-transform: uppercase; letter-spacing: 1px;
    white-space: nowrap;
  }
  .rsc-table tbody tr { border-bottom: 1px solid var(--rsc-border); transition: background .12s; }
  .rsc-table tbody tr:last-child { border-bottom: none; }
  .rsc-table tbody tr:hover { background: #FAFAF8; }
  .rsc-table td { padding: 11px 14px; font-size: .82rem; color: var(--rsc-dark); vertical-align: middle; }
  .td-bold { font-weight: 700; }
  .td-muted { color: var(--rsc-muted); }
  .td-mono { font-size: .78rem; color: var(--rsc-muted); font-family: monospace; }

  /* ── Status badges ── */
  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
    padding: 4px 10px; border-radius: 20px;
  }
  .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
  .badge-paid    { background: #E8F5EE; color: #1A7A44; }
  .badge-unpaid  { background: #FEF2F2; color: #B91C1C; }
  .badge-qty {
    display: inline-flex; align-items: center;
    padding: 4px 10px; border-radius: 20px;
    background: var(--rsc-orange-light); color: var(--rsc-orange);
    font-size: .68rem; font-weight: 700;
  }

  /* ── Table action buttons ── */
  .btn-sm {
    font-size: .72rem; font-weight: 700;
    padding: 5px 12px; border-radius: 7px;
    border: none; cursor: pointer;
    font-family: var(--font-main), 'Poppins', sans-serif;
    transition: opacity .15s; text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px;
  }
  .btn-sm-orange { background: var(--rsc-orange-light); color: var(--rsc-orange); }
  .btn-sm-orange:hover { background: #ffe3d3; }
  .btn-sm-green  { background: #E8F5EE; color: #1A7A44; }
  .btn-sm-green:hover { background: #d6efe0; }
  .btn-sm-block { width: 100%; justify-content: center; }

  .row-actions { display: flex; flex-direction: column; gap: 6px; min-width: 130px; }

  /* ── Empty state ── */
  .empty-cell { padding: 48px 24px; text-align: center; color: var(--rsc-muted); font-size: .88rem; font-weight: 600; }

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
    border-radius: 14px;
    box-shadow: 0 24px 60px rgba(0,0,0,0.14);
    padding: 28px;
    width: 100%; max-width: 680px;
    max-height: 85vh; overflow-y: auto;
  }
  .modal-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 20px;
  }
  .modal-title {
    font-family: var(--font-main), 'Poppins', sans-serif;
    font-size: 1.05rem; font-weight: 800; color: var(--rsc-dark);
    margin: 0;
  }
  .btn-close-modal {
    background: var(--rsc-bg); border: 1px solid var(--rsc-border);
    border-radius: 8px; width: 34px; height: 34px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 1rem; color: var(--rsc-muted);
    transition: background .15s, color .15s;
  }
  .btn-close-modal:hover { background: #EDE8E3; color: var(--rsc-dark); }

  .modal-footer { display: flex; justify-content: flex-end; margin-top: 20px; }

  /* Detail item card di dalam modal */
  .detail-item-card {
    border: 1px solid var(--rsc-border);
    background: var(--rsc-bg);
    border-radius: 12px;
    padding: 16px;
    margin-top: 14px;
  }
  .detail-item-head { display: flex; align-items: center; gap: 14px; margin-bottom: 10px; }
  .detail-item-head img {
    width: 64px; height: 64px; object-fit: cover;
    border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    flex-shrink: 0;
  }
  .detail-item-head .name { font-weight: 700; color: var(--rsc-dark); font-size: 1rem; margin: 0 0 2px; }
  .detail-item-head .variant { font-size: .8rem; color: var(--rsc-muted); margin: 0; }
  .detail-item-body p { font-size: .82rem; color: var(--rsc-dark); margin: 4px 0; }
  .detail-item-body strong { color: var(--rsc-muted); font-weight: 600; }
  .detail-empty { color: var(--rsc-muted); font-size: .85rem; padding: 12px 0; }

  /* Modal Konfirmasi (regenerate QR) */
  .rsc-modal-confirm { max-width: 420px; text-align: center; }
  .confirm-icon {
    display: flex; align-items: center; justify-content: center;
    width: 56px; height: 56px; border-radius: 50%;
    background: var(--rsc-orange-light);
    margin: 0 auto 16px;
  }
  .confirm-title {
    font-family: var(--font-main), 'Poppins', sans-serif;
    font-size: 1.1rem; font-weight: 800; color: var(--rsc-dark);
    margin: 0 0 8px;
  }
  .confirm-text { color: var(--rsc-muted); font-size: .84rem; margin: 0 0 20px; line-height: 1.5; }
  .confirm-actions { display: flex; gap: 10px; }
  .confirm-actions .btn-ghost, .confirm-actions .btn-green { flex: 1; justify-content: center; }

  /* ── Responsive ── */
  @media (max-width: 900px) {
    .stats-row { grid-template-columns: 1fr 1fr; }
    .filter-grid { grid-template-columns: 1fr 1fr; }
    .filter-grid .span-full { grid-column: 1 / -1; }
  }
  @media (max-width: 560px) {
    .stats-row { grid-template-columns: 1fr; }
    .filter-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="rsc-wrap">

  {{-- Header --}}
  <div class="page-header">
    <h2><span class="accent-dot"></span>Dashboard Transaksi Merchandise</h2>
    <p>Monitoring transaksi merch, status pembayaran, dan detail pembelian</p>
  </div>

  {{-- Stats --}}
  <div class="stats-row">

    {{-- Total Uang Masuk --}}
    <div class="stat-card accent">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:rgba(255,255,255,.7);"></span>
          Total Uang Masuk
        </div>
        <div class="stat-value">Rp{{ number_format($totalPaidAmount, 0, ',', '.') }}</div>
        <div class="stat-sub">Dari pembayaran berhasil</div>
      </div>
      <div class="stat-icon" style="background:rgba(255,255,255,.2);">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2">
          <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.592 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.592-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>

    {{-- Total Paid --}}
    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#1A7A44;"></span>
          Total Paid
        </div>
        <div class="stat-value">{{ $totalPaidCount }}</div>
        <div class="stat-sub">Transaksi selesai</div>
      </div>
      <div class="stat-icon stat-icon-green">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1A7A44" stroke-width="2.2">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>

    {{-- Total Unpaid --}}
    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#B91C1C;"></span>
          Total Unpaid
        </div>
        <div class="stat-value">{{ $totalUnpaidCount }}</div>
        <div class="stat-sub">Menunggu pembayaran</div>
      </div>
      <div class="stat-icon stat-icon-red">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#B91C1C" stroke-width="2.2">
          <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>

  </div>

  {{-- Filter --}}
  <div class="rsc-card" style="margin-bottom:18px;">
    <div class="section-title">Filter & Pencarian</div>

    <form method="GET" action="{{ route('admin.merch.dashboard') }}" class="filter-grid">

      <div class="field-group">
        <label for="payment_status">Status Pembayaran</label>
        <select id="payment_status" name="payment_status" class="rsc-input">
          <option value="">-- Semua Status --</option>
          <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
          <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
        </select>
      </div>

      <div class="field-group">
        <label for="start_date">Tanggal Mulai</label>
        <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" class="rsc-input"/>
      </div>

      <div class="field-group">
        <label for="end_date">Tanggal Selesai</label>
        <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" class="rsc-input"/>
      </div>

      <div class="field-group">
        <label for="q">Pencarian</label>
        <input type="text" id="q" name="q" placeholder="Cari email/nama"
               value="{{ request('q') }}" class="rsc-input"/>
      </div>

      <div class="span-full" style="display:flex; gap:10px; padding-top:4px;">
        <button type="submit" class="btn-primary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
          </svg>
          Terapkan Filter
        </button>
        <a href="{{ route('admin.merch.dashboard') }}" class="btn-ghost">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Reset
        </a>
      </div>

    </form>
  </div>

  {{-- Action Buttons --}}
  <div class="action-row">
    <a href="{{ route('admin.merch.dashboard.export.excel', request()->query()) }}" class="btn-green">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
        <path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      Export Excel
    </a>
    <a href="{{ route('admin.merch.dashboard.export.pdf', request()->query()) }}" target="_blank" class="btn-red-outline">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
        <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
      </svg>
      Export PDF
    </a>
  </div>

  {{-- Table --}}
  <div class="table-wrap">
    <div style="overflow-x:auto;">
      <table class="rsc-table">
        <thead>
          <tr>
            <th>No.</th>
            <th>Judul Merchandise</th>
            <th>Email</th>
            <th>Waktu Checkout</th>
            <th>Waktu Pembayaran</th>
            <th>Status</th>
            <th>Total</th>
            <th>QR Code</th>
            <th>Jumlah Item</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($transactions as $transaction)
            <tr>
              <td class="td-muted">{{ $loop->iteration }}</td>
              <td class="td-bold">{{ $transaction->details->first()?->product?->name ?? '-' }}</td>
              <td class="td-mono">{{ $transaction->email }}</td>
              <td class="td-muted" style="font-size:.75rem; white-space:nowrap;">{{ $transaction->checkout_time }}</td>
              <td class="td-muted" style="font-size:.75rem; white-space:nowrap;">{{ $transaction->paid_time ?? '-' }}</td>
              <td>
                <span class="badge {{ $transaction->payment_status === 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
                  <span class="badge-dot"></span>
                  {{ ucfirst($transaction->payment_status) }}
                </span>
              </td>
              <td class="td-bold">Rp{{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
              <td>
                @if($transaction->qr_code)
                  <a href="{{ route('guests.merch.qr', $transaction->kode_unik) }}" target="_blank" class="btn-sm btn-sm-orange">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    Lihat QR
                  </a>
                @else
                  <span class="td-muted" style="font-size:.75rem;">-</span>
                @endif
              </td>
              <td><span class="badge-qty">{{ $transaction->details->sum('quantity') }} pcs</span></td>
              <td>
                <div class="row-actions">
                  <button type="button" onclick="showDetail({{ $transaction->id }})" class="btn-sm btn-sm-orange btn-sm-block">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Detail
                  </button>
                  <form id="regenerate-form-{{ $transaction->id }}"
                        action="{{ route('admin.transactions.regenerateQR', $transaction->id) }}"
                        method="POST" class="inline-block">
                    @csrf
                    <button type="button" onclick="openConfirmModal({{ $transaction->id }})" class="btn-sm btn-sm-green btn-sm-block">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                      Regenerate QR
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="empty-cell">Tidak ada transaksi merchandise</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>{{-- /rsc-wrap --}}

{{-- Modal Detail --}}
<div id="detailModal" class="rsc-modal-backdrop">
  <div class="rsc-modal" id="detailModalContent">
    <div class="modal-header">
      <h3 class="modal-title">Detail Transaksi Merchandise</h3>
      <button type="button" onclick="closeDetailModal()" class="btn-close-modal">✕</button>
    </div>
    <div id="detailContent"></div>
    <div class="modal-footer">
      <button type="button" onclick="closeDetailModal()" class="btn-ghost">Tutup</button>
    </div>
  </div>
</div>

{{-- Modal Konfirmasi Regenerate QR --}}
<div id="confirmModal" class="rsc-modal-backdrop">
  <div class="rsc-modal rsc-modal-confirm">
    <div class="confirm-icon">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="var(--rsc-orange)" stroke-width="2">
        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
    </div>
    <h2 class="confirm-title">Konfirmasi</h2>
    <p class="confirm-text">Yakin mau generate ulang QR untuk transaksi ini? QR code lama akan diganti.</p>
    <div class="confirm-actions">
      <button type="button" onclick="closeConfirmModal()" class="btn-ghost">Batal</button>
      <button type="button" id="confirmButton" class="btn-green">Ya, Generate</button>
    </div>
  </div>
</div>

<script>
    const transactions = @json($transactions);

    function showDetail(id) {
        const transaction = transactions.find(t => t.id === id);
        if (!transaction) return;

        let html = '';

        if (transaction.details.length === 0) {
            html += `<p class="detail-empty">Tidak ada detail</p>`;
        } else {
            transaction.details.forEach(d => {
                html += `
                    <div class="detail-item-card">
                        <div class="detail-item-head">
                            ${d.varian?.image ? `<img src="${d.varian.image}">` : ''}
                            <div>
                                <p class="name">${d.product?.name ?? '-'}</p>
                                <p class="variant">${d.varian?.varian ?? '-'} - ${d.ukuran?.ukuran ?? '-'}</p>
                            </div>
                        </div>
                        <div class="detail-item-body">
                            <p><strong>Pembeli:</strong> ${d.buyer_name} (${d.buyer_phone ?? '-'})</p>
                            <p><strong>Qty:</strong> ${d.quantity}</p>
                            <p><strong>Subtotal:</strong> Rp${new Intl.NumberFormat('id-ID').format(d.subtotal)}</p>
                        </div>
                    </div>
                `;
            });
        }

        document.getElementById('detailContent').innerHTML = html;
        document.getElementById('detailModal').classList.add('open');
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.remove('open');
    }

    // Close saat klik backdrop
    document.getElementById('detailModal').addEventListener('click', function (e) {
        if (e.target === this) closeDetailModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDetailModal();
    });

    /* ── Modal Konfirmasi Regenerate QR ──
       Catatan: fungsi ini tidak ada di file asli meskipun sudah dipanggil
       lewat onclick="openConfirmModal(...)" pada tombol Regenerate QR.
       Ditambahkan mengikuti pola identik dari halaman Transaksi Tiket
       (yang menuju route admin.transactions.regenerateQR yang sama)
       supaya tombol tetap berfungsi. Beri tahu saya jika ingin dikembalikan
       ke versi confirm() browser biasa. */
    let selectedFormId = null;
    const confirmModal = document.getElementById("confirmModal");

    function openConfirmModal(transactionId) {
        selectedFormId = "regenerate-form-" + transactionId;
        confirmModal.classList.add("open");
    }

    function closeConfirmModal() {
        confirmModal.classList.remove("open");
        selectedFormId = null;
    }

    document.getElementById("confirmButton").addEventListener("click", function () {
        if (selectedFormId) {
            document.getElementById(selectedFormId).submit();
        }
    });

    confirmModal.addEventListener("click", function (e) {
        if (e.target === confirmModal) closeConfirmModal();
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") closeConfirmModal();
    });
</script>

@endsection