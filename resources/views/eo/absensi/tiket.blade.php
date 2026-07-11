@extends('layouts.eo')

@push('styles')
<link href="{{ asset('css/admin_dashboard.css') }}" rel="stylesheet">
@endpush

@section('title', 'Pantauan Absensi Pengunjung')

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
  .stat-icon-green { background: rgba(26,122,68,.1); }
  .stat-icon-red   { background: rgba(185,28,28,.08); }

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
  .btn-primary:hover { opacity: .85; color: #fff; }

  .btn-ghost {
    background: var(--rsc-surface2); color: var(--rsc-muted);
    border: 1px solid var(--rsc-border); border-radius: 9px;
    padding: 9px 20px; font-size: .82rem; font-weight: 700;
    cursor: pointer; font-family: 'Sora', sans-serif;
    transition: border-color .15s, color .15s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-ghost:hover { border-color: #B5AEA8; color: var(--rsc-text); }

  .btn-outline-qr {
    background: var(--rsc-accent-dim); color: var(--rsc-accent);
    border: 1px solid transparent; border-radius: 7px;
    padding: 5px 14px; font-size: .72rem; font-weight: 700;
    font-family: 'Sora', sans-serif;
    text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
    transition: background .15s;
  }
  .btn-outline-qr:hover { background: rgba(232,71,10,.15); color: var(--rsc-accent); }

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
  .rsc-table th.text-center, .rsc-table td.text-center { text-align: center; }
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

  /* ── Status badges ── */
  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
    padding: 4px 10px; border-radius: 20px;
  }
  .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
  .badge-paid   { background: #E8F5EE; color: #1A7A44; }
  .badge-unpaid { background: #FEF2F2; color: #B91C1C; }

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

  /* ── Bootstrap modal restyle (structure/behaviour unchanged) ── */
  #detailModal .modal-content {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    box-shadow: 0 24px 60px rgba(0,0,0,0.14);
  }
  #detailModal .modal-header,
  #detailModal .modal-footer {
    border-color: var(--rsc-border);
  }
  #detailModal .modal-title {
    font-family: 'Sora', sans-serif;
    font-weight: 800;
    color: var(--rsc-text);
  }
  #detailModal .modal-body table td {
    font-family: 'DM Sans', sans-serif;
    font-size: .84rem;
    padding: 8px 4px;
  }
  #detailModal .btn-primary {
    background: var(--rsc-accent);
    border-color: var(--rsc-accent);
  }
  #detailModal .btn-primary:hover { opacity: .85; background: var(--rsc-accent); }
  #detailModal code { color: var(--rsc-accent); }

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
        <h2><span class="accent-dot"></span>Pantauan Absensi Pengunjung</h2>
        <p>Monitoring kehadiran pengunjung pada event Anda</p>
    </div>

    {{-- 🔹 Ringkasan Absen --}}
    @php
        $totalSudah = $attendees->filter(fn($a) => $a->transaction?->is_registered)->count();
        $totalBelum = $attendees->filter(fn($a) => !$a->transaction?->is_registered)->count();
    @endphp

    <div class="stats-row-2">
        <div class="stat-card">
            <div>
                <div class="stat-label">
                    <span class="stat-dot" style="background:#1A7A44;"></span>
                    Sudah Absen
                </div>
                <div class="stat-value">{{ $totalSudah }}</div>
                <div class="stat-sub">Pengunjung telah hadir</div>
            </div>
            <div class="stat-icon stat-icon-green">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#1A7A44" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-label">
                    <span class="stat-dot" style="background:#B91C1C;"></span>
                    Belum Absen
                </div>
                <div class="stat-value">{{ $totalBelum }}</div>
                <div class="stat-sub">Menunggu kehadiran</div>
            </div>
            <div class="stat-icon stat-icon-red">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#B91C1C" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- 🔹 Form Filter & Sort --}}
    <div class="rsc-card" style="margin-bottom:18px;">
        <div class="section-title">Filter Absensi</div>

        <form method="GET" action="{{ route('eo.absensi.tiket') }}" class="filter-grid">
            {{-- Pilih Event --}}
            <div class="field-group span-2">
                <label for="event_id">Pilih Event</label>
                <select id="event_id" name="event_id" class="rsc-input">
                    <option value="">-- Semua Event --</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                            {{ $event->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Pencarian --}}
            <div class="field-group span-2">
                <label for="search">Pencarian</label>
                <input type="text" id="search" name="search" value="{{ $search ?? '' }}"
                    placeholder="Cari nama, email, atau no. telp" class="rsc-input">
            </div>

            {{-- Status --}}
            <div class="field-group span-2">
                <label for="status">Status Absen</label>
                <select id="status" name="status" class="rsc-input">
                    <option value="">Semua Status</option>
                    <option value="sudah" {{ ($status ?? '') === 'sudah' ? 'selected' : '' }}>Sudah Absen</option>
                    <option value="belum" {{ ($status ?? '') === 'belum' ? 'selected' : '' }}>Belum Absen</option>
                </select>
            </div>

            {{-- Tombol Aksi --}}
            <div class="span-full" style="display:flex; gap:10px; padding-top:4px;">
                <button type="submit" class="btn-primary">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('eo.absensi.tiket') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    {{-- 🔹 Tabel Absensi --}}
    <div class="table-wrap">
        <div style="overflow-x:auto;">
            <table class="rsc-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 70px;">No</th>
                        <th>Nama Event</th>
                        <th>Email</th>
                        <th>Nama Pengunjung</th>
                        <th>Status Absen</th>
                        <th class="text-center">QR Code</th>
                        <th class="text-center" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendees as $index => $attendee)
                        <tr>
                            <td class="text-center td-muted">
                                {{ ($attendees->currentPage() - 1) * $attendees->perPage() + $index + 1 }}
                            </td>
                            <td>{{ $attendee->transaction?->event?->title ?? '-' }}</td>
                            <td class="td-muted">{{ $attendee->transaction?->email ?? '-' }}</td>
                            <td class="td-bold">{{ $attendee->name ?? '-' }}</td>
                            <td>
                                @if ($attendee->transaction?->is_registered)
                                    <span class="badge badge-paid"><span class="badge-dot"></span>Sudah Absen</span>
                                @else
                                    <span class="badge badge-unpaid"><span class="badge-dot"></span>Belum Absen</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($attendee->transaction?->qr_code)
                                    <a href="{{ route('absen.form', $attendee->transaction->kode_unik) }}" target="_blank"
                                        class="btn-outline-qr">
                                        QR
                                    </a>
                                @else
                                    <span class="td-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; justify-content:center; gap:8px;">
                                    @if ($attendee->transaction)
                                        {{-- Detail Pembeli --}}
                                        <button onclick="showDetail({{ $attendee->id }})"
                                            class="btn-sm btn-sm-indigo" title="Detail Pembeli">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Tombol Absen/Batalkan --}}
                                    @if (!$attendee->transaction?->is_registered)
                                        <form method="POST" action="{{ route('eo.absensi.manual', ['id' => $attendee->id]) }}" class="m-0">
                                            @csrf
                                            <button type="submit"
                                                class="btn-sm btn-sm-green" title="Tandai Sudah Absen">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('eo.absensi.batal', ['id' => $attendee->id]) }}" class="m-0">
                                            @csrf
                                            <button type="submit"
                                                class="btn-sm btn-sm-red" title="Batalkan Absen">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                            <td colspan="7" class="empty-cell">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="1">
                                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                <p>Tidak ada data peserta.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($attendees->hasPages())
            <div class="table-footer">
                {{ $attendees->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

{{-- 🔹 Modal Detail (Bootstrap 5 native format, dipertahankan) --}}
<div class="modal fade" id="detailModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="detailModalLabel">Detail Pembeli Tiket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div id="modalContent" class="text-sm"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary w-100 fw-semibold py-2 rounded-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    const attendeeData = @json($attendees->items());

    function escapeHtml(str) {
        if (!str) return '-';
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }

    function showDetail(attendeeId) {
        const attendee = attendeeData.find(a => a.id === attendeeId);
        const modalContent = document.getElementById('modalContent');

        if (!attendee) {
            modalContent.innerHTML = '<p class="text-muted text-center my-3">Data peserta tidak ditemukan.</p>';
        } else {
            const t = attendee.transaction ?? {};
            modalContent.innerHTML = `
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="fw-semibold text-secondary py-1" style="width: 35%;">Email:</td><td class="text-dark py-1">${escapeHtml(t.email)}</td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">Waktu Checkout:</td><td class="text-dark py-1">${t.checkout_time ? new Date(t.checkout_time).toLocaleString('id-ID') : '-'}</td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">Status Bayar:</td><td class="text-dark py-1"><span class="badge bg-secondary bg-opacity-10 text-secondary">${escapeHtml(t.payment_status)}</span></td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">Nama Peserta:</td><td class="text-dark py-1">${escapeHtml(attendee.name)}</td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">No HP:</td><td class="text-dark py-1">${escapeHtml(attendee.phone_number)}</td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">ID Tiket:</td><td class="text-dark py-1"><code class="text-primary">${escapeHtml(attendee.ticket_id)}</code></td></tr>
                    </table>
                </div>
            `;
        }

        // Memanggil modal bawaan Bootstrap 5
        const myModal = new bootstrap.Modal(document.getElementById('detailModal'));
        myModal.show();
    }
</script>
@endsection