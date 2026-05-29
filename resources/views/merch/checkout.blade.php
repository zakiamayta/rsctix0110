@extends('layouts.app')

@section('title', 'Review Pesanan')
<link href="{{ asset('css/theme-dark.css') }}" rel="stylesheet">

@section('content')
<div class="container-fluid py-5" style="max-width: 700px; margin:auto; padding-left:12px; padding-right:12px;">
  <div class="card shadow-lg border-0 rounded-3 fade-in">
    
    {{-- Header --}}
    <div class="card-header text-center">
      <h2 class="text-black fs-2 mb-0 fw-bold">Review Pesanan</h2>
    </div>

    <div class="card-body p-4">
      {{-- Detail Pembeli --}}
      <h5 class="fw-bold mb-3">Data Pembeli</h5>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="p-3 rounded bg-card">
            <span class="fw-semibold"><i class="bi bi-person me-1"></i> Nama:</span><br>
            {{ $orderData['buyer_name'] }}
          </div>
        </div>
        <div class="col-md-6">
          <div class="p-3 rounded bg-card">
            <span class="fw-semibold"><i class="bi bi-envelope me-1"></i> Email:</span><br>
            {{ $orderData['email'] }}
          </div>
        </div>
        <div class="col-md-12">
          <div class="p-3 rounded bg-card">
            <span class="fw-semibold"><i class="bi bi-telephone me-1"></i> No HP:</span><br>
            {{ $orderData['buyer_phone'] }}
          </div>
        </div>
      </div>

{{-- Item Pesanan --}}
<h5 class="fw-bold mb-3">Pesanan</h5>

@foreach($orderData['items'] as $item)

    @php
        $varian = \App\Models\ProductVarian::with('product')
            ->find($item['varian_id']);

        $ukuran = null;

        if (!empty($item['ukuran_id'])) {
            $ukuran = \App\Models\ProductUkuran::find($item['ukuran_id']);
        }
    @endphp

    <div class="d-flex align-items-center p-3 mb-3 rounded bg-card shadow-sm">

        <div class="btn-orange-circle me-3">
            <i class="bi bi-bag"></i>
        </div>

        <div class="w-100">

            <p class="fw-semibold mb-1">
                {{ $varian?->product?->name }} - {{ $varian?->varian }}
            </p>

            <small class="text-black d-block">
                <i class="bi bi-rulers me-1"></i>
                {{ $ukuran?->ukuran ?? '-' }}
            </small>

            <small class="text-black d-block">
                <i class="bi bi-123 me-1"></i>
                x{{ $item['quantity'] }}
            </small>

            <p class="fw-semibold text-orange-500 text-end mb-0">
                Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
            </p>

        </div>

    </div>

@endforeach

      {{-- Total --}}
      <h5 class="fw-bold mt-4 mb-3">Ringkasan Pembayaran</h5>
      <div class="p-3 rounded bg-card">
        <div class="d-flex justify-content-between mb-2">
          <span>Total Item</span>
          <span>{{ collect($orderData['items'])->sum('quantity') }}</span>
        </div>
        @php
    $totalAmount = collect($orderData['items'])->sum('subtotal');

    $totalQty = collect($orderData['items'])->sum('quantity');

    $servicePercent = $totalQty == 1 ? 10 : 7;

    $serviceTax = round(($totalAmount * $servicePercent) / 100);

    $grandTotal = $totalAmount + $serviceTax;
@endphp

<div class="d-flex justify-content-between mb-2">
    <span>Total Produk</span>
    <span>
        Rp {{ number_format($totalAmount, 0, ',', '.') }}
    </span>
</div>

<div class="d-flex justify-content-between mb-2">
    <span>Biaya Layanan ({{ $servicePercent }}%)</span>
    <span>
        Rp {{ number_format($serviceTax, 0, ',', '.') }}
    </span>
</div>

<div class="border-top pt-2 mt-2 d-flex justify-content-between fw-bold text-orange-500">
    <span>Total Bayar</span>
    <span>
        Rp {{ number_format($grandTotal, 0, ',', '.') }}
    </span>
</div>
      </div>

      {{-- Tombol Aksi --}}
      <form method="POST" action="{{ route('merch.checkout') }}" class="mt-4">
        @csrf
        {{-- Hidden fields --}}
        <input type="hidden" name="buyer_name" value="{{ $orderData['buyer_name'] }}">
        <input type="hidden" name="email" value="{{ $orderData['email'] }}">
        <input type="hidden" name="buyer_phone" value="{{ $orderData['buyer_phone'] }}">
        @foreach($orderData['items'] as $i => $item)

  <input type="hidden"
         name="items[{{ $i }}][product_id]"
         value="{{ $item['product_id'] }}">

  <input type="hidden"
         name="items[{{ $i }}][varian_id]"
         value="{{ $item['varian_id'] }}">

  <input type="hidden"
         name="items[{{ $i }}][ukuran_id]"
         value="{{ $item['ukuran_id'] ?? '' }}">

  <input type="hidden"
         name="items[{{ $i }}][quantity]"
         value="{{ $item['quantity'] }}">

  <input type="hidden"
         name="items[{{ $i }}][price]"
         value="{{ $item['price'] }}">

  <input type="hidden"
         name="items[{{ $i }}][subtotal]"
         value="{{ $item['subtotal'] }}">

@endforeach

        <div class="d-flex justify-content-between mt-4">
          <a href="{{ route('merch.index') }}" class="btn btn-cancel px-4">
            <i class="bi bi-arrow-left me-1"></i> Kembali
          </a>
          <button type="submit" class="btn-orange-pill px-4 text-dark fw-semibold">
            <i class="bi bi-wallet2 me-1"></i> Bayar Sekarang
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection