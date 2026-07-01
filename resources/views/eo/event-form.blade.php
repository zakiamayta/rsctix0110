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
    font-size:.72rem; font-weight:800; text-transform:uppercase;
    letter-spacing:1.4px; color:#E8470A;
    display:flex; align-items:center; gap:8px; margin:0 0 14px;
  }
  #resubmitForm .rs-section-title::after {
    content:''; flex:1; height:1px;
    background:linear-gradient(to right,#E2DBD4,transparent);
  }
  /* Badge tambahan untuk menegaskan mode edit */
  #resubmitForm .rs-edit-badge {
    font-size: 0.58rem; background: rgba(232,71,10,.1); 
    color: #E8470A; padding: 2px 6px; border-radius: 4px; margin-left: auto;
  }

  /* grid */
  #resubmitForm .rs-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  #resubmitForm .rs-span2 { grid-column:span 2; }

  /* field */
  #resubmitForm .rs-field { display:flex; flex-direction:column; gap:6px; }
  #resubmitForm .rs-field label {
    font-size:.67rem; font-weight:700; color:#8A7E76;
    text-transform:uppercase; letter-spacing:.6px; margin:0;
    display: flex; align-items: center; gap: 4px;
  }
  /* Tambahan indikator kecil penanda field bisa diubah */
  #resubmitForm .rs-field label::after {
    content: '✏️'; font-size: 0.6rem; opacity: 0; transition: opacity 0.2s;
  }
  #resubmitForm .rs-field:focus-within label::after {
    opacity: 0.6;
  }

  #resubmitForm .rs-readonly{
    background:#ECE7E1 !important;
    color:#6F655E !important;
    border:1px solid #D4CCC4 !important;
    cursor:not-allowed !important;
    pointer-events:none;
    user-select:none;
}

#resubmitForm .rs-readonly:focus{
    box-shadow:none !important;
    border-color:#D4CCC4 !important;
}

  /* inputs (DIUBAH KE PUTIH AGAR TERLIHAT EDITABLE) */
  #resubmitForm .rs-input {
    background:#ffffff !important; /* Mengesankan kolom aktif/bisa diisi */
    border:1px solid #C4BBB3 !important; /* Sedikit lebih tegas dari sebelumnya */
    border-radius:9px !important;
    color:#1A1208 !important;
    padding:10px 12px !important;
    font-size:.85rem !important;
    width:100%; outline:none;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05) !important; /* Efek kedalaman form */
    transition: border-color .18s, box-shadow .18s, background-color .18s;
    font-family:'DM Sans',sans-serif !important;
  }
  #resubmitForm .rs-input::placeholder { color:#C4BBB3; }
  
  /* Hover state sebelum di-klik */
  #resubmitForm .rs-input:hover {
    border-color: #A3968E !important;
    background: #FFFDFB !important;
  }
  
  /* Focus state saat mulai mengetik */
  #resubmitForm .rs-input:focus {
    border-color:#E8470A !important;
    box-shadow:0 0 0 3px rgba(232,71,10,.15) !important;
    background:#fff !important;
  }
  #resubmitForm textarea.rs-input { resize:vertical; min-height:80px; }
  #resubmitForm input[type="datetime-local"].rs-input::-webkit-calendar-picker-indicator {
    filter: opacity(.6); cursor: pointer;
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

  /* poster thumb (Dibuat agak redup untuk membedakan dengan dropzone baru) */
  #resubmitForm .rs-thumb {
    position:relative; display:inline-block;
    border:1px solid #E2DBD4; border-radius:9px;
    overflow:hidden; margin-bottom:8px;
    opacity: 0.85; transition: opacity 0.2s;
  }
  #resubmitForm .rs-thumb:hover { opacity: 1; }
  #resubmitForm .rs-thumb img {
    display:block; width:130px; height:86px; object-fit:cover;
  }
  #resubmitForm .rs-thumb-badge {
    position:absolute; bottom:5px; left:5px;
    background:rgba(26,18,8,.7); backdrop-filter:blur(4px);
    color:#fff; font-size:.57rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.7px;
    padding:2px 6px; border-radius:4px;
  }

  /* file zone */
  #resubmitForm .rs-file-zone {
    border:1.5px dashed #C4BBB3; border-radius:9px;
    padding:16px 12px; background:#FDFBF9; cursor:pointer;
    text-align:center; position:relative;
    transition:border-color .2s, background .2s, transform .1s;
  }
  #resubmitForm .rs-file-zone:hover {
    border-color:#E8470A; background:rgba(232,71,10,.04);
    transform: scale(0.995);
  }
  #resubmitForm .rs-file-zone input[type="file"] {
    position:absolute; inset:0; opacity:0; cursor:pointer; width:100%;
  }
  #resubmitForm .rs-file-zone-txt { font-size:.78rem; color:#6E635C; }
  #resubmitForm .rs-file-zone-txt span { color:#E8470A; font-weight:700; }
  #resubmitForm .rs-file-zone-sub { font-size:.65rem; color:#B5AEA8; margin-top:3px; }

  /* jadwal */
  #resubmitForm .rs-jadwal {
    background:#FDFBF9; border:1px solid #E2DBD4;
    border-radius:12px; padding:16px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
  }
  #resubmitForm .rs-jadwal-num {
    font-size:.65rem; font-weight:800; text-transform:uppercase;
    letter-spacing:1px; color:#E8470A;
    display:flex; align-items:center; gap:8px; margin-bottom:14px;
  }
  #resubmitForm .rs-jadwal-num span {
    flex:1; height:1px; background:#E2DBD4; display:block;
  }

  /* ticket (DIUBAH MENJADI PUTIH AGAR INPUT DI DALAMNYA TIDAK DOBEL ABU-ABU) */
  #resubmitForm .rs-ticket-box {
    background:#F7F4F0; border:1px solid #E2DBD4;
    border-radius:9px; padding:14px; margin-top:12px;
  }
  #resubmitForm .rs-ticket-box-lbl {
    font-size:.63rem; font-weight:700; color:#8A7E76;
    text-transform:uppercase; letter-spacing:1px;
    display:flex; align-items:center; gap:7px; margin-bottom:10px;
  }
  #resubmitForm .rs-ticket-box-lbl span {
    flex:1; height:1px; background:#E2DBD4; display:block;
  }
  #resubmitForm .rs-ticket {
    background:#ffffff; border:1px solid #E2DBD4;
    border-radius:8px; padding:12px; margin-bottom:8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
  }
  #resubmitForm .rs-ticket:last-child { margin-bottom:0; }
  #resubmitForm .rs-ticket-grid {
    display:grid; grid-template-columns:2fr 1fr 1fr; gap:10px;
  }
  #resubmitForm .rs-ticket-lbl {
    font-size:.58rem; font-weight:700; color:#8A7E76;
    text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px;
  }

  /* submit button */
  #resubmitForm .rs-btn-submit {
    display:inline-flex; align-items:center; gap:8px;
    background:#E8470A; color:#fff; border:none;
    border-radius:9px; padding:12px 26px;
    font-size:.85rem; font-weight:800; cursor:pointer;
    letter-spacing:.5px; transition:opacity .15s, transform .12s, box-shadow .15s;
    font-family:'Sora','DM Sans',sans-serif;
    box-shadow: 0 4px 12px rgba(232, 71, 10, 0.2);
  }
  #resubmitForm .rs-btn-submit:hover { opacity:.95; transform:translateY(-1px); box-shadow: 0 6px 16px rgba(232, 71, 10, 0.3); }
  #resubmitForm .rs-btn-submit:active { transform:translateY(1px); }

  @media (max-width:540px) {
    #resubmitForm .rs-grid { grid-template-columns:1fr; gap:10px; }
    #resubmitForm .rs-span2 { grid-column:1; }
    #resubmitForm .rs-ticket-grid { grid-template-columns:1fr; gap:8px; }
  }
</style>

<form id="resubmitForm"
      action="{{ route('eo.event.update', $event->id) }}"
      method="POST"
      enctype="multipart/form-data">
  @csrf
  @method('PUT')

  @if ($errors->any())
<div style="
    background:#FDECEC;
    border:1px solid #F5C2C2;
    color:#9C2222;
    padding:14px;
    border-radius:10px;
    margin-bottom:15px;
">
    @foreach($errors->all() as $error)
        <div>• {{ $error }}</div>
    @endforeach
</div>
@endif

  {{-- Admin note / Rejection Reason --}}
@if(
    $event->status === 'rejected'
    &&
    ($event->rejection_reason || $event->owner_note)
)
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
        <label>Judul Event 🔒</label>
        <input type="text"
                name="title"
                class="rs-input rs-readonly"
                value="{{ $event->title }}"
                readonly>
      </div>

      <div class="rs-field">
        <label>Tanggal Event 🔒</label>
        <input type="datetime-local"
                id="eventDate"
                name="date"
                class="rs-input rs-readonly"
                value="{{ \Carbon\Carbon::parse($event->date)->format('Y-m-d\TH:i') }}"
                readonly>
      </div>

      <div class="rs-field">
        <label>Instagram 🔒</label>
        <input type="text"
            name="instagram"
            class="rs-input rs-readonly"
            value="{{ $event->instagram }}"
            readonly>
      </div>

      <div class="rs-field">
        <label>Lineup</label>
        <input type="text" name="lineup" class="rs-input"
               value="{{ old('lineup', $event->lineup) }}" placeholder="Nama artis / performer">
      </div>

      <div class="rs-field">
        <label>Minimal Umur</label>
        <input type="number" name="min_age" class="rs-input"
               value="{{ old('min_age', $event->min_age) }}" placeholder="17">
      </div>

      <div class="rs-field">
        <label>Maks Tiket / Email</label>
        <input type="number" name="max_tickets_per_email" class="rs-input"
               value="{{ old('max_tickets_per_email', $event->max_tickets_per_email) }}" required>
      </div>

      <div class="rs-field">
        <label>Mulai Penjualan Tiket</label>
        <input type="datetime-local" name="ticket_sale_start" class="rs-input"
               value="{{ old('ticket_sale_start', $event->ticket_sale_start ? \Carbon\Carbon::parse($event->ticket_sale_start)->format('Y-m-d\TH:i') : '') }}">
      </div>

      <div class="rs-field">
        <label>Mulai Redeem Tiket</label>
        <input type="datetime-local" name="ticket_redeem_start" class="rs-input"
               value="{{ old('ticket_redeem_start', $event->ticket_redeem_start ? \Carbon\Carbon::parse($event->ticket_redeem_start)->format('Y-m-d\TH:i') : '') }}">
      </div>

      <div class="rs-field rs-span2">
        <label>Lokasi</label>
        <input type="text" name="location" class="rs-input"
               value="{{ old('location', $event->location) }}" placeholder="Venue lengkap" required>
      </div>

      <div class="rs-field rs-span2">
        <label>Deskripsi</label>
        <textarea name="description" class="rs-input" required>{{ old('description', $event->description) }}</textarea>
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
                   value="{{ old("jadwal.$i.info", $jadwal->info) }}"
                   placeholder="Misal: Hari 1 / Stage A" required>
          </div>
          <div class="rs-field">
            <label>Tanggal & Waktu</label>
            <input type="datetime-local"
                  name="jadwal[{{ $i }}][tanggal]"
                  class="rs-input jadwal-date"
                  value="{{ old("jadwal.$i.tanggal", \Carbon\Carbon::parse($jadwal->tanggal)->format('Y-m-d\TH:i')) }}"
                  required>
          </div>
        </div>

        <div class="rs-field">
          <label>Deskripsi Jadwal</label>
          <textarea name="jadwal[{{ $i }}][deskripsi]"
                    class="rs-input"
                    style="min-height:56px;">{{ old("jadwal.$i.deskripsi", $jadwal->deskripsi) }}</textarea>
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
                       value="{{ old("jadwal.$i.tickets.$t.name", $ticket->name) }}"
                       class="rs-input"
                       placeholder="Reguler / VIP" required>
              </div>
              <div>
                <div class="rs-ticket-lbl">Harga (Rp)</div>
                <input type="number"
                       name="jadwal[{{ $i }}][tickets][{{ $t }}][price]"
                       value="{{ old("jadwal.$i.tickets.$t.price", $ticket->price) }}"
                       class="rs-input"
                       placeholder="0" required>
              </div>
              <div>
                <div class="rs-ticket-lbl">Stok</div>
                <input type="number"
                       name="jadwal[{{ $i }}][tickets][{{ $t }}][stock]"
                       value="{{ old("jadwal.$i.tickets.$t.stock", $ticket->stock) }}"
                       class="rs-input"
                       placeholder="0" required>
              </div>
              <div style="margin-top:10px;" class="rs-grid">
                  <div class="rs-field">
                      <label>Mulai Jual</label>
                      <input
                          type="datetime-local"
                          class="rs-input"
                          name="jadwal[{{ $i }}][tickets][{{ $t }}][start_sale]"
                          value="{{ old("jadwal.$i.tickets.$t.start_sale", $ticket->start_sale ? \Carbon\Carbon::parse($ticket->start_sale)->format('Y-m-d\TH:i') : '') }}">
                  </div>

                  <div class="rs-field">
                      <label>Akhir Jual</label>
                      <input
                          type="datetime-local"
                          class="rs-input"
                          name="jadwal[{{ $i }}][tickets][{{ $t }}][end_sale]"
                          value="{{ old("jadwal.$i.tickets.$t.end_sale", $ticket->end_sale ? \Carbon\Carbon::parse($ticket->end_sale)->format('Y-m-d\TH:i') : '') }}">
                  </div>
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
      Selesai
    </button>
  </div>

</form>
<script>
function getEventDate()
{
    return document.getElementById('eventDate').value;
}

function getFirstJadwalDate()
{
    const firstJadwal =
        document.querySelector('.jadwal-date');

    return firstJadwal
        ? firstJadwal.value
        : null;
}

/*
|--------------------------------------------------------------------------
| EVENT DATE CHANGE
|--------------------------------------------------------------------------
*/
document.getElementById('eventDate')
?.addEventListener('change', function(){

    const eventValue = this.value;

    document
        .querySelectorAll('.jadwal-date')
        .forEach(function(el,index){

            if(index === 0){

                el.value = eventValue;
                return;
            }

            if(el.value < eventValue){

                const nextDay =
                    new Date(eventValue);

                nextDay.setDate(
                    nextDay.getDate() + 1
                );

                el.value =
                    nextDay
                    .toISOString()
                    .slice(0,16);
            }

        });

});

/*
|--------------------------------------------------------------------------
| EVENT SALE START
|--------------------------------------------------------------------------
*/
document.querySelector(
    'input[name="ticket_sale_start"]'
)?.addEventListener('change', function(){

    const eventValue =
        getEventDate();

    if(!eventValue){

        alert(
            'Isi tanggal event terlebih dahulu.'
        );

        this.value = '';
        return;
    }

    const eventDate =
        new Date(eventValue);

    const saleDate =
        new Date(this.value);

    const latestAllowed =
        new Date(eventDate);

    latestAllowed.setDate(
        latestAllowed.getDate() - 2
    );

    if(saleDate > latestAllowed){

        alert(
            'Mulai penjualan tiket harus minimal H-2 sebelum event.'
        );

        this.value = '';
        return;
    }

});

/*
|--------------------------------------------------------------------------
| REDEEM START
|--------------------------------------------------------------------------
*/
document.querySelector(
    'input[name="ticket_redeem_start"]'
)?.addEventListener('change', function(){

    const saleStart =
        document.querySelector(
            'input[name="ticket_sale_start"]'
        )?.value;

    const eventValue =
        getEventDate();

    if(!saleStart){

        alert(
            'Isi tanggal mulai penjualan tiket terlebih dahulu.'
        );

        this.value = '';
        return;
    }

    if(!eventValue){
        return;
    }

    const redeemDate =
        new Date(this.value);

    const saleDate =
        new Date(saleStart);

    const eventDate =
        new Date(eventValue);

    if(redeemDate < saleDate){

        alert(
            'Tanggal redeem tidak boleh sebelum tanggal mulai penjualan tiket.'
        );

        this.value = '';
        return;
    }

    if(redeemDate > eventDate){

        alert(
            'Tanggal redeem tidak boleh melebihi tanggal event.'
        );

        this.value = '';
        return;
    }

});

/*
|--------------------------------------------------------------------------
| VALIDASI JADWAL
|--------------------------------------------------------------------------
*/
document.addEventListener(
    'change',
    function(e){

        if(
            !e.target.classList.contains(
                'jadwal-date'
            )
        ){
            return;
        }

        const eventValue =
            getEventDate();

        if(!eventValue){
            return;
        }

        const eventDate =
            new Date(eventValue);

        const jadwalDate =
            new Date(e.target.value);

        const maxDate =
            new Date(eventValue);

        maxDate.setDate(
            maxDate.getDate() + 14
        );

        const allJadwal =
            document.querySelectorAll(
                '.jadwal-date'
            );

        const index =
            [...allJadwal]
            .indexOf(e.target);

        let previousInput = null;

        if(index > 0){

            previousInput =
                allJadwal[index - 1];

            if(previousInput?.value){

                const previousDate =
                    new Date(
                        previousInput.value
                    );

                if(
                    jadwalDate <
                    previousDate
                ){

                    alert(
                        'Tanggal jadwal tidak boleh lebih awal dari jadwal sebelumnya.'
                    );

                    e.target.value =
                        previousInput.value;

                    return;
                }

            }

        }

        if(jadwalDate < eventDate){

            alert(
                'Tanggal jadwal tidak boleh mendahului tanggal event.'
            );

            e.target.value =
                eventValue;

            return;
        }

        if(jadwalDate > maxDate){

            alert(
                'Tanggal jadwal maksimal 14 hari setelah event.'
            );

            e.target.value =
                previousInput?.value ||
                eventValue;

            return;
        }

    }
);

/*
|--------------------------------------------------------------------------
| VALIDASI TIKET START SALE
|--------------------------------------------------------------------------
*/
document.addEventListener(
    'change',
    function(e){

        if(
            !e.target.name ||
            !e.target.name.includes(
                '[start_sale]'
            )
        ){
            return;
        }

        const eventValue =
            getEventDate();

        const globalSaleStart =
            document.querySelector(
                'input[name="ticket_sale_start"]'
            )?.value;

        if(!eventValue){

            alert(
                'Isi tanggal event terlebih dahulu.'
            );

            e.target.value = '';
            return;
        }

        const saleDate =
            new Date(
                e.target.value
            );

        const eventDate =
            new Date(
                eventValue
            );

        const latestAllowed =
            new Date(
                eventDate
            );

        latestAllowed.setDate(
            latestAllowed.getDate() - 2
        );

        if(
            saleDate >
            latestAllowed
        ){

            alert(
                'Mulai penjualan tiket minimal H-2 sebelum event.'
            );

            e.target.value = '';
            return;
        }

        if(globalSaleStart){

            const globalDate =
                new Date(
                    globalSaleStart
                );

            if(
                saleDate <
                globalDate
            ){

                alert(
                    'Penjualan tiket tidak boleh sebelum tanggal Mulai Penjualan Tiket Event.'
                );

                e.target.value = '';
                return;
            }

        }

    }
);

/*
|--------------------------------------------------------------------------
| VALIDASI TIKET END SALE
|--------------------------------------------------------------------------
*/
document.addEventListener(
    'change',
    function(e){

        if(
            !e.target.name ||
            !e.target.name.includes(
                '[end_sale]'
            )
        ){
            return;
        }

        const eventDate =
            new Date(
                getEventDate()
            );

        const endDate =
            new Date(
                e.target.value
            );

        const ticketBox =
            e.target.closest(
                '.rs-ticket'
            );

        const startInput =
            ticketBox.querySelector(
                'input[name*="[start_sale]"]'
            );

        if(startInput?.value){

            const startDate =
                new Date(
                    startInput.value
                );

            if(
                endDate <
                startDate
            ){

                alert(
                    'Akhir penjualan tiket tidak boleh sebelum mulai penjualan tiket.'
                );

                e.target.value = '';
                return;
            }

        }

        if(
            endDate >
            eventDate
        ){

            alert(
                'Akhir penjualan tiket tidak boleh melebihi tanggal event.'
            );

            e.target.value = '';
            return;
        }

    }
);

/*
|--------------------------------------------------------------------------
| POSTER PREVIEW
|--------------------------------------------------------------------------
*/
function rsPreview(e)
{
    const f =
        e.target.files[0];

    const p =
        document.getElementById(
            'rsPosterPreview'
        );

    if(f){

        p.src =
            URL.createObjectURL(f);

        p.style.display =
            'block';
    }
}
</script>
