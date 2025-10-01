@extends('layouts.app')

@section('title', 'Cara Memesan Tiket')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-10">

    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-3xl font-bold text-gray-800">Cara Memesan Tiket</h1>
        <p class="mt-4 text-gray-600 max-w-2xl mx-auto">
            Ikuti langkah-langkah berikut untuk memesan tiket acara dengan mudah. Pastikan Anda sudah menyiapkan data diri yang lengkap dan metode pembayaran yang tersedia.
        </p>
    </div>

    <!-- Step List -->
    <div class="space-y-8">
        
        <!-- Step 1 -->
        <div class="bg-white rounded-xl shadow-lg p-6 flex items-start gap-4 hover:shadow-xl transition transform hover:-translate-y-1 fade-in">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-lg">1</div>
            <div>
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-calendar-check text-blue-500"></i> Pilih Acara
                </h3>
                <p class="text-gray-600">Buka halaman <a href="{{ url('/#upcoming-events') }}" class="text-blue-500 hover:underline">Daftar Acara</a> dan pilih acara yang ingin Anda hadiri.</p>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="bg-white rounded-xl shadow-lg p-6 flex items-start gap-4 hover:shadow-xl transition transform hover:-translate-y-1 fade-in">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center font-bold text-lg">2</div>
            <div>
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-ticket text-green-500"></i> Pilih Kategori Tiket
                </h3>
                <p class="text-gray-600">Tentukan kategori tiket sesuai kebutuhan (misalnya Early Bird, Presale, atau VIP) dan jumlah tiket yang diinginkan.</p>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="bg-white rounded-xl shadow-lg p-6 flex items-start gap-4 hover:shadow-xl transition transform hover:-translate-y-1 fade-in">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-purple-500 text-white flex items-center justify-center font-bold text-lg">3</div>
            <div>
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-user text-purple-500"></i> Isi Data Pengunjung
                </h3>
                <p class="text-gray-600">Masukkan data lengkap setiap pengunjung sesuai jumlah tiket yang dipesan, termasuk nama dan email.</p>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="bg-white rounded-xl shadow-lg p-6 flex items-start gap-4 hover:shadow-xl transition transform hover:-translate-y-1 fade-in">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-orange-500 text-white flex items-center justify-center font-bold text-lg">4</div>
            <div>
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-credit-card text-orange-500"></i> Lanjut ke Pembayaran
                </h3>
                <p class="text-gray-600">Klik tombol <strong>Checkout</strong> dan pilih metode pembayaran yang tersedia, seperti QRIS, transfer bank, atau e-wallet.</p>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="bg-white rounded-xl shadow-lg p-6 flex items-start gap-4 hover:shadow-xl transition transform hover:-translate-y-1 fade-in">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-pink-500 text-white flex items-center justify-center font-bold text-lg">5</div>
            <div>
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-envelope-open-text text-pink-500"></i> Konfirmasi Pembayaran
                </h3>
                <p class="text-gray-600">Setelah membayar, sistem akan otomatis memverifikasi transaksi Anda. Anda akan menerima tiket melalui email dalam bentuk PDF dan barcode.</p>
            </div>
        </div>
    </div>

    <!-- Note -->
    <div class="mt-12 p-5 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-700 flex items-start gap-3 fade-in">
        <i class="fa-solid fa-circle-info text-2xl mt-1"></i>
        <p><strong>Catatan:</strong> Pastikan email yang Anda masukkan benar, karena tiket akan dikirim ke alamat tersebut.</p>
    </div>
</div>

<!-- Animasi Scroll -->
<style>
.fade-in {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.6s ease-out;
}
.fade-in.show {
    opacity: 1;
    transform: translateY(0);
}
</style>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const elements = document.querySelectorAll(".fade-in");
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("show");
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    elements.forEach(el => observer.observe(el));
});
</script>
@endsection
