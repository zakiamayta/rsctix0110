@extends('layouts.owner')

@section('title', 'Approval EO')

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

  /* ── Header ── */
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

  /* ── Stats ── */
  .stats-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 20px; }
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
    font-size: 1.75rem; font-weight: 800;
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

  /* ── Toolbar (filter + search) ── */
  .toolbar {
    display: flex; align-items: center;
    justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    margin-bottom: 18px;
  }
  .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
  .tab-btn {
    font-family: 'Sora', sans-serif;
    font-size: .72rem; font-weight: 700;
    padding: 7px 16px; border-radius: 99px;
    border: 1px solid var(--rsc-border);
    background: var(--rsc-surface);
    color: var(--rsc-muted);
    cursor: pointer; transition: all .15s; letter-spacing: .2px;
  }
  .tab-btn:hover { border-color: #B5AEA8; color: var(--rsc-text); }
  .tab-btn.active { background: var(--rsc-accent); border-color: var(--rsc-accent); color: #fff; }

  .search-wrap { position: relative; }
  .search-wrap svg {
    position: absolute; left: 11px; top: 50%;
    transform: translateY(-50%); pointer-events: none;
    color: var(--rsc-muted);
  }
  .search-input {
    font-family: 'DM Sans', sans-serif;
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: 99px;
    padding: 8px 14px 8px 34px;
    font-size: .82rem; color: var(--rsc-text);
    outline: none; width: 220px;
    transition: border-color .18s, box-shadow .18s;
  }
  .search-input::placeholder { color: #BEB5AD; }
  .search-input:focus {
    border-color: var(--rsc-accent);
    box-shadow: 0 0 0 3px var(--rsc-accent-dim);
  }

  /* ── Table ── */
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
  .rsc-table { width: 100%; border-collapse: collapse; min-width: 760px; }
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

  /* ── Badges ── */
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

  /* ── Action buttons ── */
  .btn-sm {
    font-size: .72rem; font-weight: 700;
    padding: 6px 13px; border-radius: 8px;
    border: none; cursor: pointer;
    font-family: 'Sora', sans-serif;
    transition: opacity .15s;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 4px;
  }
  .btn-sm:hover { opacity: .82; }
  .btn-detail  { background: var(--rsc-surface2); color: var(--rsc-text); border: 1px solid var(--rsc-border); }
  .btn-approve { background: #E8F5EE; color: #1A7A44; }
  .btn-reject  { background: #FEF2F2; color: #B91C1C; }

  .action-group { display: flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: wrap; }

  /* ── Empty state ── */
  .empty-cell { padding: 52px 24px; text-align: center; color: var(--rsc-muted); }
  .empty-cell svg { opacity: .18; margin: 0 auto 12px; display: block; }
  .empty-cell p { font-size: .88rem; font-weight: 600; margin: 0; }

  /* ── Modal ── */
  .rsc-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(26,18,8,0.5);
    backdrop-filter: blur(4px);
    display: none; align-items: center; justify-content: center;
    z-index: 50; padding: 24px;
    overflow-y: auto;
  }
  .rsc-modal-backdrop.open { display: flex; }
  .rsc-modal {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    box-shadow: 0 24px 60px rgba(0,0,0,0.16);
    padding: 28px;
    width: 100%; max-width: 760px;
    max-height: 88vh; overflow-y: auto;
    animation: fadeSlide .2s ease;
    position: relative;
  }
  @keyframes fadeSlide {
    from { opacity:0; transform: translateY(10px); }
    to   { opacity:1; transform: translateY(0); }
  }
  .modal-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 22px; gap: 12px;
  }
  .modal-title {
    font-family: 'Sora', sans-serif;
    font-size: 1.05rem; font-weight: 800; color: var(--rsc-text); margin: 0;
  }
  .modal-sub { font-size: .78rem; color: var(--rsc-muted); margin-top: 3px; }
  .btn-close-modal {
    background: var(--rsc-surface2); border: 1px solid var(--rsc-border);
    border-radius: 8px; width: 34px; height: 34px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 1rem; color: var(--rsc-muted);
    transition: background .15s, color .15s;
  }
  .btn-close-modal:hover { background: #EDE8E3; color: var(--rsc-text); }

  /* modal sections */
  .modal-section { margin-bottom: 22px; }
  .modal-section-title {
    font-family: 'Sora', sans-serif;
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.4px;
    color: var(--rsc-accent); margin-bottom: 14px;
    display: flex; align-items: center; gap: 8px;
  }
  .modal-section-title::after {
    content:''; flex:1; height:1px;
    background: linear-gradient(to right, var(--rsc-border), transparent);
  }
  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .info-item label { font-size: .68rem; color: var(--rsc-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .6px; display: block; margin-bottom: 3px; }
  .info-item p { font-size: .84rem; font-weight: 600; color: var(--rsc-text); margin: 0; }
  .info-item.full { grid-column: 1 / -1; }

  .rekening-card {
    background: var(--rsc-surface2);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    padding: 16px 20px;
    display: grid; grid-template-columns: repeat(3,1fr); gap: 16px;
  }

  .doc-frame-wrap {
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    overflow: hidden; margin-bottom: 8px;
  }
  .doc-frame-wrap iframe { display: block; width: 100%; height: 420px; border: none; }
  .doc-frame-wrap img { display: block; width: 100%; max-height: 420px; object-fit: contain; }

  .btn-open-tab {
    font-family: 'Sora', sans-serif;
    font-size: .72rem; font-weight: 700;
    color: var(--rsc-accent); text-decoration: none;
    display: inline-flex; align-items: center; gap: 5px;
    transition: opacity .15s;
  }
  .btn-open-tab:hover { opacity: .75; }

  /* ── Responsive ── */
  @media (max-width: 900px) {
    .stats-row { grid-template-columns: 1fr 1fr; }
    .info-grid  { grid-template-columns: 1fr; }
    .rekening-card { grid-template-columns: 1fr; }
  }
  @media (max-width: 560px) {
    .stats-row { grid-template-columns: 1fr; }
  }
</style>

<div class="rsc-wrap">

  {{-- ── Header ── --}}
  <div class="page-header">
    <h2><span class="accent-dot"></span>Approval EO</h2>
    <p>Tinjau dan kelola pendaftaran Event Organizer baru</p>
  </div>

  {{-- ── Stat Cards ── --}}
  @php
    $countPending  = $eoList->where('status', 'pending')->count();
    $countApproved = $eoList->where('status', 'approved')->count();
    $countRejected = $eoList->where('status', 'rejected')->count();
  @endphp

  <div class="stats-row">

    <div class="stat-card accent">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:rgba(255,255,255,.7);"></span>
          Total EO
        </div>
        <div class="stat-value">{{ $eoList->count() }}</div>
        <div class="stat-sub">Terdaftar dalam sistem</div>
      </div>
      <div class="stat-icon" style="background:rgba(255,255,255,.2);">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2">
          <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
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
        <div class="stat-sub">Perlu ditindaklanjuti</div>
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
          Aktif Disetujui
        </div>
        <div class="stat-value">{{ $countApproved }}</div>
        <div class="stat-sub">EO telah terverifikasi</div>
      </div>
      <div class="stat-icon stat-icon-green">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1A7A44" stroke-width="2.2">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>

  </div>

  {{-- ── Toolbar ── --}}
  <div class="toolbar">
    <div class="filter-tabs" id="filter-bar">
      <button class="tab-btn active" onclick="filterTable('all', this)">
        Semua ({{ $eoList->count() }})
      </button>
      <button class="tab-btn" onclick="filterTable('pending', this)">
        Pending ({{ $countPending }})
      </button>
      <button class="tab-btn" onclick="filterTable('approved', this)">
        Approved ({{ $countApproved }})
      </button>
      <button class="tab-btn" onclick="filterTable('rejected', this)">
        Rejected ({{ $countRejected }})
      </button>
    </div>

    <div class="search-wrap">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2.2">
        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
      </svg>
      <input type="text"
             id="search-input"
             class="search-input"
             placeholder="Cari nama / email / badan usaha…"
             oninput="searchTable(this.value)">
    </div>
  </div>

  {{-- ── Table ── --}}
  <div class="table-wrap">

    <div class="table-header">
      <span class="table-header-title">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5">
          <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
        </svg>
        Daftar Event Organizer
      </span>
      <span style="font-size:.75rem; color:var(--rsc-muted);" id="row-count">
        {{ $eoList->count() }} EO ditampilkan
      </span>
    </div>

    <div style="overflow-x:auto;">
      <table class="rsc-table" id="eo-table">

        <thead>
          <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>Badan Usaha</th>
            <th class="text-center">Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          @forelse($eoList as $eo)
          <tr data-status="{{ $eo->status }}"
              data-search="{{ strtolower($eo->name . ' ' . $eo->email . ' ' . $eo->nama_badan_usaha) }}">

            {{-- Nama --}}
            <td>
              <p style="font-weight:700; margin:0;">{{ $eo->name }}</p>
            </td>

            {{-- Email --}}
            <td>
              <p class="td-mono" style="margin:0;">{{ $eo->email }}</p>
            </td>

            {{-- Badan Usaha --}}
            <td>
              <p style="margin:0;">{{ $eo->nama_badan_usaha }}</p>
              <p class="td-sub">PJ: {{ $eo->penanggung_jawab ?? '-' }}</p>
            </td>

            {{-- Status --}}
            <td class="td-center">
              @if($eo->status === 'pending')
                <span class="badge badge-pending">
                  <span class="badge-dot"></span>Pending
                </span>
              @elseif($eo->status === 'approved')
                <span class="badge badge-approved">
                  <span class="badge-dot"></span>Approved
                </span>
              @else
                <span class="badge badge-rejected">
                  <span class="badge-dot"></span>Rejected
                </span>
              @endif
            </td>

            {{-- Aksi --}}
            <td class="td-center">
              <div class="action-group">

                <button onclick="openModal({{ $eo->id }})" class="btn-sm btn-detail">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                       stroke="currentColor" stroke-width="2.5">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                  Detail
                </button>

                @if($eo->status === 'pending')

                  <form method="POST" action="{{ route('owner.eo.approve', $eo->id) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn-sm btn-approve">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                           stroke="currentColor" stroke-width="2.5">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      </svg>
                      Approve
                    </button>
                  </form>

                  <form method="POST" action="{{ route('owner.eo.reject', $eo->id) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn-sm btn-reject">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                           stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                      </svg>
                      Reject
                    </button>
                  </form>

                @endif

              </div>
            </td>

          </tr>
          @empty
          <tr>
            <td colspan="5" class="empty-cell">
              <svg width="44" height="44" viewBox="0 0 24 24" fill="none"
                   stroke="#E8470A" stroke-width="1">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
              </svg>
              <p>Belum ada data Event Organizer</p>
            </td>
          </tr>
          @endforelse
        </tbody>

      </table>
    </div>

  </div>

</div>{{-- /rsc-wrap --}}

{{-- ══════════════════════════════════════ --}}
{{-- MODALS --}}
{{-- ══════════════════════════════════════ --}}

@foreach($eoList as $eo)
<div id="modal-{{ $eo->id }}" class="rsc-modal-backdrop">
  <div class="rsc-modal" onclick="event.stopPropagation()">

    {{-- Header --}}
    <div class="modal-header">
      <div>
        <h3 class="modal-title">{{ $eo->name }}</h3>
        <p class="modal-sub">{{ $eo->nama_badan_usaha }}</p>
      </div>
      <button onclick="closeModal({{ $eo->id }})" class="btn-close-modal">✕</button>
    </div>

    {{-- Info Umum --}}
    <div class="modal-section">
      <p class="modal-section-title">Informasi Umum</p>
      <div class="info-grid">
        <div class="info-item">
          <label>Nama Lengkap</label>
          <p>{{ $eo->name }}</p>
        </div>
        <div class="info-item">
          <label>Email</label>
          <p>{{ $eo->email }}</p>
        </div>
        <div class="info-item">
          <label>Badan Usaha</label>
          <p>{{ $eo->nama_badan_usaha }}</p>
        </div>
        <div class="info-item">
          <label>Penanggung Jawab</label>
          <p>{{ $eo->penanggung_jawab ?? '-' }}</p>
        </div>
        <div class="info-item full">
          <label>Alamat</label>
          <p>{{ $eo->alamat_badan_usaha ?? '-' }}</p>
        </div>
      </div>
    </div>

    {{-- Rekening --}}
    <div class="modal-section">
      <p class="modal-section-title">Informasi Rekening</p>
      <div class="rekening-card">
        <div>
          <label style="font-size:.68rem; color:var(--rsc-muted); font-weight:600; text-transform:uppercase; letter-spacing:.6px; display:block; margin-bottom:4px;">Nama Bank</label>
          <p style="font-weight:700; font-size:.88rem; margin:0;">{{ $eo->bank_name ?? '-' }}</p>
        </div>
        <div>
          <label style="font-size:.68rem; color:var(--rsc-muted); font-weight:600; text-transform:uppercase; letter-spacing:.6px; display:block; margin-bottom:4px;">Nomor Rekening</label>
          <p style="font-weight:700; font-size:.88rem; margin:0; font-family:monospace;">{{ $eo->account_number ?? '-' }}</p>
        </div>
        <div>
          <label style="font-size:.68rem; color:var(--rsc-muted); font-weight:600; text-transform:uppercase; letter-spacing:.6px; display:block; margin-bottom:4px;">Nama Pemilik</label>
          <p style="font-weight:700; font-size:.88rem; margin:0;">{{ $eo->account_name ?? '-' }}</p>
        </div>
      </div>
    </div>

    {{-- Dokumen PDF --}}
    <div class="modal-section">
      <p class="modal-section-title">Dokumen Badan Usaha</p>
      <div class="doc-frame-wrap">
        <iframe src="{{ asset($eo->dokumen_badan_usaha) }}"></iframe>
      </div>
      <a href="{{ asset($eo->dokumen_badan_usaha) }}" target="_blank" class="btn-open-tab">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5">
          <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
          <polyline points="15 3 21 3 21 9"/>
          <line x1="10" y1="14" x2="21" y2="3"/>
        </svg>
        Buka di tab baru
      </a>
    </div>

    {{-- KTP --}}
    <div class="modal-section">
      <p class="modal-section-title">KTP Penanggung Jawab</p>
      <div class="doc-frame-wrap">
        <img src="{{ asset($eo->ktp_penanggung_jawab) }}" alt="KTP">
      </div>
      <a href="{{ asset($eo->ktp_penanggung_jawab) }}" target="_blank" class="btn-open-tab">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5">
          <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
          <polyline points="15 3 21 3 21 9"/>
          <line x1="10" y1="14" x2="21" y2="3"/>
        </svg>
        Buka di tab baru
      </a>
    </div>

  </div>
</div>
@endforeach

<script>
  /* ── Modal ── */
  function openModal(id) {
    document.getElementById('modal-' + id).classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(id) {
    document.getElementById('modal-' + id).classList.remove('open');
    document.body.style.overflow = '';
  }
  document.querySelectorAll('.rsc-modal-backdrop').forEach(el => {
    el.addEventListener('click', function(e) {
      if (e.target === this) {
        this.classList.remove('open');
        document.body.style.overflow = '';
      }
    });
  });

  /* ── Filter tabs ── */
  let currentFilter = 'all';
  let currentSearch = '';

  function filterTable(status, btn) {
    currentFilter = status;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
  }

  function searchTable(q) {
    currentSearch = q.toLowerCase().trim();
    applyFilters();
  }

  function applyFilters() {
    const rows = document.querySelectorAll('#eo-table tbody tr[data-status]');
    let visible = 0;
    rows.forEach(row => {
      const matchStatus = currentFilter === 'all' || row.dataset.status === currentFilter;
      const matchSearch = currentSearch === '' || row.dataset.search.includes(currentSearch);
      const show = matchStatus && matchSearch;
      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    document.getElementById('row-count').textContent = visible + ' EO ditampilkan';
  }
</script>

@endsection