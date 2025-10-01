@extends('layouts.app')

@section('title', 'Pembelian Merchandise')
<link href="{{ asset('css/dark-theme.css') }}" rel="stylesheet">

@section('content')
<div id="page-alert" class="alert alert-danger alert-dismissible fade d-none text-center rounded-3">
    Nomor HP harus 9â€“13 digit angka.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 py-8">
  <div class="container py-4">
    <div class="row g-4">

      {{-- ðŸ”¹ Kiri: Daftar Produk --}}
      <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-3 mb-4">
          <div class="card-header fw-semibold fs-5">
            Daftar Merchandise
          </div>
          <div class="card-body">
            <div class="row">
              @foreach($varians as $varian)
              <div class="col-md-6 col-lg-4 mb-4 d-flex">
                <div class="card h-100 shadow-sm border-0 rounded-3 w-100 d-flex flex-column overflow-hidden">

                  {{-- ðŸ”¹ Carousel Foto Produk --}}
                  <div id="carousel-{{ $varian->id }}" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                      @forelse($varian->images ?? [] as $idx => $img)
                        <div class="carousel-item @if($idx === 0) active @endif">
                          <img src="{{ asset('images/merch/' . $img->url) }}" class="d-block w-100" style="height:200px; object-fit:cover;">
                        </div>
                      @empty
                        <div class="carousel-item active">
                          <img src="https://via.placeholder.com/300x200" class="d-block w-100" style="height:200px; object-fit:cover;">
                        </div>
                      @endforelse
                    </div>

                    @if(optional($varian->images)->count() > 1)
                      <button class="carousel-control-prev" type="button" data-bs-target="#carousel-{{ $varian->id }}" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                      </button>
                      <button class="carousel-control-next" type="button" data-bs-target="#carousel-{{ $varian->id }}" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                      </button>
                    @endif
                  </div>

                  {{-- ðŸ”¹ Info Produk --}}
                  <div class="card-body d-flex flex-column">
                    <h5 class="card-title fw-bold text-orange mb-2">
                      {{ $varian->product->name }} - {{ $varian->varian }}
                    </h5>
                    <p class="small flex-grow-1 mb-3">
                      {{ $varian->product->description }}
                    </p>

                    <button type="button"
                      class="mt-auto btn-orange-pill w-100 fw-semibold btn-add"
                      data-varian="{{ $varian->id }}"
                      data-product="{{ $varian->product->id }}"
                      data-name="{{ $varian->product->name }} - {{ $varian->varian }}"
                      data-ukurans='@json($varian->ukurans)'>
                      <i class="bi bi-cart-plus me-1"></i> Pilih Ukuran
                    </button>
                  </div>

                </div>
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>

      {{-- ðŸ”¹ Kanan: Form Checkout --}}
      <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-3 mb-4">
          <div class="card-header fw-semibold fs-5">
            Detail Pembelian
          </div>
          <div class="card-body">
            <form id="checkoutForm" method="POST" action="{{ route('merch.preview') }}">
              @csrf
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" 
                      class="form-control rounded-pill" 
                      placeholder="Masukkan email anda"
                      required>
              </div>
              <div class="mb-3">
                <label class="form-label">Nama</label>
                <input type="text" name="buyer_name" 
                      class="form-control rounded-pill" 
                      placeholder="Masukkan nama lengkap"
                      required>
              </div>
              <div class="mb-3">
                <label class="form-label">No HP</label>
                <input type="text" name="buyer_phone" 
                      class="form-control rounded-pill" 
                      placeholder="Masukkan nomor HP aktif"
                      required>
              </div>

              <h5 class="mt-4 text-orange">Pesanan Anda</h5>
              <div id="order-items" class="mt-2"></div>

              <div class="mt-3 fw-bold border-top border-secondary pt-2 d-flex justify-content-between align-items-center">
                <span>Total Bayar</span>
                <span class="text-orange">Rp <span id="total" class="text-orange">0</span></span>
              </div>

              <div class="text-end">
                {{-- âœ… Tombol Checkout awalnya disabled --}}
                <button type="submit"
                        id="checkoutBtn"
                        class="btn-orange-pill px-5 py-2 mt-3"
                        disabled>
                  Checkout
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

{{-- ðŸ”¹ Modal Pilih Ukuran --}}
<div class="modal fade" id="ukuranModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card border-0 shadow-lg">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title">Pilih Ukuran</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="card-body" id="ukuranOptions"></div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
let orderItems = [];
let total = 0;
let currentVarian = null;

function renderOrder() {
  let html = '';
  total = 0;
  orderItems.forEach((item, index) => {
    let subtotal = item.harga * item.qty;
    total += subtotal;
    html += `
      <div class="order-item p-3 mb-3 fade-in position-relative card">
        <button type="button" class="btn btn-sm position-absolute top-0 end-0 m-2 rounded-circle bg-danger text-white shadow-sm trash-btn"
                onclick="removeItem(${index})" title="Hapus item">
          <i class="bi bi-trash"></i>
        </button>
        <h6 class="fw-bold text-orange mb-1">${item.name}</h6>
        <p class="small mb-1">Ukuran: ${item.ukuran}</p>
        <p class="mb-2">Harga Satuan: Rp ${item.harga.toLocaleString('id-ID')}</p>
        <div class="d-flex align-items-center gap-3 mb-2">
          <button type="button" class="btn btn-sm btn-orange-circle" onclick="decreaseQty(${index})">
            <i class="bi bi-dash-lg"></i>
          </button>
          <span class="fw-bold fs-5">${item.qty}</span>
          <button type="button" class="btn btn-sm btn-orange-circle" onclick="increaseQty(${index})">
            <i class="bi bi-plus-lg"></i>
          </button>
        </div>
        <p class="fw-semibold text-end mb-0 text-orange">Subtotal: Rp ${subtotal.toLocaleString('id-ID')}</p>

        <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
        <input type="hidden" name="items[${index}][varian_id]" value="${item.varian_id}">
        <input type="hidden" name="items[${index}][ukuran_id]" value="${item.ukuran_id}">
        <input type="hidden" name="items[${index}][name]" value="${item.name}">
        <input type="hidden" name="items[${index}][quantity]" value="${item.qty}">
        <input type="hidden" name="items[${index}][price]" value="${item.harga}">
        <input type="hidden" name="items[${index}][subtotal]" value="${subtotal}">
        <input type="hidden" name="items[${index}][ukuran]" value="${item.ukuran}">
      </div>
    `;
  });

  document.getElementById('order-items').innerHTML =
      html || '<p class="text-muted fst-italic">Belum ada pesanan</p>';
  document.getElementById('total').innerText = total.toLocaleString('id-ID');

  // Enable/Disable tombol Checkout
  document.getElementById('checkoutBtn').disabled = orderItems.length === 0;
}

function increaseQty(index) { orderItems[index].qty++; renderOrder(); }
function decreaseQty(index) { if(orderItems[index].qty>1){orderItems[index].qty--; renderOrder();} }
function removeItem(index){ orderItems.splice(index,1); renderOrder(); }

document.querySelectorAll('.btn-add').forEach(btn=>{
  btn.addEventListener('click',()=>{
    currentVarian={
      varian_id:btn.dataset.varian,
      product_id:btn.dataset.product,
      name:btn.dataset.name,
      ukurans:JSON.parse(btn.dataset.ukurans)
    };
    let html='';
    currentVarian.ukurans.forEach(u=>{
      if(u.stok>0){
        html+=`<button type="button" class="btn w-100 mb-2 btn-orange-pill" onclick="selectUkuran(${u.id}, '${u.ukuran}', ${u.harga})" data-bs-dismiss="modal">${u.ukuran} - Rp ${u.harga.toLocaleString('id-ID')}</button>`;
      } else {
        html+=`<button type="button" class="btn w-100 mb-2 btn-secondary" disabled>${u.ukuran} - SOLD OUT</button>`;
      }
    });
    document.getElementById('ukuranOptions').innerHTML=html;
    new bootstrap.Modal(document.getElementById('ukuranModal')).show();
  });
});

function selectUkuran(ukuranId, ukuran, harga){
  // cek apakah item dengan varian & ukuran sudah ada
  const existingIndex = orderItems.findIndex(
    item => item.varian_id == currentVarian.varian_id && item.ukuran_id == ukuranId
  );

  if (existingIndex !== -1) {
    orderItems[existingIndex].qty++;
    showTempAlert("Produk sudah ada di keranjang, jumlah stok ditambahkan.");
  } else {
    let item={
      product_id:currentVarian.product_id,
      varian_id:currentVarian.varian_id,
      ukuran_id:ukuranId,
      name:currentVarian.name,
      ukuran:ukuran,
      harga:harga,
      qty:1
    };
    orderItems.push(item);
  }

  renderOrder();
}

// ðŸ”¹ Alert sementara (notif kecil warna merah & tengah atas)
function showTempAlert(message) {
  const alertBox = document.createElement("div");
  alertBox.className = "alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-3 fade show text-center shadow";
  alertBox.style.zIndex = "2000";
  alertBox.style.minWidth = "300px";
  alertBox.innerHTML = message;
  document.body.appendChild(alertBox);
  setTimeout(()=>{
    alertBox.classList.remove("show");
    setTimeout(()=>alertBox.remove(), 500);
  }, 2500);
}

// Validasi nomor HP
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const phoneInput = this.querySelector('input[name="buyer_phone"]');
    const phoneValue = phoneInput.value.replace(/\D/g,''); // hanya angka

    if (phoneValue.length < 9 || phoneValue.length > 13 || !/^\d+$/.test(phoneValue)) {
        e.preventDefault(); // stop form submit

        // Tampilkan alert
        const alertBox = document.getElementById('page-alert');
        alertBox.textContent = "Nomor HP harus 9â€“13 digit angka.";
        alertBox.classList.remove('d-none');
        alertBox.classList.add('show');

        setTimeout(() => {
            alertBox.classList.remove('show');
            alertBox.classList.add('d-none');
        }, 3000);

        phoneInput.focus();
    }
});
</script>
@endpush
