@extends('layouts.app')

@section('title', 'Review Pesanan')

@section('content')
<div class="container py-5" style="max-width:700px;">
    <div class="card shadow border-0 rounded-4">

        {{-- Header --}}
        <div class="card-header bg-orange text-white text-center rounded-top-4 py-3">
            <h2 class="fw-bold mb-0">Review Pesanan</h2>
        </div>

        <div class="card-body p-4 bg-white">

            {{-- Detail Pembeli --}}
            <h5 class="fw-bold mb-3">Data Pembeli</h5>
            <div class="row g-3 mb-4">

                <div class="col-md-6">
                    <div class="p-3 rounded bg-light border">
                        <span class="fw-semibold text-muted">
                            <i class="bi bi-person me-1"></i> Nama
                        </span><br>

                        {{ $orderData['buyer_name'] }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3 rounded bg-light border">
                        <span class="fw-semibold text-muted">
                            <i class="bi bi-envelope me-1"></i> Email
                        </span><br>

                        {{ $orderData['email'] }}
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="p-3 rounded bg-light border">
                        <span class="fw-semibold text-muted">
                            <i class="bi bi-telephone me-1"></i> No HP
                        </span><br>

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

                <div class="d-flex align-items-center p-3 mb-3 rounded border bg-white shadow-sm">

                    <div class="btn-orange-circle me-3">
                        <i class="bi bi-bag"></i>
                    </div>

                    <div class="w-100">

                        <p class="fw-semibold mb-1">
                            {{ $varian?->product?->name }} - {{ $varian?->varian }}
                        </p>

                        <small class="text-muted d-block">
                            <i class="bi bi-rulers me-1"></i>
                            {{ $ukuran?->ukuran ?? '-' }}
                        </small>

                        <small class="text-muted d-block">
                            <i class="bi bi-123 me-1"></i>
                            x{{ $item['quantity'] }}
                        </small>

                        <p class="fw-semibold text-orange text-end mb-0">
                            Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                        </p>

                    </div>

                </div>

            @endforeach

            {{-- Ringkasan Pembayaran --}}
            <h5 class="fw-bold mt-4 mb-3">Ringkasan Pembayaran</h5>

            @php
                // Logika tier sama persis dengan TicketController::calcServiceTax & serviceLabel
                $totalAmount = collect($orderData['items'])->sum('subtotal');

                if ($totalAmount == 0) {
                    $serviceTax = 0;
                    $serviceLabel = 'Gratis';
                } elseif ($totalAmount <= 500000) {
                    $serviceTax = max(2500, ($totalAmount * 5) / 100);
                    $calculated = round(($totalAmount * 5) / 100);
                    $serviceLabel = ($serviceTax == 2500 && $calculated < 2500) ? 'Minimal Rp2.500' : '5%';
                } elseif ($totalAmount <= 1500000) {
                    $serviceTax = ($totalAmount * 3) / 100;
                    $serviceLabel = '3%';
                } elseif ($totalAmount <= 2500000) {
                    $serviceTax = ($totalAmount * 2) / 100;
                    $serviceLabel = '2%';
                } else {
                    $serviceTax = 50000;
                    $serviceLabel = 'Flat Rp50.000';
                }

                $grandTotal = $totalAmount + $serviceTax;
            @endphp

            <div class="p-3 rounded bg-light border">

                <div class="d-flex justify-content-between mb-2">
                    <span>Total Item</span>
                    <span>{{ collect($orderData['items'])->sum('quantity') }}</span>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span>Total Produk</span>
                    <span>
                        Rp {{ number_format($totalAmount, 0, ',', '.') }}
                    </span>
                </div>

                <div class="d-flex justify-content-between mb-2 text-muted">
                    <span>
                        Biaya Layanan
                        @if(!empty($serviceLabel))
                            ({{ $serviceLabel }})
                        @endif
                    </span>
                    <span>
                        Rp {{ number_format($serviceTax, 0, ',', '.') }}
                    </span>
                </div>

                <hr>

                <div class="d-flex justify-content-between fw-bold text-orange fs-5 mt-3">
                    <span>Total Bayar</span>
                    <span>
                        Rp {{ number_format($grandTotal, 0, ',', '.') }}
                    </span>
                </div>

            </div>

            {{-- Informasi tambahan --}}
            <div class="alert alert-info mt-4 mb-0">
                <small>
                    <i class="bi bi-info-circle me-1"></i>
                    Total pembayaran sudah termasuk biaya layanan platform.
                </small>
            </div>

            {{-- Tombol Aksi --}}
            <form
                method="POST"
                action="{{ route('merch.checkout') }}"
                class="mt-4">

                @csrf

                {{-- simpan event agar tidak hilang --}}
                @if(isset($event))
                    <input
                        type="hidden"
                        name="event_id"
                        value="{{ $event->id }}">
                @endif

                {{-- Data pembeli --}}
                <input
                    type="hidden"
                    name="buyer_name"
                    value="{{ $orderData['buyer_name'] }}">

                <input
                    type="hidden"
                    name="email"
                    value="{{ $orderData['email'] }}">

                <input
                    type="hidden"
                    name="buyer_phone"
                    value="{{ $orderData['buyer_phone'] }}">

                {{-- Detail item --}}
                @foreach($orderData['items'] as $i => $item)

                    <input
                        type="hidden"
                        name="items[{{ $i }}][product_id]"
                        value="{{ $item['product_id'] }}">

                    <input
                        type="hidden"
                        name="items[{{ $i }}][varian_id]"
                        value="{{ $item['varian_id'] }}">

                    <input
                        type="hidden"
                        name="items[{{ $i }}][ukuran_id]"
                        value="{{ $item['ukuran_id'] ?? '' }}">

                    <input
                        type="hidden"
                        name="items[{{ $i }}][quantity]"
                        value="{{ $item['quantity'] }}">

                    <input
                        type="hidden"
                        name="items[{{ $i }}][price]"
                        value="{{ $item['price'] }}">

                    <input
                        type="hidden"
                        name="items[{{ $i }}][subtotal]"
                        value="{{ $item['subtotal'] }}">

                @endforeach


                <div class="d-flex justify-content-end gap-2 mt-4">

                    {{-- tombol kembali --}}
                    @if(isset($event))

                        <a
                            href="{{ route('merch.index', $event->id) }}"
                            class="btn btn-outline-secondary rounded-pill px-4">

                            <i class="bi bi-arrow-left me-1"></i>
                            Kembali

                        </a>

                    @endif


                    {{-- bayar --}}
                    <button
                        type="submit"
                        class="btn-orange-pill">

                        <i class="bi bi-wallet2 me-1"></i>
                        Bayar Sekarang

                    </button>

                </div>

            </form>

        </div>
    </div>
</div>
@endsection