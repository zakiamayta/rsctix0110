@extends('layouts.admin')

@section('title', 'Transaksi Refund')

@push('styles')
<link href="{{ asset('css/admin_dashboard.css') }}" rel="stylesheet">
@endpush

@section('content')

<style>
  .rsc-wrap * { font-family: var(--font-main), 'Poppins', sans-serif; box-sizing: border-box; }
  .rsc-wrap { color: var(--rsc-dark); }

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

  .rsc-card {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: 14px;
    padding: 22px 24px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
  }

  .stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 20px; }
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
    font-size: 1.6rem; font-weight: 800;
    color: var(--rsc-dark); line-height: 1; margin-bottom: 3px;
  }
  .stat-card.accent .stat-value { color: #fff; }
  .stat-sub { font-size: .72rem; color: var(--rsc-muted); }
  .stat-card.accent .stat-sub { color: rgba(255,255,255,.6); }
  .stat-icon {
    width: 48px; height: 48px; border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .stat-icon-red    { background: rgba(185,28,28,.08); }
  .stat-icon-orange { background: var(--rsc-orange-light); }
  .stat-icon-green  { background: rgba(26,122,68,.1); }
  .stat-card.accent .stat-icon { background: rgba(255,255,255,.2); }

  /* Tabs tipe komoditas */
  .type-tabs { display: flex; gap: 8px; margin-bottom: 18px; }
  .type-tab {
    padding: 9px 18px; border-radius: 9px; font-size: .82rem; font-weight: 700;
    border: 1px solid var(--rsc-border); background: var(--rsc-surface); color: var(--rsc-muted);
    text-decoration: none; transition: all .15s;
  }
  .type-tab.active { background: var(--rsc-orange); border-color: var(--rsc-orange); color: #fff; }

  .filter-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
  .filter-grid .span-full { grid-column: 1 / -1; }
  .field-group { display: flex; flex-direction: column; gap: 5px; }
  .field-group label {
    font-size: .72rem; font-weight: 700;
    color: var(--rsc-muted); text-transform: uppercase; letter-spacing: .6px;
  }
  .rsc-input {
    background: var(--rsc-bg); border: 1px solid var(--rsc-border); border-radius: 9px;
    color: var(--rsc-dark); padding: 9px 12px; font-size: .84rem; width: 100%;
    transition: border-color .18s, box-shadow .18s; outline: none;
    font-family: var(--font-main), 'Poppins', sans-serif;
  }
  .rsc-input:focus { border-color: var(--rsc-orange); box-shadow: 0 0 0 3px var(--rsc-orange-light); }
  select.rsc-input { cursor: pointer; }

  .btn-primary {
    background: var(--rsc-orange); color: #fff; border: none; border-radius: 9px;
    padding: 9px 20px; font-size: .82rem; font-weight: 700; cursor: pointer;
    font-family: var(--font-main), 'Poppins', sans-serif; transition: opacity .15s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-primary:hover { opacity: .85; }
  .btn-ghost {
    background: var(--rsc-bg); color: var(--rsc-muted); border: 1px solid var(--rsc-border);
    border-radius: 9px; padding: 9px 20px; font-size: .82rem; font-weight: 700; cursor: pointer;
    font-family: var(--font-main), 'Poppins', sans-serif; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-ghost:hover { border-color: #B5AEA8; color: var(--rsc-dark); }
  .btn-sm {
    font-size: .72rem; font-weight: 700; padding: 5px 12px; border-radius: 7px;
    border: none; cursor: pointer; font-family: var(--font-main), 'Poppins', sans-serif;
    transition: opacity .15s; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
  }
  .btn-sm-orange { background: var(--rsc-orange-light); color: var(--rsc-orange); }
  .btn-sm-orange:hover { background: #ffe3d3; }

  .table-wrap {
    background: var(--rsc-surface); border: 1px solid var(--rsc-border); border-radius: 14px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.05); overflow: hidden;
  }
  .rsc-table { width: 100%; border-collapse: collapse; min-width: 1020px; }
  .rsc-table thead tr { background: var(--rsc-bg); border-bottom: 1px solid var(--rsc-border); }
  .rsc-table th {
    padding: 11px 14px; text-align: left; font-size: .65rem; font-weight: 700;
    color: var(--rsc-muted); text-transform: uppercase; letter-spacing: 1px; white-space: nowrap;
  }
  .rsc-table tbody tr { border-bottom: 1px solid var(--rsc-border); transition: background .12s; }
  .rsc-table tbody tr:last-child { border-bottom: none; }
  .rsc-table tbody tr:hover { background: #FAFAF8; }
  .rsc-table td { padding: 11px 14px; font-size: .82rem; color: var(--rsc-dark); vertical-align: middle; }
  .td-bold { font-weight: 700; }
  .td-muted { color: var(--rsc-muted); }
  .td-mono { font-size: .78rem; color: var(--rsc-muted); font-family: monospace; }

  .badge {
    display: inline-flex; align-items: center; gap: 5px; font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px; padding: 4px 10px; border-radius: 20px;
  }
  .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
  .badge-refunded { background: #E8F5EE; color: #1A7A44; }
  .badge-pending  { background: #FFF5E0; color: #9A6200; }
  .badge-waiting  { background: #F1F1F1; color: #6B6B6B; }
  .badge-rejected { background: #FEF2F2; color: #B91C1C; }

  .empty-cell { padding: 48px 24px; text-align: center; color: var(--rsc-muted); }
  .empty-cell svg { opacity: .18; margin: 0 auto 10px; display: block; }
  .empty-cell p { font-size: .88rem; font-weight: 600; margin: 0; }
  .empty-cell p.small { font-size: .78rem; font-weight: 400; margin-top: 4px; color: var(--rsc-subtle); }

  .rsc-modal-backdrop {
    position: fixed; inset: 0; background: rgba(26,18,8,0.45); backdrop-filter: blur(4px);
    display: none; align-items: center; justify-content: center; z-index: 50; padding: 24px;
  }
  .rsc-modal-backdrop.open { display: flex; }
  .rsc-modal {
    background: var(--rsc-surface); border: 1px solid var(--rsc-border); border-radius: 14px;
    box-shadow: 0 24px 60px rgba(0,0,0,0.14); padding: 28px; width: 100%; max-width: 720px;
    max-height: 82vh; overflow-y: auto;
  }
  .modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
  .modal-title { font-family: var(--font-main), 'Poppins', sans-serif; font-size: 1.05rem; font-weight: 800; color: var(--rsc-dark); margin: 0; }
  .modal-sub { font-size: .78rem; color: var(--rsc-muted); margin: 2px 0 18px; }
  .btn-close-modal {
    background: var(--rsc-bg); border: 1px solid var(--rsc-border); border-radius: 8px;
    width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 1rem; color: var(--rsc-muted); flex-shrink: 0;
  }
  .btn-close-modal:hover { background: #EDE8E3; color: var(--rsc-dark); }

  .refund-summary-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 18px;
  }
  .rs-box {
    background: var(--rsc-bg); border: 1px solid var(--rsc-border); border-radius: 10px; padding: 12px 14px;
  }
  .rs-box .rs-label { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--rsc-muted); margin-bottom: 4px; }
  .rs-box .rs-value { font-size: .92rem; font-weight: 800; color: var(--rsc-dark); }

  .modal-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
  .modal-table thead tr { background: var(--rsc-bg); }
  .modal-table th {
    padding: 9px 12px; text-align: left; font-size: .65rem; font-weight: 700;
    color: var(--rsc-muted); text-transform: uppercase; letter-spacing: .8px; border-bottom: 1px solid var(--rsc-border);
  }
  .modal-table td { padding: 9px 12px; color: var(--rsc-dark); border-bottom: 1px solid var(--rsc-border); }
  .modal-table tbody tr:last-child td { border-bottom: none; }
  .modal-table tbody tr:hover { background: #FAFAF8; }

  @media (max-width: 900px) {
    .stats-row { grid-template-columns: 1fr 1fr; }
    .filter-grid { grid-template-columns: 1fr 1fr; }
    .filter-grid .span-full { grid-column: 1 / -1; }
    .refund-summary-grid { grid-template-columns: 1fr; }
  }
  @media (max-width: 560px) {
    .stats-row { grid-template-columns: 1fr; }
    .filter-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="rsc-wrap">

  {{-- Header --}}
  <div class="page-header">
    <h2><span class="accent-dot"></span>Transaksi Refund</h2>
    <p>Riwayat pengeluaran dana refund kepada pembeli tiket / merchandise</p>
  </div>

  {{-- Stats --}}
  <div class="stats-row">
    <div class="stat-card accent">
      <div>
        <div class="stat-label"><span class="stat-dot" style="background:rgba(255,255,255,.7);"></span>Total Dana Keluar</div>
        <div class="stat-value">Rp{{ number_format($totalRefunded, 0, ',', '.') }}</div>
        <div class="stat-sub">Sudah dikembalikan ke pembeli</div>
      </div>
      <div class="stat-icon" style="background:rgba(255,255,255,.2);">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2">
          <polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/>
        </svg>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-label"><span class="stat-dot" style="background:#9A6200;"></span>Biaya Admin Xendit</div>
        <div class="stat-value">Rp{{ number_format($totalFee, 0, ',', '.') }}</div>
        <div class="stat-sub">Ditanggung platform</div>
      </div>
      <div class="stat-icon stat-icon-orange">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--rsc-orange)" stroke-width="2.2">
          <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>
        </svg>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-label"><span class="stat-dot" style="background:#B91C1C;"></span>Menunggu Diproses</div>
        <div class="stat-value">{{ $pendingCount }}</div>
        <div class="stat-sub">Antrean refund</div>
      </div>
      <div class="stat-icon stat-icon-red">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#B91C1C" stroke-width="2.2">
          <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-label"><span class="stat-dot" style="background:#1A7A44;"></span>Refund Selesai</div>
        <div class="stat-value">{{ $refundedCount }}</div>
        <div class="stat-sub">Transaksi terselesaikan</div>
      </div>
      <div class="stat-icon stat-icon-green">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1A7A44" stroke-width="2.2">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>
  </div>

  {{-- Tabs tipe komoditas --}}
  <div class="type-tabs">
    <a href="{{ route('admin.refund.transactions', array_merge(request()->except(['type']), ['type' => 'ticket'])) }}"
       class="type-tab {{ $type === 'ticket' ? 'active' : '' }}">🎟️ Tiket</a>
    <a href="{{ route('admin.refund.transactions', array_merge(request()->except(['type']), ['type' => 'merch'])) }}"
       class="type-tab {{ $type === 'merch' ? 'active' : '' }}">🧥 Merchandise</a>
  </div>

  {{-- Filter --}}
  <div class="rsc-card" style="margin-bottom:18px;">
    <div class="section-title">Filter & Pencarian</div>
    <form method="GET" action="{{ route('admin.refund.transactions') }}" class="filter-grid">
      <input type="hidden" name="type" value="{{ $type }}">

      <div class="field-group">
        <label for="event_id">Pilih Event</label>
        <select id="event_id" name="event_id" class="rsc-input">
          <option value="">Semua Event</option>
          @foreach($events as $event)
            <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
              {{ $event->title }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="field-group">
        <label for="status">Status Refund</label>
        <select id="status" name="status" class="rsc-input">
          <option value="">Semua Status</option>
          <option value="waiting" {{ request('status') === 'waiting' ? 'selected' : '' }}>Waiting</option>
          <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
          <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
          <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
      </div>

      <div class="field-group">
        <label for="start_date">Tanggal Mulai</label>
        <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" class="rsc-input">
      </div>

      <div class="field-group">
        <label for="end_date">Tanggal Selesai</label>
        <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" class="rsc-input">
      </div>

      <div class="field-group">
        <label for="q">Cari Email Pembeli</label>
        <input type="text" id="q" name="q" placeholder="Cari email" value="{{ request('q') }}" class="rsc-input">
      </div>

      <div class="span-full" style="display:flex; gap:10px; padding-top:4px;">
        <button type="submit" class="btn-primary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
          </svg>
          Terapkan Filter
        </button>
        <a href="{{ route('admin.refund.transactions', ['type' => $type]) }}" class="btn-ghost">Reset</a>
      </div>
    </form>
  </div>

  {{-- Table --}}
  <div class="table-wrap">
    <div style="overflow-x:auto;">
      <table class="rsc-table">
        <thead>
          <tr>
            <th>No.</th>
            <th>Judul Acara</th>
            <th>Email Pembeli</th>
            <th>Jumlah Peserta</th>
            <th>Total Dikembalikan</th>
            <th>Biaya Admin</th>
            <th>Bank Tujuan</th>
            <th>Status</th>
            <th>Diproses Pada</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($refunds as $refund)
            @php
              $related = $type === 'ticket' ? $refund->transaction : $refund->transactionMerch;
              $attendeeCount = $type === 'ticket' ? optional($related)->attendees->count() : null;
            @endphp
            <tr>
              <td class="td-muted">{{ $loop->iteration }}</td>
              <td class="td-bold">{{ optional($related?->event)->title ?? '-' }}</td>
              <td class="td-mono">{{ $related->email ?? '-' }}</td>
              <td class="td-muted">{{ $attendeeCount !== null ? $attendeeCount . ' orang' : '-' }}</td>
              <td class="td-bold">Rp{{ number_format($refund->grand_total_refunded, 0, ',', '.') }}</td>
              <td class="td-muted">Rp{{ number_format($refund->refunds_tax, 0, ',', '.') }}</td>
              <td class="td-muted" style="font-size:.78rem;">
                {{ $refund->bank_name }}<br>
                <span class="td-mono">{{ $refund->account_number }}</span> — {{ $refund->account_name }}
              </td>
              <td>
                @php
                  $badgeClass = [
                    'refunded' => 'badge-refunded',
                    'pending'  => 'badge-pending',
                    'waiting'  => 'badge-waiting',
                    'rejected' => 'badge-rejected',
                  ][$refund->status] ?? 'badge-waiting';
                @endphp
                <span class="badge {{ $badgeClass }}">
                  <span class="badge-dot"></span>{{ ucfirst($refund->status) }}
                </span>
              </td>
              <td class="td-muted" style="font-size:.75rem; white-space:nowrap;">
                {{ $refund->processed_at ? \Carbon\Carbon::parse($refund->processed_at)->format('d M Y H:i') : '-' }}
              </td>
              <td>
                <button type="button" onclick="showRefundDetail({{ $refund->id }})" class="btn-sm btn-sm-orange">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  Detail
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="empty-cell">
                <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/>
                </svg>
                <p>Belum ada data refund yang ditemukan</p>
                <p class="small">Coba ubah filter atau reset pencarian</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>{{-- /rsc-wrap --}}

{{-- Modal Detail Refund --}}
<div id="refundDetailModal" class="rsc-modal-backdrop">
  <div class="rsc-modal">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">Detail Refund</h3>
        <p class="modal-sub" id="refundDetailEvent"></p>
      </div>
      <button type="button" onclick="closeRefundModal()" class="btn-close-modal">✕</button>
    </div>

    <div class="refund-summary-grid" id="refundSummaryGrid"></div>

    <div id="refundDetailContent"></div>
  </div>
</div>

<script>
    // Data refund dikirim lengkap dengan relasi attendee -> ticket agar bisa
    // ditampilkan tanpa request tambahan (sesuaikan bila datanya besar, pertimbangkan endpoint AJAX terpisah).
    const refundsData = @json($refunds);
    const refundType = @json($type);

    function formatRupiah(angka) {
        return 'Rp' + Number(angka || 0).toLocaleString('id-ID');
    }

    function showRefundDetail(refundId) {
        const refund = refundsData.find(r => r.id === refundId);
        if (!refund) return;

        const related = refundType === 'ticket' ? refund.transaction : refund.transaction_merch;
        const eventTitle = related?.event?.title ?? '-';

        document.getElementById('refundDetailEvent').innerText =
            `${eventTitle} — ${related?.email ?? '-'}`;

        document.getElementById('refundSummaryGrid').innerHTML = `
            <div class="rs-box">
                <div class="rs-label">Total Dikembalikan</div>
                <div class="rs-value">${formatRupiah(refund.grand_total_refunded)}</div>
            </div>
            <div class="rs-box">
                <div class="rs-label">Biaya Admin</div>
                <div class="rs-value">${formatRupiah(refund.refunds_tax)}</div>
            </div>
            <div class="rs-box">
                <div class="rs-label">Bank Tujuan</div>
                <div class="rs-value" style="font-size:.8rem;">${refund.bank_name ?? '-'} — ${refund.account_number ?? '-'}</div>
            </div>
        `;

        let html = '';

        if (refundType === 'ticket') {
            const attendees = related?.attendees ?? [];
            if (attendees.length === 0) {
                html = `<p style="color:var(--rsc-muted); font-size:.84rem; text-align:center; padding:24px 0;">Tidak ada data peserta pada transaksi ini.</p>`;
            } else {
                html = `
                <div style="overflow-x:auto;">
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th>Nama Peserta</th>
                                <th>Nomor HP</th>
                                <th>Jenis Tiket</th>
                                <th>Harga Tiket</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${attendees.map(a => `
                                <tr>
                                    <td style="font-weight:600;">${a.name ?? '-'}</td>
                                    <td style="color:var(--rsc-muted);">${a.phone_number ?? '-'}</td>
                                    <td>${a.ticket?.name ?? '-'}</td>
                                    <td style="font-weight:700;">${formatRupiah(a.ticket?.price)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>`;
            }
        } else {
            const details = related?.details ?? [];
            if (details.length === 0) {
                html = `<p style="color:var(--rsc-muted); font-size:.84rem; text-align:center; padding:24px 0;">Tidak ada rincian item merchandise pada transaksi ini.</p>`;
            } else {
                html = `
                <div style="overflow-x:auto;">
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th>Pembeli</th>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${details.map(d => `
                                <tr>
                                    <td style="font-weight:600;">${d.buyer_name ?? '-'}</td>
                                    <td>${d.product_varian?.product?.name ?? '-'} (${d.product_varian?.varian ?? '-'})</td>
                                    <td>${d.quantity}</td>
                                    <td style="font-weight:700;">${formatRupiah(d.subtotal)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>`;
            }
        }

        document.getElementById('refundDetailContent').innerHTML = html;
        document.getElementById('refundDetailModal').classList.add('open');
    }

    function closeRefundModal() {
        document.getElementById('refundDetailModal').classList.remove('open');
    }

    document.getElementById('refundDetailModal').addEventListener('click', function (e) {
        if (e.target === this) closeRefundModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeRefundModal();
    });
</script>

@endsection