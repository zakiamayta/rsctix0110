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

.rsc-input:disabled{
    background:#f3f3f3;
    cursor:not-allowed;
    opacity:.7;
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
          <input type="datetime-local"id="eventDate" name="date" class="rsc-input"required>
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
function getEventDate()
{
    return document.getElementById('eventDate').value;
}

document.querySelector(
    'input[name="ticket_sale_start"]'
).addEventListener('change', function(){



    const eventValue = getEventDate();

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
    }
        updateTicketSaleInputs();

});

let jadwalIndex = 0;

function addJadwal()
{

    let defaultDate = getEventDate();

    if(jadwalIndex > 0){

        const previousInput =
            document.querySelector(
                `input[name="jadwal[${jadwalIndex - 1}][tanggal]"]`
            );

        if(previousInput?.value){

            const nextDate =
                new Date(previousInput.value);

            nextDate.setDate(
                nextDate.getDate() + 1
            );

            defaultDate =
                nextDate
                .toISOString()
                .slice(0,16);
        }
    }



    const wrapper =
        document.getElementById('jadwal-wrapper');

    const isFirstJadwal = jadwalIndex === 0;

    const html = `
    <div class="jadwal-item">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">

            <div class="jadwal-label">
                Jadwal #${jadwalIndex + 1}
            </div>

            ${
                isFirstJadwal
                ? ''
                : `
                <button
                    type="button"
                    class="btn-remove"
                    onclick="this.closest('.jadwal-item').remove()">
                    ✕ Hapus
                </button>
                `
            }

        </div>

        <div class="field-grid">

            <div class="field-group">

                <label>Info Jadwal</label>

                <input
                    type="text"
                    name="jadwal[${jadwalIndex}][info]"
                    class="rsc-input"
                    required>

            </div>

            <div class="field-group">

                <label>Tanggal & Waktu</label>

                <input
                    type="datetime-local"
                    name="jadwal[${jadwalIndex}][tanggal]"
                    class="rsc-input jadwal-date"
                    value="${defaultDate}"
                    ${isFirstJadwal ? 'readonly' : ''}
                    required>

            </div>

            <div class="field-group span2">

                <label>Deskripsi Jadwal</label>

                <textarea
                    name="jadwal[${jadwalIndex}][deskripsi]"
                    class="rsc-input"></textarea>

            </div>

        </div>

        <div class="ticket-box">

            <div class="ticket-box-header">

                <span class="ticket-box-label">
                    Tiket
                </span>

                <button
                    type="button"
                    class="btn-add-ticket"
                    onclick="addTicket(${jadwalIndex})">

                    + Tiket

                </button>

            </div>

            <div id="ticket-wrapper-${jadwalIndex}">
            </div>

        </div>

    </div>
    `;

    wrapper.insertAdjacentHTML(
        'beforeend',
        html
    );



    addTicket(jadwalIndex);

        updateTicketSaleInputs();

    jadwalIndex++;
}

document
.getElementById('eventDate')
.addEventListener('change', function(){

    const firstJadwal =
        document.querySelector('.jadwal-date');

    if(firstJadwal){
        firstJadwal.value = this.value;
    }

});

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
    }

    updateTicketSaleInputs();

});

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

        const match =
            e.target.name.match(
                /jadwal\[(\d+)\]/
            );

        let previousInput = null;

        if(match){

            const currentIndex =
                parseInt(match[1]);

            if(currentIndex > 0){

                previousInput =
                    document.querySelector(
                        `input[name="jadwal[${currentIndex - 1}][tanggal]"]`
                    );

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

document.addEventListener('change', function(e){

    if(
        !e.target.name ||
        !e.target.name.includes('[start_sale]')
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
        alert('Isi tanggal event terlebih dahulu.');
        e.target.value = '';
        return;
    }

    const saleDate =
        new Date(e.target.value);

    const eventDate =
        new Date(eventValue);

    const latestAllowed =
        new Date(eventDate);

    latestAllowed.setDate(
        latestAllowed.getDate() - 2
    );

    // wajib H-2
    if(saleDate > latestAllowed){

        alert(
            'Mulai penjualan tiket minimal H-2 sebelum event.'
        );

        e.target.value = '';
        return;
    }


    if(globalSaleStart){

    const globalDate =
        new Date(globalSaleStart);

    if(saleDate < globalDate){

        alert(
            'Penjualan tiket tidak boleh sebelum tanggal Mulai Penjualan Tiket Event.'
        );

        e.target.value = '';
        return;
    }

}
    

});

document.addEventListener('change', function(e){

    const eventDate =
        new Date(getEventDate());

    if(
        e.target.name &&
        e.target.name.includes('[end_sale]')
    ){

        const endDate =
            new Date(e.target.value);

        const ticketItem =
            e.target.closest('.ticket-item');

        const startInput =
            ticketItem.querySelector(
                'input[name*="[start_sale]"]'
            );

        if(startInput?.value){

            const startDate =
                new Date(startInput.value);

            if(endDate < startDate){

                alert(
                    'Akhir penjualan tiket tidak boleh sebelum mulai penjualan tiket.'
                );

                e.target.value = '';
                return;
            }
        }

        if(endDate > eventDate){

            alert(
                'Akhir penjualan tiket tidak boleh melebihi tanggal event.'
            );

            e.target.value = '';
            return;
        }
    }

});

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
              class="rsc-input ticket-start-sale"
              disabled>
      </div>
      <div class="field-group">
        <label>Akhir Jual</label>
        <input type="datetime-local"
              name="jadwal[${jadwalId}][tickets][${ticketId}][end_sale]"
              class="rsc-input ticket-end-sale"
              disabled>
      </div>
    </div>
  </div>`;

  wrapper.insertAdjacentHTML('beforeend', html);

  updateTicketSaleInputs();
}

function updateTicketSaleInputs()
{
    const saleStart =
        document.querySelector(
            'input[name="ticket_sale_start"]'
        )?.value;

    const redeemStart =
        document.querySelector(
            'input[name="ticket_redeem_start"]'
        )?.value;

    const enabled =
        saleStart &&
        redeemStart;

    document
        .querySelectorAll(
            '.ticket-start-sale, .ticket-end-sale'
        )
        .forEach(input => {

            input.disabled = !enabled;

            if(!enabled){
                input.value = '';
            }

        });
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