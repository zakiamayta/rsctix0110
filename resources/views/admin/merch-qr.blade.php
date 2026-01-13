@extends('layouts.admin')

@section('content')
{{-- CONTAINER: Menggunakan flexbox untuk centering vertikal (min-h-screen asumsi diambil dari body/layout, jadi kita fokus pada centering di main content) --}}
<div class="container mx-auto py-8 px-4 sm:px-6 lg:px-8 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-2xl">
        
        {{-- CARD UTAMA: Info Transaksi + QR --}}
        {{-- Meniru card dari file kedua: bg-white, rounded-2xl, shadow-xl. Ditambah shadow-2xl untuk efek 'hover-lift' dan border-blue untuk konsistensi. --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-xl hover:shadow-2xl transition duration-300 ease-in-out">
            
            {{-- HEADER CARD: Menggunakan warna Blue-600 untuk meniru gradient-blue --}}
            <div class="bg-blue-600 text-white text-center py-4 rounded-t-2xl">
                <h4 class="mb-0 text-xl font-semibold">QR Code Merchandise</h4>
            </div>

            <div class="p-8 text-center">

                {{-- INFORMASI TRANSAKSI --}}
                <div class="flex justify-center mb-6">
                    <div class="w-full max-w-sm">
                        {{-- Menggunakan list-group-flush simulasi dengan div flex --}}
                        <div class="space-y-2 text-left">
                            <div class="flex justify-between p-2 border-b border-gray-100 hover:bg-blue-50 transition duration-150 rounded">
                                <span class="font-medium text-gray-500">Kode Unik Transaksi:</span>
                                <span class="uppercase font-bold text-gray-800">{{ $transaction->kode_unik }}</span>
                            </div>
                            <div class="flex justify-between p-2 border-b border-gray-100 hover:bg-blue-50 transition duration-150 rounded">
                                <span class="font-medium text-gray-500">Email:</span>
                                <span class="text-gray-600">{{ $transaction->email }}</span>
                            </div>
                            <div class="flex justify-between items-center p-2 hover:bg-blue-50 transition duration-150 rounded">
                                <span class="font-medium text-gray-500">Status Pembayaran:</span>
                                {{-- Badge Status: Meniru warna dari Blade sebelumnya --}}
                                @php
                                    $status_class = match ($transaction->payment_status) {
                                        'paid' => 'bg-green-600',
                                        'failed' => 'bg-red-600',
                                        default => 'bg-purple-600',
                                    };
                                @endphp
                                <span class="px-3 py-1 text-sm font-semibold rounded-full text-white {{ $status_class }}">
                                    {{ strtoupper($transaction->payment_status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- QR CODE --}}
                @if($transaction->qr_code)
                    <div class="my-6">
                        <div class="bg-white p-6 rounded-lg shadow-lg inline-block border border-gray-200">
                            <img src="{{ asset($transaction->qr_code) }}"
                                 alt="QR Code Merchandise"
                                 class="w-48 h-48 sm:w-64 sm:h-64 rounded" />
                        </div>

                        {{-- TOMBOL AKSI --}}
                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            <a href="{{ asset($transaction->qr_code) }}"
                               target="_blank"
                               class="inline-flex items-center bg-blue-500 hover:bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg shadow-md transition duration-200">
                                🔍 Lihat QR Code
                            </a>
                            <a href="{{ asset($transaction->qr_code) }}"
                               download="qr_merch_{{ $transaction->kode_unik }}.png"
                               class="inline-flex items-center bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded-lg shadow-md transition duration-200">
                                ⬇️ Unduh QR Code
                            </a>
                        </div>

                        {{-- LINK DI BAWAH QR --}}
                        <div class="mt-4">
                            <p class="text-gray-500 text-sm mb-2">Scan QR di atas atau buka link berikut:</p>
                            <a href="{{ asset($transaction->qr_code) }}"
                               target="_blank"
                               class="inline-block bg-blue-100 text-blue-700 font-semibold px-3 py-2 rounded-lg text-xs break-all hover:bg-blue-200 transition duration-150">
                                {{ asset($transaction->qr_code) }}
                            </a>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500 italic">QR Code belum tersedia.<br>Silakan cek kembali setelah pembayaran terverifikasi.</p>
                @endif
            </div>
        </div>

        ---

        {{-- CARD TAMBAHAN: DETAIL MERCHANDISE --}}
        @if($transaction->details && $transaction->details->count())
            <div class="bg-white border border-gray-200 rounded-2xl shadow-lg mt-8 hover:shadow-xl transition duration-300 ease-in-out">
                <div class="bg-gray-50 text-center border-b border-gray-200 py-3 rounded-t-2xl">
                    <h5 class="mb-0 text-lg font-semibold text-gray-700">Detail Merchandise</h5>
                </div>
                <div class="p-0">
                    {{-- Simulasi table table-hover dan table-borderless --}}
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($transaction->details as $detail)
                                <tr class="hover:bg-blue-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-center">{{ $detail->product->name ?? 'Produk tidak ditemukan' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ $detail->quantity }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Rp {{ number_format($detail->price, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection