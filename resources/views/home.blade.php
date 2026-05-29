@extends('layouts.app')

@section('title', 'RSCTicket')

@section('content')
<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 py-8 bg-gray-50 text-gray-800 fade-in">

  <!-- 🔒 CTA TAMBAH EVENT (PALING ATAS - PAGAR) -->
  <div class="mb-10" data-aos="fade-down" data-aos-duration="800">
    <div class="bg-gradient-to-r from-orange-400 to-orange-500 rounded-xl shadow-lg p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-white">
      
      <div>
        <h2 class="text-xl sm:text-2xl font-extrabold mb-1">
          Punya event seru?
        </h2>
        <p class="text-sm sm:text-base opacity-90">
          Buat & publikasikan event Anda sekarang dan jangkau lebih banyak penonton.
        </p>
      </div>

      {{-- PAGAR: HALAMAN TAMBAH EVENT BELUM DIBUAT --}}
      <a href="{{ route('eo.register') }}"
        class="inline-flex items-center gap-2 px-6 py-3 rounded-full
                bg-white text-orange-500 text-sm font-bold
                hover:bg-orange-50 transition
                shadow-md opacity-90">
        <i class="fa-solid fa-plus"></i>
        Buat Event
      </a>


    </div>
  </div>


  <!-- 🔹 SLIDER BANNER -->
  <div class="mb-12" data-aos="fade-up" data-aos-duration="800">
    <div class="swiper mySwiper rounded-xl overflow-hidden shadow-lg bg-white">
      <div class="swiper-wrapper">

        @foreach(['slider2.jpg','slider3.JPG','slider4.JPG'] as $slider)
        <div class="swiper-slide relative group">
          <img src="{{ asset($slider) }}"
               class="w-full h-60 sm:h-72 md:h-[22rem] lg:h-[26rem] object-cover transition-transform duration-500 group-hover:scale-105"
               alt="Banner">
          <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
        </div>
        @endforeach

      </div>
      <div class="swiper-pagination mt-2"></div>
    </div>
  </div>

  <!-- 🔸 CTA CARA MEMESAN -->
  <div class="mb-12" data-aos="fade-up" data-aos-duration="900" data-aos-delay="200">
    <a href="{{ url('/cara-memesan') }}" class="block transition duration-500 hover:scale-[1.02]">
      <img src="{{ asset('banner-cara-memesan.png') }}"
           alt="Cara Memesan Tiket"
           class="rounded-xl w-full object-cover shadow-md hover:shadow-xl">
    </a>
  </div>

  <!-- 🔹 UPCOMING SHOWS -->
  <div id="upcoming-events" class="mb-6" data-aos="fade-right">
    <h1 class="text-2xl sm:text-3xl font-extrabold flex items-center gap-2 text-gray-900">
      <i class="fa-solid fa-calendar-days text-orange-500"></i>
      Upcoming Shows
    </h1>
    <div class="h-1 w-24 bg-orange-500 mt-2 rounded"></div>
  </div>

  <!-- 🔹 GRID EVENT -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 justify-items-center">

    @forelse($events as $event)
    <div class="bg-white rounded-xl shadow-sm hover:shadow-lg w-full max-w-sm flex flex-col overflow-hidden transition transform hover:-translate-y-1"
         data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">

      <img src="{{ asset($event->poster) }}"
        alt="Poster Event"
        class="w-full aspect-video object-cover bg-gray-200">

      <div class="flex flex-col flex-1 p-4 sm:p-6">
        <h2 class="text-base sm:text-lg font-bold text-gray-900 mb-1">
          {{ $event->title }}
        </h2>

        <p class="text-sm text-gray-500 mb-1">
          <i class="fa-solid fa-location-dot text-orange-500 me-1"></i>
          {{ $event->location }}
        </p>

        <p class="text-sm text-gray-500 mb-4">
          <i class="fa-solid fa-calendar text-orange-500 me-1"></i>
          {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d F Y') }}
        </p>

        <div class="mt-auto">
          <a href="{{ route('info.show', $event->id) }}"
             class="inline-block px-4 py-2 rounded-full bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600 transition shadow">
            More Info
          </a>
        </div>
      </div>
    </div>
    @empty
      <p class="text-gray-500 col-span-full">Belum ada event yang tersedia.</p>
    @endforelse

  </div>
</div>

<!-- Swiper Init Script -->
<script>
  const swiper = new Swiper('.mySwiper', {
    loop: true,
    slidesPerView: 2,
    spaceBetween: 16,
    autoplay: {
      delay: 4000,
    },
    pagination: {
      el: '.swiper-pagination',
      clickable: true,
    },
    breakpoints: {
      0: { slidesPerView: 1 },
      768: { slidesPerView: 2 },
    }
  });
</script>

<!-- AOS Init -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({
    once: true,
    offset: 50
  });
</script>
@endsection
