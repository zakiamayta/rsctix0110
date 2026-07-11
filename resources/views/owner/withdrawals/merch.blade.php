@extends('layouts.owner')

@section('title', 'Approval Withdrawal Merchandise')

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

  .rsc-alert {
    border-radius: var(--radius);
    padding: 12px 16px;
    font-size: .84rem; font-weight: 500;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
  }
  .rsc-alert-success { background: #E8F5EE; border: 1px solid #A7D5B8; color: #1A5C35; }
  .rsc-alert-error   { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }
  .rsc-alert svg { flex-shrink: 0; }

  /* Style Tambahan Filter Form */
  .filter-box {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    padding: 16px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.02);
  }
  .filter-form { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
  .filter-form label { font-size: .78rem; font-weight: 700; color: var(--rsc-text); text-transform: uppercase; }
  .filter-select {
    padding: 8px 14px; font-size: .85rem; border-radius: 8px;
    border: 1px solid var(--rsc-border); background: #fff; color: var(--rsc-text);
    min-width: 240px; outline: none;
  }
  .btn-filter {
    font-size: .78rem; font-weight: 700; padding: 9px 18px; border-radius: 8px;
    border: none; background: var(--rsc-text); color: #fff; cursor: pointer; transition: opacity .15s;
  }
  .btn-reset {
    font-size: .78rem; font-weight: 700; padding: 9px 18px; border-radius: 8px;
    border: 1px solid var(--rsc-border); background: var(--rsc-surface2); color: var(--rsc-text);
    cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
  }
  .btn-filter:hover, .btn-reset:hover { opacity: .85; }

  /* ── Filter tabs status (baru) ── */
  .filter-tabs {
    display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px;
  }
  .tab-btn {
    font-family: 'Sora', sans-serif;
    font-size: .72rem; font-weight: 700;
    padding: 7px 16px; border-radius: 99px;
    border: 1px solid var(--rsc-border);
    background: var(--rsc-surface);
    color: var(--rsc-muted);
    cursor: pointer; transition: all .15s;
    letter-spacing: .2px;
  }
  .tab-btn:hover { border-color: #B5AEA8; color: var(--rsc-text); }
  .tab-btn.active {
    background: var(--rsc-accent);
    border-color: var(--rsc-accent);
    color: #fff;
  }

  .stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 20px; }
  .stat-card {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    padding: 20px 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
  }
  .stat-card.accent { background: var(--rsc-accent); border-color: var(--rsc-accent); }
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
    font-size: 1.55rem; font-weight: 800;
    color: var(--rsc-text); line-height: 1; margin-bottom: 3px;
  }
  .stat-card.accent .stat-value { color: #fff; }
  .stat-sub { font-size: .72rem; color: var(--rsc-muted); }
  .stat-card.accent .stat-sub { color: rgba(255,255,255,.6); }
  .stat-icon {
    width: 52px; height: 52px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .stat-icon-yellow { background: rgba(180,120,0,.09); }
  .stat-icon-green  { background: rgba(26,122,68,.1); }
  .stat-icon-red    { background: rgba(185,28,28,.08); }

  .table-wrap {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
    overflow: hidden;
  }
  .table-header {
    padding: 18px 22px 16px;
    border-bottom: 1px solid var(--rsc-border);
    display: flex; align-items: center; justify-content: space-between;
  }
  .table-header-title {
    font-family: 'Sora', sans-serif;
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.5px;
    color: var(--rsc-accent);
    display: flex; align-items: center; gap: 8px;
  }
  .rsc-table { width: 100%; border-collapse: collapse; min-width: 860px; }
  .rsc-table thead tr { background: var(--rsc-surface2); border-bottom: 1px solid var(--rsc-border); }
  .rsc-table th {
    padding: 11px 14px; text-align: left;
    font-size: .65rem; font-weight: 700;
    color: var(--rsc-muted); text-transform: uppercase; letter-spacing: 1px;
    white-space: nowrap;
  }
  .rsc-table th.text-center { text-align: center; }
  .rsc-table tbody tr { border-bottom: 1px solid var(--rsc-border); transition: background .12s; }
  .rsc-table tbody tr:last-child { border-bottom: none; }
  .rsc-table tbody tr:hover { background: #FAFAF8; }
  .rsc-table td { padding: 13px 14px; font-size: .82rem; color: var(--rsc-text); vertical-align: middle; }
  .td-center { text-align: center; }
  .td-sub  { font-size: .72rem; color: var(--rsc-muted); margin-top: 3px; }
  .td-mono { font-size: .75rem; color: var(--rsc-muted); font-family: monospace; margin-top: 3px; }
  .td-amount {
    font-family: 'Sora', sans-serif;
    font-size: .9rem; font-weight: 800; color: var(--rsc-accent);
  }

  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .66rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
    padding: 4px 10px; border-radius: 20px;
  }
  .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
  .badge-pending  { background: #FEF3C7; color: #B45309; }
  .badge-approved { background: #E8F5EE; color: #1A7A44; }
  .badge-rejected { background: #FEF2F2; color: #B91C1C; }
  .badge-unknown  { background: var(--rsc-surface2); color: var(--rsc-muted); }

  .invoice-pill {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px;
    padding: 5px 12px; border-radius: 20px;
    text-decoration: none; transition: opacity .15s;
  }
  .invoice-pill:hover { opacity: .82; }
  .invoice-pill.has-file { background: #E8F5EE; color: #1A7A44; }
  .invoice-pill.no-file  { background: var(--rsc-surface2); color: var(--rsc-muted); border: 1px solid var(--rsc-border); }

  .btn-sm {
    font-size: .72rem; font-weight: 700;
    padding: 7px 16px; border-radius: 8px;
    border: none; cursor: pointer;
    font-family: 'Sora', sans-serif;
    transition: opacity .15s;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--rsc-accent); color: #fff;
  }
  .btn-sm:hover { opacity: .85; }

  .empty-cell { padding: 52px 24px; text-align: center; color: var(--rsc-muted); }
  .empty-cell svg { opacity: .18; margin: 0 auto 12px; display: block; }
  .empty-cell p { font-size: .88rem; font-weight: 600; margin: 0; }

  .pagination-wrap { padding: 16px 22px; border-top: 1px solid var(--rsc-border); }

  @media (max-width: 1024px) { .stats-row { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 560px)  { .stats-row { grid-template-columns: 1fr; } }
</style>

<div class="rsc-wrap">

  {{-- ── Header ── --}}
  <div class="page-header">
    <h2><span class="accent-dot"></span>Approval Withdrawal Merchandise</h2>
    <p>Kelola seluruh pengajuan pencairan dana merchandise milik Event Organizer</p>
  </div>

  {{-- ── Flash Messages ── --}}
  @if(session('success'))
  <div class="rsc-alert rsc-alert-success">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('success') }}
  </div>
  @endif

  @if(session('error'))
  <div class="rsc-alert rsc-alert-error">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
      <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('error') }}
  </div>
  @endif

  {{-- ── Form Penyortiran / Filter Event ── --}}
  <div class="filter-box">
    <form action="{{ route('owner.withdrawals.merch.index') }}" method="GET" class="filter-form">
      <label for="event_id">Filter Event:</label>
      <select name="event_id" id="event_id" class="filter-select">
        <option value="">-- Semua Event --</option>
        @foreach($filterEvents as $e)
          <option value="{{ $e->id }}" {{ request('event_id') == $e->id ? 'selected' : '' }}>
            {{ $e->title }}
          </option>
        @endforeach
      </select>
      <button type="submit" class="btn-filter">Terapkan</button>
      @if(request('event_id'))
        <a href="{{ route('owner.withdrawals.merch.index') }}" class="btn-reset">Reset</a>
      @endif
    </form>
  </div>

  {{-- ── Stat Cards ── --}}
  @php
    $allItems      = $withdrawals->getCollection();
    $countPending  = $allItems->where('status', 'pending')->count();
    $countApproved = $allItems->where('status', 'approved')->count();
    $countRejected = $allItems->where('status', 'rejected')->count();
  @endphp

  <div class="stats-row">

    {{-- KARTU SALDO YANG SUDAH DINAMIS MENGIKUTI SORTIR --}}
    <div class="stat-card accent">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:rgba(255,255,255,.7);"></span>
          Saldo Merch Tersedia
        </div>
        <div class="stat-value" style="font-size: 1.35rem;">Rp{{ number_format($totalAvailableBalance, 0, ',', '.') }}</div>
        <div class="stat-sub">{{ request('event_id') ? 'Saldo Event Terpilih' : 'Total Semua Event' }}</div>
      </div>
      <div class="stat-icon" style="background:rgba(255,255,255,.2);">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2">
          <rect x="2" y="4" width="20" height="16" rx="2"/>
          <line x1="12" y1="11" x2="12" y2="13"/>
          <line x1="8" y1="12" x2="16" y2="12"/>
        </svg>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#B45309;"></span>
          Menunggu Review
        </div>
        <div class="stat-value">{{ $countPending }}</div>
        <div class="stat-sub">Halaman ini</div>
      </div>
      <div class="stat-icon stat-icon-yellow">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#B45309" stroke-width="2.2">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#1A7A44;"></span>
          Disetujui
        </div>
        <div class="stat-value">{{ $countApproved }}</div>
        <div class="stat-sub">Halaman ini</div>
      </div>
      <div class="stat-icon stat-icon-green">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1A7A44" stroke-width="2.2">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#B91C1C;"></span>
          Ditolak
        </div>
        <div class="stat-value">{{ $countRejected }}</div>
        <div class="stat-sub">Halaman ini</div>
      </div>
      <div class="stat-icon stat-icon-red">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#B91C1C" stroke-width="2.2">
          <circle cx="12" cy="12" r="10"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
          <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
      </div>
    </div>

  </div>

  {{-- ── Filter Tabs Status (baru, seperti halaman Approval Event) ── --}}
  <div class="filter-tabs" id="filter-bar">
    <button class="tab-btn active" onclick="filterMerchTable('all', this)">
      Semua ({{ $allItems->count() }})
    </button>
    <button class="tab-btn" onclick="filterMerchTable('pending', this)">
      Pending ({{ $countPending }})
    </button>
    <button class="tab-btn" onclick="filterMerchTable('approved', this)">
      Approved ({{ $countApproved }})
    </button>
    <button class="tab-btn" onclick="filterMerchTable('rejected', this)">
      Rejected ({{ $countRejected }})
    </button>
  </div>

  {{-- ── Table ── --}}
  <div class="table-wrap">

    <div class="table-header">
      <span class="table-header-title">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
          <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
        </svg>
        Daftar Pengajuan Merchandise
      </span>
      <span style="font-size:.75rem; color:var(--rsc-muted);">
        Halaman {{ $withdrawals->currentPage() }} dari {{ $withdrawals->lastPage() }}
      </span>
    </div>

    <div style="overflow-x:auto;">
      <table class="rsc-table" id="merch-table">

        <thead>
          <tr>
            <th>Event</th>
            <th>Event Organizer</th>
            <th class="text-center">Invoice</th>
            <th>Jumlah</th>
            <th>Tanggal Pengajuan</th>
            <th class="text-center">Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          @forelse($withdrawals as $withdrawal)
          <tr data-status="{{ $withdrawal->status }}">

            {{-- Event --}}
            <td>
              <p style="font-weight:700; margin:0;">
                {{ optional($withdrawal->event)->title ?? '-' }}
              </p>
            </td>

            {{-- EO --}}
            <td>
              <p style="font-weight:500; margin:0;">
                {{ optional($withdrawal->eo)->nama_badan_usaha ?? '-' }}
              </p>
            </td>

            {{-- Invoice --}}
            <td class="td-center">
              @if($withdrawal->invoice_file)
                <a href="{{ asset($withdrawal->invoice_file) }}"
                   target="_blank"
                   class="invoice-pill has-file">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
                       stroke="currentColor" stroke-width="2.5">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                  Lihat
                </a>
              @else
                <span class="invoice-pill no-file">Tidak Ada</span>
              @endif
            </td>

            {{-- Amount --}}
            <td>
              <p class="td-amount" style="margin:0;">
                Rp{{ number_format($withdrawal->amount, 0, ',', '.') }}
              </p>
            </td>

            {{-- Date --}}
            <td>
              <p style="margin:0;">{{ $withdrawal->created_at->format('d M Y') }}</p>
              <p class="td-sub">{{ $withdrawal->created_at->format('H:i') }} WIB</p>
            </td>

            {{-- Status --}}
            <td class="td-center">
              @switch($withdrawal->status)
                @case('pending')
                  <span class="badge badge-pending">
                    <span class="badge-dot"></span>Pending
                  </span>
                @break
                @case('approved')
                  <span class="badge badge-approved">
                    <span class="badge-dot"></span>Approved
                  </span>
                @break
                @case('rejected')
                  <span class="badge badge-rejected">
                    <span class="badge-dot"></span>Rejected
                  </span>
                @break
                @default
                  <span class="badge badge-unknown">
                    <span class="badge-dot"></span>Unknown
                  </span>
              @endswitch
            </td>

            {{-- Action --}}
            <td class="td-center">
              <a href="{{ route('owner.withdrawals.merch.show', $withdrawal->id) }}"
                 class="btn-sm">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                Detail
              </a>
            </td>

          </tr>
          @empty
          <tr>
            <td colspan="7" class="empty-cell">
              <svg width="44" height="44" viewBox="0 0 24 24" fill="none"
                   stroke="#f97316" stroke-width="1">
                <path d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
                <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
              </svg>
              <p>Belum ada pengajuan withdrawal merchandise</p>
            </td>
          </tr>
          @endforelse
        </tbody>

      </table>
    </div>

    @if($withdrawals->hasPages())
    <div class="pagination-wrap">
      {{ $withdrawals->links() }}
    </div>
    @endif

  </div>

</div>

<script>
function filterMerchTable(status, btn) {
  document.querySelectorAll('#filter-bar .tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#merch-table tbody tr[data-status]').forEach(row => {
    row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
  });
}
</script>

@endsection