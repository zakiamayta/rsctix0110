@extends('layouts.owner')

@section('title', 'Approval Event')

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
  .stat-icon-yellow { background: rgba(180,120,0,.09); }
  .stat-icon-red    { background: rgba(185,28,28,.08); }
  .stat-icon-blue   { background: rgba(29,78,216,.08); }

  /* ── Filter tabs ── */
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

  /* ── Table ── */
  .table-wrap {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
    overflow: hidden;
  }
  .rsc-table { width: 100%; border-collapse: collapse; min-width: 760px; }
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
    padding: 12px 14px; font-size: .82rem;
    color: var(--rsc-text); vertical-align: middle;
  }
  .td-muted { color: var(--rsc-muted); font-size: .75rem; }
  .td-sub   { font-size: .72rem; color: var(--rsc-muted); margin-top: 2px; }
  .td-strike { text-decoration: line-through; color: var(--rsc-muted); font-size: .75rem; }
  .td-arrow  {
    display: flex; align-items: center; gap: 5px;
    font-size: .78rem; color: var(--rsc-text); font-weight: 500; margin-top: 2px;
  }
  .td-arrow svg { flex-shrink: 0; }

  /* ── Badges ── */
  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .66rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
    padding: 4px 10px; border-radius: 20px;
  }
  .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
  .badge-pending    { background: #FEF3C7; color: #B45309; }
  .badge-cancel     { background: #FEF2F2; color: #B91C1C; }
  .badge-reschedule { background: #EFF6FF; color: #1D4ED8; }

  /* ── Action button ── */
  .btn-sm {
    font-size: .72rem; font-weight: 700;
    padding: 6px 14px; border-radius: 8px;
    border: 1px solid var(--rsc-border);
    background: var(--rsc-surface2);
    color: var(--rsc-text);
    cursor: pointer; font-family: 'Sora', sans-serif;
    transition: border-color .15s, background .15s;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 5px;
  }
  .btn-sm:hover { border-color: #B5AEA8; background: #EDE8E3; }

  /* ── Empty state ── */
  .empty-cell { padding: 48px 24px; text-align: center; color: var(--rsc-muted); }
  .empty-cell svg { opacity: .18; margin: 0 auto 10px; display: block; }
  .empty-cell p { font-size: .88rem; font-weight: 600; margin: 0; }

  /* ── Responsive ── */
  @media (max-width: 900px) {
    .stats-row { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 560px) {
    .stats-row { grid-template-columns: 1fr; }
  }
</style>

<div class="rsc-wrap">

  {{-- ── Header ── --}}
  <div class="page-header">
    <h2><span class="accent-dot"></span>Approval Event</h2>
    <p>Review dan tindak lanjuti pengajuan dari Event Organizer</p>
  </div>

  {{-- ── Stat Cards ── --}}
  @php
    $countPending    = $events->where('status', 'pending')->count();
    $countCancel     = $events->where('status', 'pending_cancel')->count();
    $countReschedule = $events->where('status', 'pending_reschedule')->count();
  @endphp

  <div class="stats-row">

    <div class="stat-card accent">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:rgba(255,255,255,.7);"></span>
          Total Pengajuan
        </div>
        <div class="stat-value">{{ $events->count() }}</div>
        <div class="stat-sub">Semua yang perlu ditinjau</div>
      </div>
      <div class="stat-icon" style="background:rgba(255,255,255,.2);">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="#fff" stroke-width="2.2">
          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#B45309;"></span>
          Pending Baru
        </div>
        <div class="stat-value">{{ $countPending }}</div>
        <div class="stat-sub">Event baru menunggu review</div>
      </div>
      <div class="stat-icon stat-icon-yellow">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="#B45309" stroke-width="2.2">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#B91C1C;"></span>
          Cancel &amp; Reschedule
        </div>
        <div class="stat-value">{{ $countCancel + $countReschedule }}</div>
        <div class="stat-sub">Perubahan dari EO aktif</div>
      </div>
      <div class="stat-icon stat-icon-red">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="#B91C1C" stroke-width="2.2">
          <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>

  </div>

  {{-- ── Filter Tabs ── --}}
  <div class="filter-tabs" id="filter-bar">
    <button class="tab-btn active" onclick="filterTable('all', this)">
      Semua ({{ $events->count() }})
    </button>
    <button class="tab-btn" onclick="filterTable('pending', this)">
      Pending Baru ({{ $countPending }})
    </button>
    <button class="tab-btn" onclick="filterTable('pending_cancel', this)">
      Pending Cancel ({{ $countCancel }})
    </button>
    <button class="tab-btn" onclick="filterTable('pending_reschedule', this)">
      Pending Reschedule ({{ $countReschedule }})
    </button>
  </div>

  {{-- ── Table ── --}}
  <div class="table-wrap">
    <div style="overflow-x:auto;">
      <table class="rsc-table" id="event-table">

        <thead>
          <tr>
            <th>Judul Event</th>
            <th>Event Organizer</th>
            <th>Tanggal</th>
            <th>Jenis Pengajuan</th>
            <th>Aksi</th>
          </tr>
        </thead>

        <tbody>
          @forelse($events as $event)
          <tr data-status="{{ $event->status }}">

            {{-- Judul --}}
            <td>
              <p style="font-weight:700; margin:0;">{{ $event->title }}</p>
              <p class="td-sub">Diajukan {{ $event->updated_at->diffForHumans() }}</p>
            </td>

            {{-- EO --}}
            <td>
              <p style="font-weight:500; margin:0;">{{ $event->eo->nama_badan_usaha ?? '-' }}</p>
            </td>

            {{-- Tanggal ── reschedule tampilkan coret + tanggal baru --}}
            <td>
              @if($event->status === 'pending_reschedule' && $event->proposed_date)
                <p class="td-strike">
                  {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d M Y') }}
                </p>
                <div class="td-arrow">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                       stroke="#f97316" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                  </svg>
                  {{ \Carbon\Carbon::parse($event->proposed_date)->translatedFormat('d M Y') }}
                </div>
              @else
                <p style="margin:0;">
                  {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d M Y') }}
                </p>
              @endif
            </td>

            {{-- Badge --}}
            <td>
              @if($event->status === 'pending')
                <span class="badge badge-pending">
                  <span class="badge-dot"></span>Pending Baru
                </span>
              @elseif($event->status === 'pending_cancel')
                <span class="badge badge-cancel">
                  <span class="badge-dot"></span>Pengajuan Cancel
                </span>
              @elseif($event->status === 'pending_reschedule')
                <span class="badge badge-reschedule">
                  <span class="badge-dot"></span>Pengajuan Reschedule
                </span>
              @endif
            </td>

            {{-- Aksi --}}
            <td>
              <a href="{{ route('owner.events.show', $event->id) }}" class="btn-sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                Lihat Detail
              </a>
            </td>

          </tr>
          @empty
          <tr>
            <td colspan="5" class="empty-cell">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
                   stroke="#f97316" stroke-width="1">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
              <p>Tidak ada pengajuan yang perlu ditinjau saat ini</p>
            </td>
          </tr>
          @endforelse
        </tbody>

      </table>
    </div>
  </div>

</div>{{-- /rsc-wrap --}}

<script>
function filterTable(status, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#event-table tbody tr[data-status]').forEach(row => {
    row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
  });
}
</script>

@endsection