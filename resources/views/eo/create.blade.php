@extends('layouts.eo')

@section('title', 'Ajukan Event')

@section('content')

<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500&display=swap');

:root {
  --rsc-bg: #F9F6F2;
  --rsc-card: #FFFFFF;
  --rsc-soft: #FFF0EB;
  --rsc-border: #EDE8E3;
  --rsc-accent: #E8470A;
  --rsc-accent-soft: rgba(232,71,10,0.08);
  --rsc-text: #1A1208;
  --rsc-muted: #7A6E66;
  --rsc-danger: #9C2222;
  --rsc-danger-bg: #FDECEC;
  --radius: 14px;
}

/* Reset */
.rsc-wrap * {
  font-family: 'DM Sans', sans-serif;
  box-sizing: border-box;
}

/* Wrapper */
.rsc-wrap {
  background: var(--rsc-bg);
  min-height: 100vh;
  padding: 28px 24px 60px;
  color: var(--rsc-text);
}

/* Header */
.page-header {
  margin-bottom: 24px;
}
.page-header h2 {
  font-family: 'Sora', sans-serif;
  font-size: 1.4rem;
  font-weight: 800;
  margin: 0 0 4px;
  color: var(--rsc-text);
}
.page-header p {
  color: var(--rsc-muted);
  font-size: .82rem;
}
.accent-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--rsc-accent);
  display: inline-block;
  margin-right: 8px;
}

/* Error */
.err-box {
  background: var(--rsc-danger-bg);
  border: 1px solid #F5C2C2;
  color: var(--rsc-danger);
  padding: 14px 16px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: .82rem;
}

/* Card Form */
.rsc-form {
  background: var(--rsc-card);
  border: 1px solid var(--rsc-border);
  border-radius: var(--radius);
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 28px;
}

/* Section */
.section-title {
  font-family: 'Sora', sans-serif;
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  color: var(--rsc-accent);
  margin-bottom: 14px;
}

/* Grid */
.field-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.span2 {
  grid-column: span 2;
}

/* Field */
.field-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.field-group label {
  font-size: .72rem;
  font-weight: 600;
  color: var(--rsc-muted);
  text-transform: uppercase;
}

/* Input */
.rsc-input {
  background: #fff;
  border: 1px solid var(--rsc-border);
  border-radius: 8px;
  color: var(--rsc-text);
  padding: 10px 12px;
  font-size: .85rem;
  outline: none;
  transition: all .15s;
}
.rsc-input::placeholder {
  color: #B0A7A0;
}
.rsc-input:focus {
  border-color: var(--rsc-accent);
  box-shadow: 0 0 0 2px var(--rsc-accent-soft);
}
textarea.rsc-input {
  resize: vertical;
  min-height: 90px;
}

/* File Upload */
.file-zone {
  border: 1.5px dashed var(--rsc-border);
  border-radius: 10px;
  padding: 20px;
  text-align: center;
  cursor: pointer;
  background: #fff;
  transition: .2s;
}
.file-zone:hover {
  border-color: var(--rsc-accent);
  background: var(--rsc-soft);
}
.file-zone input {
  display: none;
}
.file-zone-icon {
  font-size: 1.5rem;
}
.file-zone-text {
  font-size: .78rem;
  color: var(--rsc-muted);
}
.file-zone-text span {
  color: var(--rsc-accent);
  font-weight: 600;
}
#posterPreview {
  display: none;
  margin-top: 12px;
  width: 120px;
  border-radius: 8px;
  border: 1px solid var(--rsc-border);
}

/* Jadwal */
.jadwal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}

.btn-add-jadwal {
  background: var(--rsc-accent);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 16px;
  font-size: .78rem;
  font-weight: 700;
  cursor: pointer;
}

/* Jadwal Card */
.jadwal-item {
  background: #fff;
  border: 1px solid var(--rsc-border);
  border-radius: 12px;
  padding: 16px;
}

.jadwal-label {
  font-family: 'Sora', sans-serif;
  font-size: .72rem;
  font-weight: 700;
  color: var(--rsc-accent);
}

/* Remove */
.btn-remove {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--rsc-muted);
  font-size: .75rem;
}
.btn-remove:hover {
  color: var(--rsc-danger);
}

/* Ticket */
.ticket-box {
  background: var(--rsc-bg);
  border: 1px solid var(--rsc-border);
  border-radius: 10px;
  padding: 14px;
  margin-top: 12px;
}

.ticket-box-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.ticket-box-label {
  font-size: .7rem;
  font-weight: 700;
  color: var(--rsc-muted);
}

.btn-add-ticket {
  border: 1px solid var(--rsc-accent);
  color: var(--rsc-accent);
  background: transparent;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: .75rem;
  cursor: pointer;
}
.btn-add-ticket:hover {
  background: var(--rsc-accent-soft);
}

/* Ticket Item */
.ticket-item {
  background: #fff;
  border: 1px solid var(--rsc-border);
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 10px;
}

.ticket-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr auto;
  gap: 8px;
  margin-bottom: 8px;
}
.ticket-grid2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.btn-remove-ticket {
  background: #FDECEC;
  border: none;
  color: var(--rsc-danger);
  border-radius: 6px;
  width: 34px;
  cursor: pointer;
}
.btn-remove-ticket:hover {
  opacity: .8;
}

/* Submit */
.submit-row {
  display: flex;
  justify-content: flex-end;
}
.btn-submit {
  background: var(--rsc-accent);
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 12px 28px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
}
.btn-submit:hover {
  opacity: .9;
}

/* Responsive */
@media (max-width: 640px) {
  .field-grid {
    grid-template-columns: 1fr;
  }
  .span2 {
    grid-column: span 1;
  }
  .ticket-grid {
    grid-template-columns: 1fr 1fr;
  }
  .ticket-grid2 {
    grid-template-columns: 1fr;
  }
}

/* Wrapper jadwal */
#jadwal-wrapper {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
</style>

<div class="rsc-wrap">

  {{-- Header --}}
  <div class="page-header">
    <h2><span class="accent-dot"></span>Ajukan Event</h2>
    <p>Buat event beserta jadwal dan tiket</p>
  </div>

  {{-- Errors --}}
  @if ($errors->any())
  <div class="err-box">
    @foreach ($errors->all() as $error)
      <div>• {{ $error }}</div>
    @endforeach
  </div>
  @endif

  {{-- Form --}}
  <form action="{{ route('eo.event.store') }}"
        method="POST"
        enctype="multipart/form-data"
        class="rsc-form">
  @csrf

    {{-- ══ Informasi Event ══ --}}
    <div>
      <div class="section-title">Informasi Event</div>

      <div class="field-grid">

        <div class="field-group">
          <label>Judul Event</label>
          <input type="text" name="title" class="rsc-input" placeholder="Nama event kamu" required>
        </div>

        <div class="field-group">
          <label>Tanggal Event</label>
          <input type="datetime-local" name="date" class="rsc-input" required>
        </div>

        <div class="field-group">
          <label>Instagram</label>
          <input type="text" name="instagram" class="rsc-input" placeholder="@username">
        </div>

        <div class="field-group">
          <label>Lineup</label>
          <input type="text" name="lineup" class="rsc-input" placeholder="Nama artis / performer">
        </div>

        <div class="field-group">
          <label>Minimal Umur</label>
          <input type="number" name="min_age" class="rsc-input" placeholder="Contoh: 17">
        </div>

        <div class="field-group">
          <label>Maks Tiket / Email</label>
          <input type="number" name="max_tickets_per_email" value="3" class="rsc-input">
        </div>

        <div class="field-group">
          <label>Mulai Penjualan Tiket</label>
          <input type="datetime-local" name="ticket_sale_start" class="rsc-input">
        </div>

        <div class="field-group">
          <label>Mulai Redeem Tiket</label>
          <input type="datetime-local" name="ticket_redeem_start" class="rsc-input">
        </div>

        <div class="field-group span2">
          <label>Lokasi</label>
          <input type="text" name="location" class="rsc-input" placeholder="Venue lengkap" required>
        </div>

        <div class="field-group span2">
          <label>Deskripsi</label>
          <textarea name="description" class="rsc-input" placeholder="Ceritakan event kamu…"></textarea>
        </div>

      <div class="field-group span2">
        <label>Poster Event</label>

        <label for="posterInput" class="file-zone">

          <input type="file"
                id="posterInput"
                name="poster"
                accept="image/*"
                onchange="previewPoster(event)">

          <div class="file-zone-icon">🖼️</div>

          <div class="file-zone-text">
            <span>Pilih file</span> atau seret ke sini<br>
            JPG, PNG, WEBP — maks 5 MB
          </div>

        </label>

        <img id="posterPreview" alt="Preview poster">

      </div>

      </div>
    </div>

    {{-- ══ Jadwal ══ --}}
    <div>
      <div class="jadwal-header">
        <div class="section-title" style="margin:0; flex:1;">Jadwal Event</div>
        <button type="button" class="btn-add-jadwal" onclick="addJadwal()">+ Jadwal</button>
      </div>

      <div id="jadwal-wrapper"></div>
    </div>

    {{-- ══ Submit ══ --}}
    <div class="submit-row">
      <button type="submit" class="btn-submit">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5">
          <path d="M5 12l5 5L20 7"/>
        </svg>
        Submit Event
      </button>
    </div>

  </form>
</div>

<script>
let jadwalIndex = 0;

function addJadwal() {
  const wrapper = document.getElementById('jadwal-wrapper');

  const html = `
  <div class="jadwal-item">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
      <div class="jadwal-label">Jadwal #${jadwalIndex + 1}</div>
      <button type="button" class="btn-remove"
              onclick="this.closest('.jadwal-item').remove()">
        ✕ Hapus
      </button>
    </div>

    <div class="field-grid" style="margin-bottom:0;">
      <div class="field-group">
        <label>Info Jadwal</label>
        <input type="text"
               name="jadwal[${jadwalIndex}][info]"
               class="rsc-input" placeholder="Misal: Hari 1 / Stage A"
               required>
      </div>

      <div class="field-group">
        <label>Tanggal & Waktu</label>
        <input type="datetime-local"
               name="jadwal[${jadwalIndex}][tanggal]"
               class="rsc-input" required>
      </div>

      <div class="field-group span2">
        <label>Deskripsi Jadwal</label>
        <textarea name="jadwal[${jadwalIndex}][deskripsi]"
                  class="rsc-input" style="min-height:64px;"
                  placeholder="Opsional…"></textarea>
      </div>
    </div>

    <div class="ticket-box">
      <div class="ticket-box-header">
        <span class="ticket-box-label">Tiket</span>
        <button type="button" class="btn-add-ticket"
                onclick="addTicket(${jadwalIndex})">
          + Tiket
        </button>
      </div>
      <div id="ticket-wrapper-${jadwalIndex}"></div>
    </div>
  </div>`;

  wrapper.insertAdjacentHTML('beforeend', html);
  addTicket(jadwalIndex);
  jadwalIndex++;
}

function addTicket(jadwalId) {
  const wrapper = document.getElementById(`ticket-wrapper-${jadwalId}`);
  const ticketId = Date.now();

  const html = `
  <div class="ticket-item">
    <div class="ticket-grid">
      <div class="field-group">
        <label>Nama Tiket</label>
        <input type="text"
               name="jadwal[${jadwalId}][tickets][${ticketId}][name]"
               class="rsc-input" placeholder="Reguler / VIP" required>
      </div>
      <div class="field-group">
        <label>Harga (Rp)</label>
        <input type="number"
               name="jadwal[${jadwalId}][tickets][${ticketId}][price]"
               class="rsc-input" placeholder="0" required>
      </div>
      <div class="field-group">
        <label>Stok</label>
        <input type="number"
               name="jadwal[${jadwalId}][tickets][${ticketId}][stock]"
               class="rsc-input" placeholder="100" required>
      </div>
      <button type="button"
              class="btn-remove-ticket"
              style="margin-top:22px;"
              onclick="this.closest('.ticket-item').remove()">
        ✕
      </button>
    </div>
    <div class="ticket-grid2">
      <div class="field-group">
        <label>Mulai Jual</label>
        <input type="datetime-local"
               name="jadwal[${jadwalId}][tickets][${ticketId}][start_sale]"
               class="rsc-input">
      </div>
      <div class="field-group">
        <label>Akhir Jual</label>
        <input type="datetime-local"
               name="jadwal[${jadwalId}][tickets][${ticketId}][end_sale]"
               class="rsc-input">
      </div>
    </div>
  </div>`;

  wrapper.insertAdjacentHTML('beforeend', html);
}

function previewPoster(event) {
  const file = event.target.files[0];
  const preview = document.getElementById('posterPreview');
  if (file) {
    preview.src = URL.createObjectURL(file);
    preview.style.display = 'block';
  }
}

document.addEventListener('DOMContentLoaded', function () {
  addJadwal();
});
</script>

@endsection