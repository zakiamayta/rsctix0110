@extends('layouts.app')

@section('title', 'Pembelian Tiket')

@section('content')
<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 py-8">
  <div class="container py-5">

    <!-- 🔹 Notifikasi Alert (Floating) -->
    <div id="page-alert" class="alert alert-danger d-none text-center d-flex align-items-center justify-content-center gap-2 shadow-lg rounded-lg" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <span id="page-alert-text"></span>
    </div>

    <div class="row g-4">
      <!-- Kiri: Poster & Kategori Tiket -->
      <div class="col-lg-5">
        <!-- Poster Event -->
        <div class="card shadow-sm border-0 mb-3 overflow-hidden rounded-3">
          <img src="{{ asset($event->poster) }}" class="card-img-top" style="max-height: 250px; object-fit: cover;" alt="Poster Event">
          <div class="card-body">
            <h4 class="card-title fw-bold text-orange-600 mb-2">{{ $event->title }}</h4>
            <p class="card-text text-white mb-1">
              <i class="bi bi-geo-alt me-1"></i> {{ $event->location }}  
              — <i class="bi bi-calendar-event me-1"></i> {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d F Y') }}
            </p>
            <p class="card-text small text-gray-500">{{ $event->description }}</p>
          </div>

        </div>

        <!-- Kategori Tiket -->
        <div class="card shadow-sm border-0 rounded-3">
          <div class="card-header bg-grey fw-semibold fs-5">Kategori Tiket</div>
          <div class="card-body">
            @foreach ($tickets as $ticket)
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
              <div>
                <div class="fw-bold">{{ $ticket->name }}</div>
                <div class="text-gray-500">Rp {{ number_format($ticket->price, 0, ',', '.') }}</div>
              </div>
              <div class="ticket-control" id="ticket-control-{{ $ticket->id }}" data-price="{{ $ticket->price }}" data-stock="{{ $ticket->stock }}">
                @if ($ticket->stock > 0)
                  <button type="button" class="btn btn-sm btn-orange-pill" onclick="addTicket({{ $ticket->id }}, {{ $ticket->price }}, {{ $ticket->stock }})">
                    <i class="bi bi-plus-lg me-1"></i>Tambah
                  </button>
                @else
                  <span class="badge bg-danger px-3 py-2">SOLD</span>
                @endif
              </div>
            </div>
            @endforeach
          </div>
        </div>

      </div>

      <!-- Kanan: Detail & Form -->
      <div class="col-lg-7">
        <!-- Detail Pesanan -->
        <div class="card shadow-sm border-0 mb-3 rounded-3">
          <div class="card-header bg-grey fw-semibold fs-5">Detail Pesanan</div>
          <div class="card-body">
            <p class="mb-2"><span id="ticket-count">0</span> Tiket Dipesan</p>
            <p class="fw-bold border-top pt-2 mb-0">
              Total Bayar 
              <span class="float-end">Rp <span id="total-final">0</span></span>
            </p>
          </div>
        </div>

        <!-- Form Pemesanan -->
        <div class="card shadow-sm border-0 rounded-3">
          <div class="card-header bg-grey fw-semibold fs-5">Form Pemesanan</div>
          <div class="card-body">
            @if(session('error'))
              <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                  @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <div id="ticket-error" class="alert alert-danger d-none"></div>

            @php $activeTicket = $tickets->first(); @endphp

            @if($activeTicket)
            <form action="{{ route('ticket.store') }}" method="POST" id="ticket-form">
              @csrf
              <div class="mb-3">
                <label class="form-label">Email Pembeli 
                  <small class="text-white d-block">
                    <i class="bi bi-envelope me-1"></i> Tiket online akan dikirim ke email ini
                  </small>
                </label>

                <input type="email" name="email" class="form-control rounded-pill" required placeholder="nama@gmail.com" value="{{ old('email') }}" />
              </div>
              <input type="hidden" name="qty" id="ticketQty" value="0">
              <div id="ticket-hidden-inputs"><!-- JS akan men-generate ticket_id[] & qty[] di sini --></div>


              <!-- List Data Pengunjung -->
              <div id="pengunjung-list"></div>

              <div class="text-end">
                <button type="submit" 
                    class="bg-gradient-to-r from-orange-500 to-yellow-400 hover:from-orange-600 hover:to-yellow-500 
                          text-white font-semibold px-5 py-2 rounded-pill shadow-md 
                          transition transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
                    id="checkout-btn" disabled>
                    Checkout
                </button>
              </div>
            </form>
            @else
              <div class="alert alert-warning">Tiket belum tersedia saat ini.</div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@if($activeTicket)
  <script>
    const maxQty = {{ $event->max_tickets_per_email }}; // dari server
    let cart = {};      // { ticketId: qty }
    let order = [];     // sequence of ticketId (menentukan mapping tiap pengunjung ke tiket)
    let savedValues = []; // menyimpan name/phone sementara saat rebuild form

    function showPageAlert(message) {
      const alertBox = document.getElementById('page-alert');
      document.getElementById('page-alert-text').innerText = message;
      alertBox.classList.remove('d-none');
      setTimeout(() => alertBox.classList.add('show'), 10);
      setTimeout(() => { alertBox.classList.remove('show'); setTimeout(() => alertBox.classList.add('d-none'), 500); }, 3000);
    }

    // Render tombol kontrol (baca price/stock dari data-* server-rendered)
    function renderTicketControls(ticketId, qty = 0) {
      const control = document.getElementById(`ticket-control-${ticketId}`);
      if (!control) return;
      const price = parseInt(control.dataset.price || 0);
      const stock = parseInt(control.dataset.stock || 0);

      // simpan data pada dataset (jika awalnya kosong)
      control.dataset.price = price;
      control.dataset.stock = stock;

      if (stock === 0) {
        control.innerHTML = `<span class="badge bg-danger px-3 py-2">SOLD</span>`;
        return;
      }

      if (qty > 0) {
        control.innerHTML = `
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-orange-circle" onclick="decreaseTicket(${ticketId})">
              <i class="bi bi-dash-lg"></i>
            </button>
            <span class="fw-bold">${qty}</span>
            <button type="button" class="btn btn-sm btn-orange-circle" onclick="addTicket(${ticketId})">
              <i class="bi bi-plus-lg"></i>
            </button>
          </div>`;
      } else {
        control.innerHTML = `
          <button type="button" class="btn btn-sm btn-orange-pill" onclick="addTicket(${ticketId})">
            <i class="bi bi-plus-lg me-1"></i>Tambah
          </button>`;
      }
    }

    function addTicket(ticketId) {
      const control = document.getElementById(`ticket-control-${ticketId}`);
      if (!control) return;
      const price = parseInt(control.dataset.price || 0);
      const stock = parseInt(control.dataset.stock || 0);

      const totalQty = order.length;
      if (totalQty + 1 > maxQty) {
        showPageAlert(`Maksimal pembelian per email adalah ${maxQty} tiket.`);
        return;
      }

      const cur = cart[ticketId] || 0;
      if (cur + 1 > stock) {
        showPageAlert(`Maksimal pembelian untuk tiket ini hanya ${stock} buah.`);
        return;
      }

      cart[ticketId] = cur + 1;
      order.push(ticketId); // catat urutan (penting untuk removePengunjung)
      renderTicketControls(ticketId, cart[ticketId]);
      updateSummary();
    }

    function decreaseTicket(ticketId) {
      const cur = cart[ticketId] || 0;
      if (cur <= 0) return;

      cart[ticketId] = cur - 1;
      // hapus last occurrence dari order
      const idx = order.lastIndexOf(ticketId);
      if (idx !== -1) order.splice(idx, 1);
      if (cart[ticketId] === 0) delete cart[ticketId];

      renderTicketControls(ticketId, cart[ticketId] || 0);
      updateSummary();
    }

    // Mengupdate ringkasan, hidden inputs dan merender form pengunjung sesuai total quantity (order.length)
    function updateSummary() {
      const totalQty = order.length;
      let totalPrice = 0;

      Object.keys(cart).forEach(ticketId => {
        const control = document.getElementById(`ticket-control-${ticketId}`);
        const price = parseInt(control?.dataset?.price || 0);
        const qty = cart[ticketId] || 0;
        totalPrice += price * qty;
      });

      document.getElementById('ticket-count').innerText = totalQty;
      document.getElementById('total-final').innerText = totalPrice.toLocaleString();
      document.getElementById('checkout-btn').disabled = totalQty === 0;
      document.getElementById('ticketQty').value = totalQty;

      // update hidden aggregated inputs (ticket_id[] & qty[])
      const hiddenWrap = document.getElementById('ticket-hidden-inputs');
      if (hiddenWrap) {
        hiddenWrap.innerHTML = '';
        Object.keys(cart).forEach(ticketId => {
          hiddenWrap.insertAdjacentHTML('beforeend', `<input type="hidden" name="ticket_id[]" value="${ticketId}">`);
          hiddenWrap.insertAdjacentHTML('beforeend', `<input type="hidden" name="qty[]" value="${cart[ticketId]}">`);
        });
      }

      // rebuild visitor forms sesuai jumlah order (preserve existing values)
      updateFormFields(totalQty);
    }

    function updateFormFields(qty) {
      const wrapper = document.getElementById('pengunjung-list');
      // tangkap nilai lama
      const old = [];
      wrapper.querySelectorAll('.pengunjung-entry').forEach((entry, i) => {
        const nm = entry.querySelector('input[name="name[]"]')?.value || '';
        const ph = entry.querySelector('input[name="phone[]"]')?.value || '';
        old[i] = { name: nm, phone: ph };
      });

      wrapper.innerHTML = '';
      // sesuaikan savedValues length
      savedValues = savedValues.slice(0, qty);

      for (let i = 0; i < qty; i++) {
        const div = document.createElement('div');
        div.className = 'border rounded-4 p-3 mb-3 position-relative pengunjung-entry shadow-sm fade-in';
        div.dataset.index = i;

        div.innerHTML = `
          <button type="button" class="btn btn-sm position-absolute top-0 end-0 m-2 rounded-circle bg-danger text-white shadow-sm trash-btn" title="Hapus pengunjung">
            <i class="bi bi-trash"></i>
          </button>
          <p class="fw-semibold mb-3 text-white">Data Pengunjung ${i + 1}</p>
          <div class="mb-3">
            <label class="form-label">Nama Lengkap:</label>
            <input type="text" name="name[]" required class="form-control rounded-pill" placeholder="Nama sesuai KTP" />
          </div>
          <div>
            <label class="form-label">No. Telepon:</label>
            <input type="text" name="phone[]" class="form-control rounded-pill" placeholder="08xxxxxxxxxx" />
          </div>`;

        wrapper.appendChild(div);

        const nameInput = div.querySelector('input[name="name[]"]');
        const phoneInput = div.querySelector('input[name="phone[]"]');
        const trashBtn = div.querySelector('.trash-btn');

        // restore old values
        if (old[i]) {
          nameInput.value = old[i].name;
          phoneInput.value = old[i].phone;
          savedValues[i] = { name: old[i].name, phone: old[i].phone };
        } else if (!savedValues[i]) {
          savedValues[i] = { name: '', phone: '' };
        }

        const syncSaved = () => { savedValues[i] = { name: nameInput.value, phone: phoneInput.value }; };
        nameInput.addEventListener('input', syncSaved);
        phoneInput.addEventListener('input', syncSaved);

        // per tombol hapus: panggil removePengunjung dengan index sekarang dari dataset
        trashBtn.addEventListener('click', () => {
          const idx = Number(div.dataset.index);
          removePengunjung(idx);
        });
      }
    }

    // Hapus pengunjung pada index tertentu -> juga hapus mapping ticket dari order & kurangi cart
    function removePengunjung(index) {
      index = Number(index);
      if (isNaN(index) || index < 0 || index >= order.length) return;

      const ticketId = order.splice(index, 1)[0];
      if (ticketId) {
        cart[ticketId] = (cart[ticketId] || 1) - 1;
        if (cart[ticketId] <= 0) delete cart[ticketId];
      }

      savedValues.splice(index, 1);
      updateSummary();
    }

    // Inisialisasi saat DOM siap
    window.addEventListener('DOMContentLoaded', () => {
      // render control untuk setiap ticket (mengambil data dari data-* yang sudah kita tambahkan server-side)
      document.querySelectorAll('.ticket-control').forEach(ctrl => {
        const id = ctrl.id.replace('ticket-control-', '');
        renderTicketControls(id, 0);
      });

      // jika aturan event mengharuskan maxQty == 1, auto-add 1 tiket ke activeTicket
      const initialQty = (maxQty === 1) ? 1 : 0;
      if (initialQty === 1) {
        const initialTicketId = {{ $activeTicket->id }};
        addTicket(initialTicketId);
      }
    });

    // Validasi sebelum submit (telepon numeric dan length)
    document.getElementById('ticket-form')?.addEventListener('submit', function(e) {
      const phoneInputs = document.querySelectorAll('input[name="phone[]"]');
      let valid = true;
      let message = "";

      phoneInputs.forEach((input, i) => {
        const val = input.value.trim();

        if (!/^[0-9]+$/.test(val)) {
          valid = false;
          message = `Nomor telepon pengunjung ${i+1} hanya boleh berisi angka.`;
        } else if (val.length < 9 || val.length > 15) {
          valid = false;
          message = `Nomor telepon pengunjung ${i+1} tidak valid (9–15 digit).`;
        }
      });

      if (!valid) {
        e.preventDefault();
        showPageAlert(message);
      }
    });
  </script>

@endif
@endsection
