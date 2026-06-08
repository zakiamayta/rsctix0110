<style>
  /* ── Scoped to #resubmitForm only — safe inside any modal ── */
  #resubmitForm,
  #resubmitForm * {
    font-family: 'DM Sans', 'Inter', sans-serif;
    box-sizing: border-box;
  }
  #resubmitForm { display:flex; flex-direction:column; gap:24px; padding: 20px; }

  /* section title */
  #resubmitForm .rs-section-title {
    font-size:.67rem; font-weight:800; text-transform:uppercase;
    letter-spacing:1.4px; color:#E8470A;
    display:flex; align-items:center; gap:8px; margin:0 0 14px;
  }
  #resubmitForm .rs-section-title::after {
    content:''; flex:1; height:1px;
    background:linear-gradient(to right,#E2DBD4,transparent);
  }

  /* grid */
  #resubmitForm .rs-grid { display:grid; grid-template-columns:1fr 1fr; gap:11px; }
  #resubmitForm .rs-span2 { grid-column:span 2; }

  /* field */
  #resubmitForm .rs-field { display:flex; flex-direction:column; gap:5px; }
  #resubmitForm .rs-field label {
    font-size:.67rem; font-weight:700; color:#8A7E76;
    text-transform:uppercase; letter-spacing:.6px; margin:0;
  }

  /* inputs */
  #resubmitForm .rs-input {
    background:#F2EEE9 !important;
    border:1px solid #E2DBD4 !important;
    border-radius:9px !important;
    color:#1A1208 !important;
    padding:9px 12px !important;
    font-size:.85rem !important;
    width:100%; outline:none;
    box-shadow:none !important;
    transition:border-color .18s, box-shadow .18s;
    font-family:'DM Sans',sans-serif !important;
  }
  #resubmitForm .rs-input::placeholder { color:#C4BBB3; }
  #resubmitForm .rs-input:focus {
    border-color:#E8470A !important;
    box-shadow:0 0 0 3px rgba(232,71,10,.1) !important;
    background:#fff !important;
  }
  #resubmitForm textarea.rs-input { resize:vertical; min-height:72px; }
  #resubmitForm input[type="datetime-local"].rs-input::-webkit-calendar-picker-indicator {
    filter:opacity(.4);
  }

  /* admin note */
  #resubmitForm .rs-note {
    display:flex; gap:11px; align-items:flex-start;
    background:#FFF7F3;
    border:1px solid rgba(232,71,10,.25);
    border-left:4px solid #E8470A;
    border-radius:10px; padding:12px 14px;
  }
  #resubmitForm .rs-note-icon {
    width:28px; height:28px; flex-shrink:0;
    background:rgba(232,71,10,.1); border-radius:7px;
    display:flex; align-items:center; justify-content:center;
  }
  #resubmitForm .rs-note-tag {
    font-size:.63rem; font-weight:800; text-transform:uppercase;
    letter-spacing:1px; color:#E8470A; margin-bottom:3px;
  }
  #resubmitForm .rs-note-text {
    font-size:.81rem; color:#5A3A28; line-height:1.6; margin:0;
  }

  /* poster thumb */
  #resubmitForm .rs-thumb {
    position:relative; display:inline-block;
    border:1px solid #E2DBD4; border-radius:9px;
    overflow:hidden; margin-bottom:8px;
  }
  #resubmitForm .rs-thumb img {
    display:block; width:130px; height:86px; object-fit:cover;
  }
  #resubmitForm .rs-thumb-badge {
    position:absolute; bottom:5px; left:5px;
    background:rgba(26,18,8,.55); backdrop-filter:blur(4px);
    color:#fff; font-size:.57rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.7px;
    padding:2px 6px; border-radius:4px;
  }

  /* file zone */
  #resubmitForm .rs-file-zone {
    border:1.5px dashed #E2DBD4; border-radius:9px;
    padding:13px 12px; background:#F2EEE9; cursor:pointer;
    text-align:center; position:relative;
    transition:border-color .2s, background .2s;
  }
  #resubmitForm .rs-file-zone:hover {
    border-color:#E8470A; background:rgba(232,71,10,.06);
  }
  #resubmitForm .rs-file-zone input[type="file"] {
    position:absolute; inset:0; opacity:0; cursor:pointer; width:100%;
  }
  #resubmitForm .rs-file-zone-txt { font-size:.74rem; color:#8A7E76; }
  #resubmitForm .rs-file-zone-txt span { color:#E8470A; font-weight:700; }
  #resubmitForm .rs-file-zone-sub { font-size:.65rem; color:#B5AEA8; margin-top:2px; }

  /* jadwal */
  #resubmitForm .rs-jadwal {
    background:#F2EEE9; border:1px solid #E2DBD4;
    border-radius:12px; padding:14px;
  }
  #resubmitForm .rs-jadwal-num {
    font-size:.63rem; font-weight:800; text-transform:uppercase;
    letter-spacing:1px; color:#E8470A;
    display:flex; align-items:center; gap:8px; margin-bottom:12px;
  }
  #resubmitForm .rs-jadwal-num span {
    flex:1; height:1px; background:#E2DBD4; display:block;
  }

  /* ticket */
  #resubmitForm .rs-ticket-box {
    background:#fff; border:1px solid #E2DBD4;
    border-radius:9px; padding:12px; margin-top:10px;
  }
  #resubmitForm .rs-ticket-box-lbl {
    font-size:.6rem; font-weight:700; color:#8A7E76;
    text-transform:uppercase; letter-spacing:1px;
    display:flex; align-items:center; gap:7px; margin-bottom:9px;
  }
  #resubmitForm .rs-ticket-box-lbl span {
    flex:1; height:1px; background:#E2DBD4; display:block;
  }
  #resubmitForm .rs-ticket {
    background:#F2EEE9; border:1px solid #E2DBD4;
    border-radius:7px; padding:10px; margin-bottom:6px;
  }
  #resubmitForm .rs-ticket:last-child { margin-bottom:0; }
  #resubmitForm .rs-ticket-grid {
    display:grid; grid-template-columns:2fr 1fr 1fr; gap:7px;
  }
  #resubmitForm .rs-ticket-lbl {
    font-size:.58rem; font-weight:700; color:#8A7E76;
    text-transform:uppercase; letter-spacing:.5px; margin-bottom:3px;
  }

  /* submit */
  #resubmitForm .rs-btn-submit {
    display:inline-flex; align-items:center; gap:7px;
    background:#E8470A; color:#fff; border:none;
    border-radius:9px; padding:10px 22px;
    font-size:.83rem; font-weight:800; cursor:pointer;
    letter-spacing:.2px; transition:opacity .15s, transform .12s;
    font-family:'Sora','DM Sans',sans-serif;
  }
  #resubmitForm .rs-btn-submit:hover { opacity:.88; transform:translateY(-1px); }

  @media (max-width:540px) {
    #resubmitForm .rs-grid { grid-template-columns:1fr; }
    #resubmitForm .rs-span2 { grid-column:1; }
    #resubmitForm .rs-ticket-grid { grid-template-columns:1fr 1fr; }
  }
</style>

<form id="resubmitForm"
      action="{{ route('eo.event.resubmit', $event->id) }}"
      method="POST"
      enctype="multipart/form-data">
  @csrf
  @method('PUT')

  {{-- Admin note / Rejection Reason --}}
  @if($event->rejection_reason || $event->owner_note)
  <div class="rs-note">
    <div class="rs-note-icon">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
           stroke="#E8470A" stroke-width="2.3">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
    </div>
    <div>
      <div class="rs-note-tag">Catatan Penolakan Admin</div>
      <p class="rs-note-text">
        {{ $event->rejection_reason ?? $event->owner_note }}
      </p>
    </div>
  </div>
  @endif

  {{-- Informasi Event --}}
  <div>
    <div class="rs-section-title">Informasi Event</div>
    <div class="rs-grid">

      <div class="rs-field">
        <label>Judul Event</label>
        <input type="text" name="title" class="rs-input"
               value="{{ $event->title }}" placeholder="Nama event" required>
      </div>

      <div class="rs-field">
        <label>Tanggal Event</label>
        <input type="datetime-local" name="date" class="rs-input"
               value="{{ \Carbon\Carbon::parse($event->date)->format('Y-m-d\TH:i') }}" required>
      </div>

      <div class="rs-field">
        <label>Instagram</label>
        <input type="text" name="instagram" class="rs-input"
               value="{{ $event->instagram }}" placeholder="@username">
      </div>

      <div class="rs-field">
        <label>Lineup</label>
        <input type="text" name="lineup" class="rs-input"
               value="{{ $event->lineup }}" placeholder="Nama artis / performer">
      </div>

      <div class="rs-field">
        <label>Minimal Umur</label>
        <input type="number" name="min_age" class="rs-input"
               value="{{ $event->min_age }}" placeholder="17">
      </div>

      <div class="rs-field">
        <label>Maks Tiket / Email</label>
        <input type="number" name="max_tickets_per_email" class="rs-input"
               value="{{ $event->max_tickets_per_email }}" required>
      </div>

      <div class="rs-field">
        <label>Mulai Penjualan Tiket</label>
        <input type="datetime-local" name="ticket_sale_start" class="rs-input"
               value="{{ $event->ticket_sale_start ? \Carbon\Carbon::parse($event->ticket_sale_start)->format('Y-m-d\TH:i') : '' }}">
      </div>

      <div class="rs-field">
        <label>Mulai Redeem Tiket</label>
        <input type="datetime-local" name="ticket_redeem_start" class="rs-input"
               value="{{ $event->ticket_redeem_start ? \Carbon\Carbon::parse($event->ticket_redeem_start)->format('Y-m-d\TH:i') : '' }}">
      </div>

      <div class="rs-field rs-span2">
        <label>Lokasi</label>
        <input type="text" name="location" class="rs-input"
               value="{{ $event->location }}" placeholder="Venue lengkap" required>
      </div>

      <div class="rs-field rs-span2">
        <label>Deskripsi</label>
        <textarea name="description" class="rs-input" required>{{ $event->description }}</textarea>
      </div>

      <div class="rs-field rs-span2">
        <label>Poster Event</label>

        @if($event->poster)
        <div class="rs-thumb">
          <img src="{{ asset($event->poster) }}" alt="Poster saat ini">
          <span class="rs-thumb-badge">Poster saat ini</span>
        </div>
        @endif

        <div class="rs-file-zone"
             onclick="document.getElementById('rsPosterId').click()">
          <input type="file" id="rsPosterId" name="poster"
                 accept="image/*" onchange="rsPreview(event)">
          <div style="font-size:1.1rem;margin-bottom:3px;">🖼️</div>
          <div class="rs-file-zone-txt">
            <span>Ganti poster</span> atau biarkan kosong
          </div>
          <div class="rs-file-zone-sub">JPG, PNG, WEBP — maks 5 MB</div>
        </div>
        <img id="rsPosterPreview"
             style="display:none;margin-top:8px;width:110px;
                    border-radius:8px;border:1px solid #E2DBD4;">
      </div>

    </div>
  </div>

  {{-- Jadwal & Tiket --}}
  <div>
    <div class="rs-section-title">Jadwal Event</div>

    <div style="display:flex;flex-direction:column;gap:10px;">
      @foreach($event->jadwals as $i => $jadwal)
      <div class="rs-jadwal">

        <div class="rs-jadwal-num">
          Jadwal #{{ $i + 1 }}<span></span>
        </div>

        <div class="rs-grid" style="margin-bottom:10px;">
          <div class="rs-field">
            <label>Info Jadwal</label>
            <input type="text"
                   name="jadwal[{{ $i }}][info]"
                   class="rs-input"
                   value="{{ $jadwal->info }}"
                   placeholder="Misal: Hari 1 / Stage A" required>
          </div>
          <div class="rs-field">
            <label>Tanggal & Waktu</label>
            <input type="datetime-local"
                   name="jadwal[{{ $i }}][tanggal]"
                   class="rs-input"
                   value="{{ \Carbon\Carbon::parse($jadwal->tanggal)->format('Y-m-d\TH:i') }}" required>
          </div>
        </div>

        <div class="rs-field">
          <label>Deskripsi Jadwal</label>
          <textarea name="jadwal[{{ $i }}][deskripsi]"
                    class="rs-input"
                    style="min-height:56px;">{{ $jadwal->deskripsi }}</textarea>
        </div>

        <div class="rs-ticket-box">
          <div class="rs-ticket-box-lbl">Tiket <span></span></div>

          @foreach($jadwal->tickets as $t => $ticket)
          <div class="rs-ticket">
            <div class="rs-ticket-grid">
              <div>
                <div class="rs-ticket-lbl">Nama Tiket</div>
                <input type="text"
                       name="jadwal[{{ $i }}][tickets][{{ $t }}][name]"
                       value="{{ $ticket->name }}"
                       class="rs-input"
                       placeholder="Reguler / VIP" required>
              </div>
              <div>
                <div class="rs-ticket-lbl">Harga (Rp)</div>
                <input type="number"
                       name="jadwal[{{ $i }}][tickets][{{ $t }}][price]"
                       value="{{ $ticket->price }}"
                       class="rs-input"
                       placeholder="0" required>
              </div>
              <div>
                <div class="rs-ticket-lbl">Stok</div>
                <input type="number"
                       name="jadwal[{{ $i }}][tickets][{{ $t }}][stock]"
                       value="{{ $ticket->stock }}"
                       class="rs-input"
                       placeholder="0" required>
              </div>
            </div>
          </div>
          @endforeach
        </div>

      </div>
      @endforeach
    </div>
  </div>

  {{-- Action Submit --}}
  <div style="display:flex;justify-content:flex-end;">
    <button type="submit" class="rs-btn-submit">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2.5">
        <line x1="22" y1="2" x2="11" y2="13"/>
        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
      </svg>
      Re-Submit Perbaikan Event
    </button>
  </div>

</form>

<script>
function rsPreview(e) {
  const f = e.target.files[0];
  const p = document.getElementById('rsPosterPreview');
  if (f) { 
    p.src = URL.createObjectURL(f); 
    p.style.display = 'block'; 
  }
}
</script>