@extends('layouts.app')

@section('title', 'Pembelian Merchandise')
<link href="{{ asset('css/dark-theme.css') }}" rel="stylesheet">

@section('content')
<div id="page-alert" class="alert alert-danger alert-dismissible fade d-none text-center rounded-3">
    Nomor HP harus 9-13 digit angka.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 py-8">
  <div class="container py-4">
    <div class="row g-4">

      {{-- ðŸ“Œ Kiri: Daftar Produk + Deskripsi --}}
      <div class="col-lg-8">
        {{-- Daftar Merchandise --}}
        <div class="card shadow-sm border-0 rounded-3 mb-4">
          <div class="card-header fw-semibold fs-5">
            Daftar Merchandise
          </div>
          <div class="card-body">
            <div class="row">
              @foreach($varians as $varian)
              @php
                $hargaList = $varian->ukurans->pluck('harga')->filter()->toArray();
                $minHarga = $hargaList ? min($hargaList) : 0;
                $maxHarga = $hargaList ? max($hargaList) : 0;
              @endphp
              <div class="col-md-6 col-lg-4 mb-4 d-flex">
                <div class="card h-100 shadow-sm border-0 rounded-3 w-100 d-flex flex-column overflow-hidden">

                {{-- ðŸ“· Carousel Foto Produk --}}
                <div id="carousel-{{ $varian->id }}" class="carousel slide" data-bs-ride="carousel">
                  {{-- indikator --}}
                  @if(!empty($varian->images) && count($varian->images) > 1)
                    <div class="carousel-indicators">
                      @foreach($varian->images as $idx => $img)
                        <button type="button" data-bs-target="#carousel-{{ $varian->id }}" data-bs-slide-to="{{ $idx }}"
                          class="@if($idx===0) active @endif" aria-current="@if($idx===0) true @endif"></button>
                      @endforeach
                    </div>
                  @endif
                
                  <div class="carousel-inner">
                    @forelse($varian->images ?? [] as $idx => $img)
                      <div class="carousel-item @if($idx === 0) active @endif">
                        <div class="product-img-wrapper">
                          <img src="{{ asset('images/merch/' . $img->url) }}" class="d-block w-100" alt="Produk">
                        </div>
                      </div>
                    @empty
                      <div class="carousel-item active">
                        <div class="product-img-wrapper">
                          <img src="https://via.placeholder.com/300x300" class="d-block w-100" alt="Placeholder">
                        </div>
                      </div>
                    @endforelse
                  </div>
                
                  {{-- tombol next/prev --}}
                  @if(!empty($varian->images) && count($varian->images) > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#carousel-{{ $varian->id }}" data-bs-slide="prev">
                      <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carousel-{{ $varian->id }}" data-bs-slide="next">
                      <span class="carousel-control-next-icon"></span>
                    </button>
                  @endif
                </div>


                  {{-- Ã°Å¸â€œÅ’ Info Produk --}}
                  <div class="card-body d-flex flex-column">
                    <h5 class="card-title fw-bold text-black mb-1">
                      {{ $varian->product->name }} - {{ $varian->varian }}
                    </h5>

                    {{-- Harga --}}
                    <p class="fw-semibold text-orange mb-2">
                      @if($minHarga === $maxHarga)
                        Rp {{ number_format($minHarga, 0, ',', '.') }}
                      @else
                        Rp {{ number_format($minHarga, 0, ',', '.') }} Ã¢â‚¬â€œ Rp {{ number_format($maxHarga, 0, ',', '.') }}
                      @endif
                    </p>

                    {{-- Tombol Pilih --}}
                    <button type="button"
                      class="mt-auto btn btn-outline-orange w-100 fw-semibold btn-add"
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

        {{-- Ã°Å¸â€œÅ’ Card Deskripsi Produk --}}
         {{-- Deskripsi Produk --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
      <div class="card-header fw-semibold fs-5">
        Deskripsi Produk
      </div>
      <div class="card-body" style="white-space: pre-line;">
        @if($varians->isNotEmpty())
          <h6 class="fw-bold text-white mb-2">{{ $varians->first()->product->name }}</h6>
          <p class="mb-0">{{ $varians->first()->product->description }}</p>
        @else
          <p class="text-muted fst-italic">Tidak ada deskripsi produk</p>
        @endif
      </div>
    </div>
  </div> {{-- ðŸ”¥ ini penutup col-lg-8, sebelumnya hilang --}}

  {{-- ðŸ“Œ Kanan: Form Checkout --}}
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
            <input type="email" name="email" class="form-control rounded-pill" placeholder="Masukkan email anda" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" name="buyer_name" class="form-control rounded-pill" placeholder="Masukkan nama lengkap" required>
          </div>
          <div class="mb-3">
            <label class="form-label">No HP</label>
            <input type="text" name="buyer_phone" class="form-control rounded-pill" placeholder="Masukkan nomor HP aktif" required>
          </div>

          <h5 class="mt-4 text-orange">Pesanan Anda</h5>
          <div id="order-items" class="mt-2"></div>

          <div class="mt-3 fw-bold border-top border-secondary pt-2 d-flex justify-content-between align-items-center">
            <span>Total Bayar</span>
            <span class="text-orange">Rp <span id="total" class="text-orange">0</span></span>
          </div>

          <div class="text-end">
            <button type="submit" id="checkoutBtn" class="btn-orange-pill px-5 py-2 mt-3" disabled>
              Checkout
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

{{-- Ã°Å¸â€œÅ’ Modal Pilih Ukuran --}}
<div class="modal fade" id="ukuranModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card border-0 shadow-lg">
      <div class="modal-header border-0 d-flex justify-content-between align-items-center">
        <h4 class="modal-title fw-bold text-black" id="modalTitle">Pilih Ukuran</h4>
        <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        {{-- Judul produk & harga --}}
        <h5 class="fw-bold text-black" id="modalProductName"></h5>
        <p class="fw-semibold text-orange mb-3" id="modalProductPrice"></p>

        {{-- Pilih ukuran --}}
        <div class="mb-3">
          <h6 class="fw-bold text-black">Pilih Ukuran</h6>
          <div id="ukuranOptions" class="d-flex flex-wrap gap-2"></div>
        </div>

        {{-- Harga + tombol pilih --}}
        <div id="hargaOption" class="mt-3 d-none"></div>
      </div>
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

    document.getElementById('modalProductName').textContent = currentVarian.name;
    document.getElementById('modalProductPrice').textContent = "";
    let html='';
    currentVarian.ukurans.forEach(u=>{
      if(u.stok>0){
        html+=`<button type="button" class="btn btn-outline-orange btn-sm pilih-ukuran" onclick="showHarga(${u.id}, '${u.ukuran}', ${u.harga}, this)">${u.ukuran}</button>`;
      } else {
        html+=`<button type="button" class="btn btn-sm btn-secondary" disabled>${u.ukuran} - SOLD OUT</button>`;
      }
    });
    document.getElementById('ukuranOptions').innerHTML=html;
    document.getElementById('hargaOption').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('ukuranModal')).show();
  });
});

function showHarga(ukuranId, ukuran, harga, el){
  document.querySelectorAll('#ukuranOptions .btn').forEach(b=>b.classList.remove('active'));
  el.classList.add('active');
  const hargaDiv = document.getElementById('hargaOption');
  hargaDiv.classList.remove('d-none');
  hargaDiv.innerHTML = `
    <p class="fw-semibold">Harga: Rp ${harga.toLocaleString('id-ID')}</p>
    <button type="button" class="btn btn-orange-pill w-100" onclick="selectUkuran(${ukuranId}, '${ukuran}', ${harga})" data-bs-dismiss="modal">
      Pilih ${ukuran}
    </button>
  `;
  document.getElementById('modalProductPrice').textContent = "Rp " + harga.toLocaleString('id-ID');
}

function selectUkuran(ukuranId, ukuran, harga){
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
  document.getElementById('checkoutForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function showTempAlert(message) {
  const alertBox = document.createElement("div");
  alertBox.className = "alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-3 fade show text-center shadow";
  alertBox.style.zIndex = "2000";
  alertBox.style.minWidth = "300px";
  alertBox.innerHTML = message;
  document.body.appendChild(alertBox);
  setTimeout(()=>{ alertBox.classList.remove("show"); setTimeout(()=>alertBox.remove(), 500); }, 2500);
}

document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const phoneInput = this.querySelector('input[name="buyer_phone"]');
    const phoneValue = phoneInput.value.replace(/\D/g,'');
    if (phoneValue.length < 9 || phoneValue.length > 13 || !/^\d+$/.test(phoneValue)) {
        e.preventDefault();
        const alertBox = document.getElementById('page-alert');
        alertBox.textContent = "Nomor HP harus 9Ã¢â‚¬â€œ13 digit angka.";
        alertBox.classList.remove('d-none');
        alertBox.classList.add('show');
        setTimeout(() => { alertBox.classList.remove('show'); alertBox.classList.add('d-none'); }, 3000);
        phoneInput.focus();
    }
});
</script>
@endpush
