@extends('layouts.eo')

@push('styles')
<link href="{{ asset('css/admin_dashboard.css') }}" rel="stylesheet">
@endpush

@section('content')

<style>
  @import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500&display=swap');

  :root {
    --rsc-bg: #F7F4F1;
    --rsc-surface: #FFFFFF;
    --rsc-surface2: #F2EEE9;
    --rsc-border: #E2DBD4;
    --rsc-accent: #E8470A;
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
  .stats-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 20px; }
  .stat-card {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    padding: 20px 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
  }
  .stat-card.accent {
    background: var(--rsc-accent);
    border-color: var(--rsc-accent);
  }
  .stat-label {
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.2px;
    color: var(--rsc-muted); margin-bottom: 6px;
    display: flex; align-items: center; gap: 6px;
  }
  .stat-card.accent .stat-label { color: rgba(255,255,255,.7); }
  .stat-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
  .stat-value {
    font-family: 'Sora', sans-serif;
    font-size: 1.75rem; font-weight: 800;
    color: var(--rsc-text); line-height: 1; margin-bottom: 3px;
  }
  .stat-card.accent .stat-value { color: #fff; }
  .stat-sub { font-size: .72rem; color: var(--rsc-muted); }
  .stat-card.accent .stat-sub { color: rgba(255,255,255,.6); }
  .stat-icon {
    width: 52px; height: 52px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .stat-icon-orange { background: var(--rsc-accent-dim); }
  .stat-icon-green  { background: rgba(26,122,68,.1); }
  .stat-icon-red    { background: rgba(185,28,28,.08); }
  .stat-card.accent .stat-icon { background: rgba(255,255,255,.2); }

  /* ── Filter form ── */
  .filter-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
  }
  .filter-grid .span-full { grid-column: 1 / -1; }

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

  .btn-green {
    background: #15803D; color: #fff;
    border: none; border-radius: 9px;
    padding: 9px 18px; font-size: .82rem; font-weight: 700;
    cursor: pointer; font-family: 'Sora', sans-serif;
    transition: opacity .15s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-green:hover { opacity: .87; }

  .btn-red-outline {
    background: #fff; color: #B91C1C;
    border: 1px solid #FECACA; border-radius: 9px;
    padding: 9px 18px; font-size: .82rem; font-weight: 700;
    cursor: pointer; font-family: 'Sora', sans-serif;
    transition: background .15s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-red-outline:hover { background: #FEF2F2; }

  .action-row { display: flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; align-items: center; }

  /* ── Table ── */
  .table-wrap {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
    overflow: hidden;
  }
  .rsc-table { width: 100%; border-collapse: collapse; min-width: 820px; }
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

  /* ── Table action buttons ── */
  .btn-sm {
    font-size: .72rem; font-weight: 700;
    padding: 5px 12px; border-radius: 7px;
    border: none; cursor: pointer;
    font-family: 'Sora', sans-serif;
    transition: opacity .15s; text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px;
  }
  .btn-sm-blue   { background: var(--rsc-accent-dim); color: var(--rsc-accent); }
  .btn-sm-blue:hover { background: rgba(232,71,10,.15); }
  .btn-sm-indigo { background: #EEF2FF; color: #4338CA; }
  .btn-sm-indigo:hover { background: #E0E7FF; }

  /* ── Empty state ── */
  .empty-cell { padding: 48px 24px; text-align: center; color: var(--rsc-muted); }
  .empty-cell svg { opacity: .18; margin: 0 auto 10px; display: block; }
  .empty-cell p { font-size: .88rem; font-weight: 600; margin: 0; }

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
    width: 100%; max-width: 680px;
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

  /* modal inner table */
  .modal-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
  .modal-table thead tr { background: var(--rsc-surface2); }
  .modal-table th {
    padding: 9px 12px; text-align: left;
    font-size: .65rem; font-weight: 700;
    color: var(--rsc-muted); text-transform: uppercase; letter-spacing: .8px;
    border-bottom: 1px solid var(--rsc-border);
  }
  .modal-table td {
    padding: 9px 12px; color: var(--rsc-text);
    border-bottom: 1px solid var(--rsc-border);
  }
  .modal-table tbody tr:last-child td { border-bottom: none; }
  .modal-table tbody tr:hover { background: #FAFAF8; }

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
    <h2><span class="accent-dot"></span>Transaksi Tiket</h2>
    <p>Monitoring seluruh transaksi event milik Anda</p>
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
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="#fff" stroke-width="2.2">
          <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.592 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.592-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>

    {{-- Pembayaran Berhasil --}}
    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#1A7A44;"></span>
          Pembayaran Berhasil
        </div>
        <div class="stat-value">{{ $totalPaidCount }}</div>
        <div class="stat-sub">Transaksi selesai</div>
      </div>
      <div class="stat-icon stat-icon-green">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="#1A7A44" stroke-width="2.2">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>

    {{-- Belum Dibayar --}}
    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#B91C1C;"></span>
          Belum Dibayar
        </div>
        <div class="stat-value">{{ $totalUnpaidCount }}</div>
        <div class="stat-sub">Menunggu pembayaran</div>
      </div>
      <div class="stat-icon stat-icon-red">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="#B91C1C" stroke-width="2.2">
          <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>

  </div>

  {{-- Filter --}}
  <div class="rsc-card" style="margin-bottom:18px;">
    <div class="section-title">Filter Transaksi</div>

    <form method="GET" action="{{ route('eo.transactions') }}" class="filter-grid">

      <div class="field-group">
        <label>Pilih Event</label>
        <select name="event_id" class="rsc-input">
          <option value="">Semua Event</option>
          @foreach($events as $event)
            <option value="{{ $event->id }}"
              {{ request('event_id') == $event->id ? 'selected' : '' }}>
              {{ $event->title }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="field-group">
        <label>Tanggal Mulai</label>
        <input type="date" name="start_date"
               value="{{ request('start_date') }}" class="rsc-input">
      </div>

      <div class="field-group">
        <label>Tanggal Selesai</label>
        <input type="date" name="end_date"
               value="{{ request('end_date') }}" class="rsc-input">
      </div>

      <div class="field-group">
        <label>Status Pembayaran</label>
        <select name="payment_status" class="rsc-input">
          <option value="">Semua Status</option>
          <option value="paid"   {{ request('payment_status') == 'paid'   ? 'selected' : '' }}>Paid</option>
          <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
        </select>
      </div>

      <div class="field-group">
        <label>Urutkan</label>
        <select name="sort_by" class="rsc-input">
          <option value="">Default</option>
          <option value="event_title">Judul Event</option>
          <option value="email">Email</option>
          <option value="name">Nama</option>
          <option value="payment_status">Status</option>
          <option value="checkout_time">Checkout</option>
        </select>
      </div>

      <div class="field-group">
        <label>Pencarian</label>
        <input type="text" name="q"
               value="{{ request('q') }}"
               placeholder="Cari email / nama…"
               class="rsc-input">
      </div>

      <div class="span-full" style="display:flex; gap:10px; padding-top:4px;">
        <button type="submit" class="btn-primary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2.5">
            <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
          </svg>
          Terapkan Filter
        </button>
        <a href="{{ route('eo.transactions') }}" class="btn-ghost">Reset</a>
      </div>

    </form>
  </div>

  {{-- Export row --}}
  <div class="action-row">
    <a href="{{ route('eo.transactions.export.excel', request()->query()) }}"
       class="btn-green">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2.2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="9" y1="15" x2="15" y2="15"/>
      </svg>
      Export Excel
    </a>
    <a href="{{ route('eo.transactions.export.pdf', request()->query()) }}"
       target="_blank"
       class="btn-red-outline">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2.2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
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
            <th>No</th>
            <th>Event</th>
            <th>Email</th>
            <th>Checkout</th>
            <th>Paid Time</th>
            <th>Status</th>
            <th>Total</th>
            <th>Tiket</th>
            <th>Aksi</th>
          </tr>
        </thead>

        <tbody>
        @forelse($transactions as $transaction)
          <tr>
            <td class="td-muted">{{ $loop->iteration }}</td>

            <td class="td-bold">{{ $transaction->event->title ?? '-' }}</td>

            <td class="td-mono">{{ $transaction->email }}</td>

            <td class="td-muted" style="font-size:.75rem; white-space:nowrap;">
              {{ $transaction->checkout_time }}
            </td>

            <td class="td-muted" style="font-size:.75rem; white-space:nowrap;">
              {{ $transaction->paid_time ?? '-' }}
            </td>

            <td>
              <span class="badge {{ $transaction->payment_status === 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
                <span class="badge-dot"></span>
                {{ ucfirst($transaction->payment_status) }}
              </span>
            </td>

            <td class="td-bold">
              Rp{{ number_format($transaction->total_amount, 0, ',', '.') }}
            </td>

            <td class="td-muted">{{ $transaction->attendees->count() }} tiket</td>


            <td>
              <button onclick="showDetail({{ $transaction->id }})"
                      class="btn-sm btn-sm-indigo">
                Detail
              </button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="10" class="empty-cell">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
                   stroke="#E8470A" stroke-width="1">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
              <p>Tidak ada transaksi ditemukan</p>
            </td>
          </tr>
        @endforelse
        </tbody>

      </table>
    </div>
  </div>

</div>{{-- /rsc-wrap --}}

{{-- Modal --}}
<div id="detailModal" class="rsc-modal-backdrop">
  <div class="rsc-modal">

    <div class="modal-header">
      <h3 class="modal-title">Detail Pembeli Tiket</h3>
      <button onclick="closeModal()" class="btn-close-modal">✕</button>
    </div>

    <div id="modalContent"></div>

  </div>
</div>

<script>
function showDetail(transactionId) {
  const transactions = @json($transactions);
  const transaction  = transactions.find(t => t.id === transactionId);
  if (!transaction) return;

  let html = '';

  if (!transaction.attendees || transaction.attendees.length === 0) {
    html = `<p style="color:#8A7E76; font-size:.84rem;">Tidak ada attendee untuk transaksi ini.</p>`;
  } else {
    html = `
    <div style="overflow-x:auto;">
      <table class="modal-table">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Phone</th>
            <th>Ticket ID</th>
          </tr>
        </thead>
        <tbody>
          ${transaction.attendees.map(a => `
            <tr>
              <td style="font-weight:600;">${a.name}</td>
              <td style="color:#8A7E76; font-size:.78rem;">${a.phone_number ?? '—'}</td>
              <td style="font-family:monospace; font-size:.75rem; color:#8A7E76;">${a.ticket_id}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>`;
  }

  document.getElementById('modalContent').innerHTML = html;
  document.getElementById('detailModal').classList.add('open');
}

function closeModal() {
  document.getElementById('detailModal').classList.remove('open');
}

// Close on backdrop click
document.getElementById('detailModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>

@endsection