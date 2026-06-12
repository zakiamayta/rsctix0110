@extends('layouts.eo')

@section('title', 'Kelola Merchandise')

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

  /* ── Alert Flash Message ── */
  .rsc-alert {
    padding: 14px 16px; border-radius: 10px; font-size: .85rem;
    margin-bottom: 20px; font-weight: 500; display: flex; align-items: center; gap: 8px;
  }
  .rsc-alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
  .rsc-alert-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FCA5A5; }

  /* ── Page header ── */
  .page-header {
    display: flex; justify-content: space-between; align-items: flex-end;
    margin-bottom: 24px;
  }
  .page-header h2 {
    font-family: 'Sora', sans-serif;
    font-size: 1.5rem; font-weight: 800;
    color: var(--rsc-text); letter-spacing: -.5px; margin: 0 0 3px;
  }
  .page-header p { color: var(--rsc-muted); font-size: .8rem; margin: 0; }
  .accent-dot {
    display: inline-block; width: 8px; height: 8px;
    border-radius: 50%; background: var(--rsc-accent);
    margin-right: 7px; vertical-align: middle;
  }

  /* ── Section title ── */
  .section-title {
    font-family: 'Sora', sans-serif;
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.5px;
    color: var(--rsc-accent); margin: 0 0 16px;
    display: flex; align-items: center; gap: 8px;
  }
  .section-title::after {
    content: ''; flex: 1; height: 1px;
    background: linear-gradient(to right, var(--rsc-border), transparent);
  }

  /* ── Buttons ── */
  .btn-primary {
    display: inline-flex; align-items: center; gap: 7px;
    background: var(--rsc-accent); color: #fff;
    border: none; border-radius: 10px;
    padding: 10px 20px; font-size: .82rem; font-weight: 700;
    cursor: pointer; font-family: 'Sora', sans-serif;
    transition: opacity .15s; text-decoration: none;
  }
  .btn-primary:hover { opacity: .86; color: #fff; }

  .btn-ghost {
    display: inline-flex; align-items: center; gap: 7px;
    background: var(--rsc-surface2); color: var(--rsc-muted);
    border: 1px solid var(--rsc-border); border-radius: 10px;
    padding: 10px 18px; font-size: .82rem; font-weight: 700;
    cursor: pointer; font-family: 'Sora', sans-serif;
    transition: border-color .15s, color .15s; text-decoration: none;
  }
  .btn-ghost:hover { border-color: #B5AEA8; color: var(--rsc-text); }

  .btn-sm {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .72rem; font-weight: 700;
    padding: 6px 13px; border-radius: 8px;
    border: none; cursor: pointer;
    font-family: 'Sora', sans-serif;
    transition: opacity .15s; text-decoration: none;
  }
  .btn-sm-dark   { background: var(--rsc-text); color: #fff; }
  .btn-sm-dark:hover { opacity: .8; color: #fff; }
  .btn-sm-danger { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
  .btn-sm-danger:hover { background: #FEE2E2; }
  .btn-sm-accent { background: var(--rsc-accent-dim); color: var(--rsc-accent); border: 1px solid rgba(232,71,10,.2); }
  .btn-sm-accent:hover { background: rgba(232,71,10,.14); }

  /* ── Filter card ── */
  .rsc-card {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    padding: 20px 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
  }

  .filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 12px; align-items: end;
  }

  .field-group { display: flex; flex-direction: column; gap: 5px; }
  .field-group label {
    font-size: .72rem; font-weight: 700;
    color: var(--rsc-muted); text-transform: uppercase; letter-spacing: .6px;
  }

  .rsc-input {
    background: var(--rsc-surface2);
    border: 1px solid var(--rsc-border);
    border-radius: 9px; color: var(--rsc-text);
    padding: 9px 12px; font-size: .85rem; width: 100%;
    outline: none; font-family: 'DM Sans', sans-serif;
    transition: border-color .18s, box-shadow .18s;
  }
  .rsc-input::placeholder { color: #C4BBB3; }
  .rsc-input:focus {
    border-color: var(--rsc-accent);
    box-shadow: 0 0 0 3px var(--rsc-accent-dim);
    background: #fff;
  }
  select.rsc-input { cursor: pointer; }
  textarea.rsc-input { resize: vertical; min-height: 82px; }

  /* ── Product grid ── */
  .product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
    gap: 16px; margin-top: 20px;
  }

  /* ── Product card ── */
  .product-card {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius); overflow: hidden;
    cursor: pointer;
    transition: border-color .2s, transform .2s, box-shadow .2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  }
  .product-card:hover {
    border-color: #C8BFB8;
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
  }

  .product-poster {
    width: 100%; height: 160px; overflow: hidden;
    background: var(--rsc-surface2); position: relative;
  }
  .product-poster img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform .3s;
  }
  .product-card:hover .product-poster img { transform: scale(1.04); }
  .poster-placeholder {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
  }

  .product-body { padding: 14px; }

  .event-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--rsc-accent-dim);
    border: 1px solid rgba(232,71,10,.18);
    color: var(--rsc-accent);
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .8px;
    padding: 3px 9px; border-radius: 20px;
    margin-bottom: 8px;
  }

  .product-title {
    font-family: 'Sora', sans-serif;
    font-size: .9rem; font-weight: 800;
    color: var(--rsc-text); margin-bottom: 5px;
    line-height: 1.3;
  }

  .product-desc {
    font-size: .75rem; color: var(--rsc-muted);
    margin-bottom: 10px; line-height: 1.5;
    display: -webkit-box; -webkit-line-clamp: 2;
    -webkit-box-orient: vertical; overflow: hidden;
  }

  .variant-chips { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px; }
  .chip {
    background: var(--rsc-surface2);
    border: 1px solid var(--rsc-border);
    color: var(--rsc-muted);
    font-size: .68rem; font-weight: 600;
    padding: 3px 9px; border-radius: 20px;
  }

  .product-actions {
    display: flex; gap: 8px;
    padding: 12px 14px;
    border-top: 1px solid var(--rsc-border);
    background: #FDFAF8;
  }
  .product-actions form { flex: 1; display: flex; }
  .product-actions .btn-sm { flex: 1; justify-content: center; }

  /* ── Empty state ── */
  .empty-state {
    grid-column: 1 / -1; text-align: center;
    padding: 56px 0; color: var(--rsc-muted);
  }
  .empty-state svg { opacity: .18; margin: 0 auto 12px; display: block; }
  .empty-state p { font-size: .88rem; font-weight: 600; margin: 0 0 4px; }
  .empty-state small { font-size: .78rem; }

  /* ── Modals Base ── */
  .rsc-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(26,18,8,.5);
    backdrop-filter: blur(4px);
    z-index: 1050;
    display: none; align-items: center; justify-content: center;
    padding: 20px;
  }
  .rsc-modal-backdrop.open { display: flex; }

  .rsc-modal {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    box-shadow: 0 28px 64px rgba(0,0,0,0.14);
    width: 100%; max-width: 780px;
    max-height: 90vh; overflow-y: auto;
    animation: fadeSlide .22s ease;
  }
  .rsc-modal.modal-sm { max-width: 440px; }
  .rsc-modal.modal-md { max-width: 560px; }

  @keyframes fadeSlide {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .modal-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 20px 24px 16px;
    border-bottom: 1px solid var(--rsc-border);
    position: sticky; top: 0; background: var(--rsc-surface); z-index: 2;
  }
  .modal-title {
    font-family: 'Sora', sans-serif;
    font-size: 1rem; font-weight: 800; color: var(--rsc-text); margin: 0;
  }
  .btn-close-modal {
    background: var(--rsc-surface2); border: 1px solid var(--rsc-border);
    border-radius: 8px; width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: .9rem; color: var(--rsc-muted);
    transition: background .15s;
  }
  .btn-close-modal:hover { background: #EDE8E3; color: var(--rsc-text); }

  .modal-body { padding: 22px 24px; }
  .modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--rsc-border);
    background: var(--rsc-surface2);
    display: flex; justify-content: flex-end; gap: 10px;
  }

  .field-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .span2 { grid-column: span 2; }

  .variant-card {
    background: var(--rsc-surface2);
    border: 1px solid var(--rsc-border);
    border-radius: 12px; padding: 16px;
    margin-bottom: 12px;
    animation: fadeSlide .18s ease;
  }
  .variant-card-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 14px;
  }
  .variant-label {
    font-family: 'Sora', sans-serif;
    font-size: .7rem; font-weight: 700;
    color: var(--rsc-accent); text-transform: uppercase; letter-spacing: 1px;
  }

  .size-box {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: 10px; padding: 14px;
    margin-top: 12px;
  }
  .size-box-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 12px;
  }
  .size-box-label {
    font-size: .68rem; font-weight: 700;
    color: var(--rsc-muted); text-transform: uppercase; letter-spacing: .8px;
  }

  .size-row {
    display: grid; grid-template-columns: 1fr 1fr 1fr auto;
    gap: 8px; margin-bottom: 8px; align-items: end;
  }

  .file-zone {
    border: 1.5px dashed var(--rsc-border);
    border-radius: 9px; padding: 14px 12px;
    background: var(--rsc-surface); cursor: pointer;
    text-align: center; position: relative;
  }
  .file-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; }
  .file-zone-text { font-size: .75rem; color: var(--rsc-muted); }
  .file-zone-text span { color: var(--rsc-accent); font-weight: 700; }

  #detailImage {
    width: 100%; max-height: 280px; object-fit: cover;
    border-radius: 10px; margin-bottom: 14px;
    border: 1px solid var(--rsc-border);
  }

  .detail-variant-card {
    background: var(--rsc-surface2);
    border: 1px solid var(--rsc-border);
    border-radius: 10px; padding: 14px; margin-bottom: 10px;
  }
  .detail-variant-title {
    font-family: 'Sora', sans-serif;
    font-size: .78rem; font-weight: 800;
    color: var(--rsc-text); margin-bottom: 10px;
  }
  .detail-size-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 7px 0; border-bottom: 1px solid var(--rsc-border);
    font-size: .8rem;
  }
  .detail-size-row:last-child { border-bottom: none; }

  .delete-confirm-body { text-align: center; padding: 28px 24px; }
  .delete-icon {
    width: 52px; height: 52px; border-radius: 50%;
    background: #FEF2F2; display: flex; align-items: center; justify-content: center;
    margin: 0 auto 14px;
  }
  .delete-confirm-title { font-family: 'Sora', sans-serif; font-size: 1rem; font-weight: 800; margin-bottom: 6px; }
  .delete-confirm-sub { font-size: .82rem; color: var(--rsc-muted); }
  .delete-confirm-footer { display: flex; gap: 10px; padding: 16px 24px; border-top: 1px solid var(--rsc-border); background: var(--rsc-surface2); }

  @media (max-width: 640px) {
    .filter-grid { grid-template-columns: 1fr; }
    .field-grid-2 { grid-template-columns: 1fr; }
    .span2 { grid-column: 1; }
    .size-row { grid-template-columns: 1fr 1fr; }
    .product-grid { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 420px) {
    .product-grid { grid-template-columns: 1fr; }
  }

  #variant-wrapper { display: flex; flex-direction: column; gap: 0; }
</style>

<div class="rsc-wrap">

  {{-- Flash Messages Alert --}}

  @if(session('error'))
    <div class="rsc-alert rsc-alert-error">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ session('error') }}
    </div>
  @endif

  {{-- Header --}}
  <div class="page-header">
    <div>
      <h2><span class="accent-dot"></span>Kelola Merchandise</h2>
      <p>Tambah dan kelola produk merch untuk event kamu</p>
    </div>
    <button type="button" class="btn-primary" onclick="openModal('createMerchModal')">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Tambah Merch
    </button>
  </div>

  {{-- Filter --}}
  <div class="rsc-card" style="margin-bottom:20px;">
    <div class="section-title">Filter</div>
    <form method="GET">
      <div class="filter-grid">
        <div class="field-group">
          <label>Event</label>
          <select name="event_id" class="rsc-input">
            <option value="">Semua Event</option>
            @foreach($events as $event)
              <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                {{ $event->title }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="field-group">
          <label>Cari Produk</label>
          <input type="text" name="search" class="rsc-input" placeholder="Nama merchandise…" value="{{ request('search') }}">
        </div>
        <button type="submit" class="btn-primary" style="height:40px; align-self:end;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
          </svg>
          Filter
        </button>
      </div>
    </form>
  </div>

  {{-- Product grid --}}
  <div class="product-grid">

    @forelse($products as $product)
    @php $firstVarian = $product->varians->first(); @endphp

    {{-- Penyimpanan objek aman menggunakan data attribute HTML5 --}}
    <div class="product-card" data-product="{{ json_encode($product) }}" onclick="showDetail(this)">

      <div class="product-poster">
        @if($firstVarian && $firstVarian->images->first())
          <img src="{{ asset($firstVarian->images->first()->url) }}" alt="{{ $product->name }}">
        @else
          <div class="poster-placeholder">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#E8470A" stroke-width="1.2" opacity=".25">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg>
          </div>
        @endif
      </div>

      <div class="product-body">
        <div class="event-badge">
          <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <rect x="3" y="4" width="18" height="18" rx="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
          {{ $product->event->title ?? '-' }}
        </div>
        <div class="product-title">{{ $product->name }}</div>
        <div class="product-desc">{{ $product->description }}</div>
        <div class="variant-chips">
          @foreach($product->varians as $varian)
            <span class="chip">{{ $varian->varian }}</span>
          @endforeach
        </div>
      </div>

      <div class="product-actions" onclick="event.stopPropagation()">
        <a href="{{ route('eo.merch.edit', $product->id) }}" class="btn-sm btn-sm-dark">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit
        </a>
        <button class="btn-sm btn-sm-danger" onclick="openDeleteModal({{ $product->id }})">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
          </svg>
          Hapus
        </button>
      </div>

    </div>
    @empty
    <div class="empty-state">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#E8470A" stroke-width="1">
        <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
      </svg>
      <p>Belum ada merchandise</p>
      <small>Mulai dengan menambahkan produk pertama</small>
    </div>
    @endforelse

  </div>
</div>

{{-- ═══════════════════════════════
     MODAL: CREATE
═══════════════════════════════ --}}
<div id="createMerchModal" class="rsc-modal-backdrop" onclick="backdropClose(event, 'createMerchModal')">
  <div class="rsc-modal">
    <form action="{{ route('eo.merch.store') }}" method="POST" enctype="multipart/form-data">
      @csrf

      <div class="modal-header">
        <h3 class="modal-title">Tambah Merchandise</h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('createMerchModal')">✕</button>
      </div>

      <div class="modal-body">
        <div class="section-title">Info Produk</div>
        <div class="field-grid-2" style="margin-bottom:14px;">
          <div class="field-group">
            <label>Event</label>
            <select name="event_id" class="rsc-input" required>
              <option value="">Pilih Event</option>
              @foreach($events as $event)
                <option value="{{ $event->id }}">{{ $event->title }}</option>
              @endforeach
            </select>
          </div>
          <div class="field-group">
            <label>Nama Produk</label>
            <input type="text" name="name" class="rsc-input" placeholder="Nama merchandise" required>
          </div>
          <div class="field-group span2">
            <label>Deskripsi</label>
            <textarea name="description" class="rsc-input" placeholder="Deskripsi singkat produk…"></textarea>
          </div>
        </div>

        <div class="section-title" style="margin-top:8px;">Varian Produk</div>
        <div id="variant-wrapper"></div>

        <button type="button" class="btn-sm btn-sm-accent" onclick="addVariant()" style="margin-top:4px;">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Tambah Varian
        </button>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="closeModal('createMerchModal')">Batal</button>
        <button type="submit" class="btn-primary">Simpan Merchandise</button>
      </div>
    </form>
  </div>
</div>

{{-- ═══════════════════════════════
     MODAL: DETAIL
═══════════════════════════════ --}}
<div id="detailModal" class="rsc-modal-backdrop" onclick="backdropClose(event, 'detailModal')">
  <div class="rsc-modal modal-md">
    <div class="modal-header">
      <h3 class="modal-title" id="detailTitle">—</h3>
      <button type="button" class="btn-close-modal" onclick="closeModal('detailModal')">✕</button>
    </div>
    <div class="modal-body">
      <div style="display:flex; align-items:center; gap:7px; margin-bottom:12px;">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#8A7E76" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <span id="detailEvent" style="font-size:.75rem; color:#8A7E76;"></span>
      </div>
      <img id="detailImage" alt="Foto produk">
      <p id="detailDesc" style="font-size:.84rem; color:#8A7E76; margin-bottom:16px; line-height:1.6;"></p>
      <div class="section-title">Varian</div>
      <div id="detailVariant"></div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════
     MODAL: DELETE
═══════════════════════════════ --}}
<div id="deleteMerchModal" class="rsc-modal-backdrop" onclick="backdropClose(event, 'deleteMerchModal')">
  <div class="rsc-modal modal-sm">
    <form id="deleteForm" method="POST">
      @csrf
      @method('DELETE')
      <div class="delete-confirm-body">
        <div class="delete-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#B91C1C" stroke-width="2.2">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
          </svg>
        </div>
        <div class="delete-confirm-title">Hapus Merchandise?</div>
        <div class="delete-confirm-sub">Tindakan ini tidak bisa dibatalkan.</div>
      </div>
      <div class="delete-confirm-footer">
        <button type="button" class="btn-ghost" style="flex:1; justify-content:center;" onclick="closeModal('deleteMerchModal')">Batal</button>
        <button type="submit" class="btn-primary" style="flex:1; justify-content:center; background:#B91C1C;">Hapus</button>
      </div>
    </form>
  </div>
</div>

<script>
/* ── Modal helpers ── */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function backdropClose(e, id) { if (e.target === e.currentTarget) closeModal(id); }

/* ── Variant builder ── */
let variantIndex = 0;

function addVariant() {
  const wrapper = document.getElementById('variant-wrapper');
  const vIdx = variantIndex;

  const html = `
  <div class="variant-card">
    <div class="variant-card-header">
      <span class="variant-label">Varian #${vIdx + 1}</span>
      <button type="button" class="btn-sm btn-sm-danger" onclick="this.closest('.variant-card').remove()">✕ Hapus</button>
    </div>
    <div class="field-grid-2" style="margin-bottom:12px;">
      <div class="field-group">
        <label>Nama Varian</label>
        <input type="text" name="varians[${vIdx}][varian]" class="rsc-input" placeholder="Misal: Hitam, Putih" required>
      </div>
      <div class="field-group">
        <label>Gambar Varian</label>
        <div class="file-zone">
          <input type="file" name="varians[${vIdx}][image]" accept="image/*" required>
          <div class="file-zone-text"><span>Pilih gambar</span></div>
        </div>
      </div>
    </div>
    <div class="size-box">
      <div class="size-box-header">
        <span class="size-box-label">Ukuran & Harga</span>
        <button type="button" class="btn-sm btn-sm-accent" onclick="addUkuran(${vIdx})">+ Ukuran</button>
      </div>
      <div id="ukuran-wrapper-${vIdx}"></div>
    </div>
  </div>`;

  wrapper.insertAdjacentHTML('beforeend', html);
  addUkuran(vIdx);
  variantIndex++;
}

function addUkuran(vIdx) {
  const wrapper = document.getElementById(`ukuran-wrapper-${vIdx}`);
  const uid = Date.now() + Math.floor(Math.random() * 100);

  const html = `
  <div class="size-row">
    <div class="field-group">
      <label>Ukuran</label>
      <input type="text" name="varians[${vIdx}][ukurans][${uid}][ukuran]" class="rsc-input" placeholder="S / M / L" required>
    </div>
    <div class="field-group">
      <label>Harga</label>
      <input type="number" name="varians[${vIdx}][ukurans][${uid}][harga]" class="rsc-input" placeholder="0" required>
    </div>
    <div class="field-group">
      <label>Stok</label>
      <input type="number" name="varians[${vIdx}][ukurans][${uid}][stok]" class="rsc-input" placeholder="0" required>
    </div>
    <button type="button" class="btn-sm btn-sm-danger" style="margin-top:22px; height:38px;" onclick="this.closest('.size-row').remove()">✕</button>
  </div>`;

  wrapper.insertAdjacentHTML('beforeend', html);
}

document.addEventListener('DOMContentLoaded', () => addVariant());

/* ── Detail modal ── */
function showDetail(element) {
  // Parsing JSON super aman dari attribute element tanpa merusak kutip tunggal javascript
  const product = JSON.parse(element.getAttribute('data-product'));

  document.getElementById('detailTitle').innerText = product.name ?? '-';
  document.getElementById('detailDesc').innerText  = product.description ?? '-';
  document.getElementById('detailEvent').innerText = product.event?.title ?? '-';

  let imgSrc = 'https://placehold.co/560x280?text=No+Image';
  if (product.varians?.length) {
    const v = product.varians.find(v => v.images?.length > 0);
    if (v) imgSrc = '/' + v.images[0].url;
  }
  document.getElementById('detailImage').src = imgSrc;

  let html = '';
  if (product.varians?.length) {
    product.varians.forEach(v => {
      html += `<div class="detail-variant-card">
        <div class="detail-variant-title">${v.varian ?? '-'}</div>`;
      if (v.ukurans?.length) {
        v.ukurans.forEach(u => {
          html += `<div class="detail-size-row">
            <span style="font-weight:600;">${u.ukuran ?? '-'}</span>
            <span style="color:#8A7E76;">Rp ${Number(u.harga||0).toLocaleString('id-ID')}</span>
            <span style="color:#8A7E76;">Stok: ${u.stok ?? 0}</span>
          </div>`;
        });
      } else {
        html += `<div style="font-size:.78rem; color:#8A7E76;">Tidak ada ukuran</div>`;
      }
      html += `</div>`;
    });
  } else {
    html = `<p style="font-size:.82rem; color:#8A7E76;">Tidak ada varian</p>`;
  }
  document.getElementById('detailVariant').innerHTML = html;
  openModal('detailModal');
}

/* ── Delete modal ── */
function openDeleteModal(id) {
  document.getElementById('deleteForm').action = `/eo/merch/${id}`;
  openModal('deleteMerchModal');
}
</script>

@endsection