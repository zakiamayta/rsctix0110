@extends('layouts.app')

@section('title', 'Daftar Layanan Event')

@section('content')
<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 pt-10 pb-4 bg-gray-50 text-gray-800">
  <!-- HEADER -->
  <div class="mb-12 text-center" data-aos="fade-down">
    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-3">
      Pilih Layanan Event
    </h1>
    <p class="text-gray-600 max-w-2xl mx-auto">
      Tentukan jenis layanan event yang sesuai dengan kebutuhan Anda.
      Setiap layanan memiliki fitur dan keuntungan yang berbeda.
    </p>
  </div>

  <!-- GRID LAYANAN -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">

    <!-- FREE EVENT -->
    <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition p-6 flex flex-col"
         data-aos="fade-up">

      <div class="mb-4">
        <span class="inline-block px-3 py-1 text-sm rounded-full bg-green-100 text-green-600 font-semibold">
          Gratis
        </span>
      </div>

      <h2 class="text-xl font-bold mb-2 text-gray-900">
        Free Event
      </h2>

      <p class="text-gray-600 mb-4">
        Cocok untuk event komunitas, sosial, atau acara gratis tanpa penjualan tiket.
      </p>

      <ul class="text-sm text-gray-600 space-y-2 mb-6">
        <li>✔ Publikasi event di platform RSCTicket</li>
        <li>✔ Informasi event & jadwal</li>
        <li>✔ Tanpa sistem pembayaran</li>
        <li>✔ Manajemen peserta manual</li>
      </ul>

      <div class="mt-auto">
        {{-- PAGAR --}}
        <a href="#"
           class="block text-center px-5 py-3 rounded-full
                  bg-green-500 text-white font-semibold
                  hover:bg-green-600 transition
                  opacity-80 cursor-not-allowed"
           title="Halaman belum tersedia">
          Lanjutkan
        </a>
      </div>
    </div>

    <!-- EVENT BERBAYAR -->
    <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition p-6 flex flex-col border-2 border-orange-400"
         data-aos="fade-up" data-aos-delay="150">

      <div class="mb-4">
        <span class="inline-block px-3 py-1 text-sm rounded-full bg-orange-100 text-orange-500 font-semibold">
          Premium
        </span>
      </div>

      <h2 class="text-xl font-bold mb-2 text-gray-900">
        Event Berbayar
      </h2>

      <p class="text-gray-600 mb-4">
        Solusi lengkap untuk event profesional dengan penjualan tiket online.
      </p>

      <ul class="text-sm text-gray-600 space-y-2 mb-6">
        <li>✔ Sistem penjualan tiket online</li>
        <li>✔ QR Code tiket & validasi</li>
        <li>✔ Manajemen transaksi & peserta</li>
        <li>✔ Laporan penjualan real-time</li>
      </ul>

      <div class="mt-auto">
        {{-- PAGAR --}}
        <a href="#"
           class="block text-center px-5 py-3 rounded-full
                  bg-orange-400 text-white font-semibold
                  hover:bg-orange-500 transition
                  opacity-80 cursor-not-allowed"
           title="Halaman belum tersedia">
          Lanjutkan
        </a>
      </div>
    </div>

  </div>

</div>

<!-- AOS -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({
    once: true,
    offset: 60
  });
</script>
@endsection
