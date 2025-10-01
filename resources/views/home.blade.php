@extends('layouts.app')

@section('title', 'RSCTicket')

@section('content')
<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 py-8 text-gray-100 fade-in">

  <!-- 🔹 SLIDER BANNER -->
  <div class="mb-12" data-aos="fade-up" data-aos-duration="800">
    <div class="swiper mySwiper rounded-xl overflow-hidden shadow-xl">
      <div class="swiper-wrapper">
        <!-- Slide 1 -->
        <div class="swiper-slide relative group">
          <img src="{{ asset('slider1.jpg') }}" 
               class="w-full h-60 sm:h-72 md:h-[22rem] lg:h-[26rem] object-cover transition-transform duration-500 group-hover:scale-105" 
               alt="Banner 1">
          <div class="absolute inset-0 flex flex-col justify-end p-6 bg-gradient-to-t">
            <h2 class="text-white text-2xl sm:text-3xl font-bold mb-2"></h2>
          </div>
        </div>

        <!-- Slide 2 -->
        <div class="swiper-slide relative group">
          <img src="{{ asset('slider2.jpg') }}" 
               class="w-full h-60 sm:h-72 md:h-[22rem] lg:h-[26rem] object-cover transition-transform duration-500 group-hover:scale-105" 
               alt="Banner 2">
          <div class="absolute inset-0 flex flex-col justify-end p-6 bg-gradient-to-t">
            <h2 class="text-white text-2xl sm:text-3xl font-bold mb-2"></h2>
          </div>
        </div>

        <!-- Slide 3 -->
        <div class="swiper-slide relative group">
          <img src="{{ asset('slider3.jpg') }}" 
               class="w-full h-60 sm:h-72 md:h-[22rem] lg:h-[26rem] object-cover transition-transform duration-500 group-hover:scale-105" 
               alt="Banner 3">
          <div class="absolute inset-0 flex flex-col justify-end p-6 bg-gradient-to-t">
            <h2 class="text-white text-2xl sm:text-3xl font-bold mb-2"></h2>
          </div>
        </div>
      </div>
      <div class="swiper-pagination mt-2"></div>
    </div>
  </div>

  <!-- 🔸 CTA: Cara Memesan -->
  <div class="mb-12" data-aos="fade-up" data-aos-duration="900" data-aos-delay="200">
    <a href="{{ url('/cara-memesan') }}" class="block transform transition duration-500 hover:scale-[1.02]">
      <img src="{{ asset('banner-cara-memesan.png') }}" 
           alt="Cara Memesan Tiket" 
           class="rounded-xl w-full object-cover shadow-lg hover:shadow-2xl">
    </a>
  </div>

  <!-- 🔹 UPCOMING SHOWS -->
  <div id="upcoming-events" class="mb-6" data-aos="fade-right" data-aos-duration="800">
    <h1 class="text-left text-2xl sm:text-3xl font-extrabold text-white flex items-center gap-2">
      <i class="fa-solid fa-calendar-days text-orange-400"></i>
      Upcoming Shows
    </h1>
    <div class="h-1 w-24 bg-orange-500 mt-2 rounded"></div>
  </div>

  <!-- 🔹 Grid Event Card Dinamis -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 justify-items-center">
    @forelse($events as $event)
      <div class="event-card bg-gray-800 rounded-xl shadow-md w-full max-w-sm flex flex-col overflow-hidden transform transition duration-300 hover:shadow-xl hover:-translate-y-1"
           data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
        <img src="{{ asset('storage/' . $event->poster) }}" 
             alt="{{ $event->title }}" 
             class="w-full aspect-square object-cover bg-gray-700">
        <div class="flex flex-col flex-1 p-4 sm:p-6">
          <h2 class="event-title text-base sm:text-lg font-bold text-white mb-1">{{ $event->title }}</h2>
          <p class="text-gray-400 text-sm mb-1"><i class="fa-solid fa-location-dot text-orange-400 me-1"></i>{{ $event->location }}</p>
          <p class="text-gray-400 text-sm mb-4"><i class="fa-solid fa-calendar text-orange-400 me-1"></i>{{ \Carbon\Carbon::parse($event->date)->translatedFormat('d F Y') }}</p>
          <div class="mt-auto">
            <a href="{{ route('info.show', ['id' => $event->id]) }}"
               class="h-3 btn-orange-pill shadow-md">
              More Info
            </a>
          </div>
        </div>
      </div>
    @empty
      <p class="text-gray-400 col-span-full" data-aos="fade-up">Belum ada event yang tersedia.</p>
    @endforelse
  </div>

</div>

<!-- Swiper Init Script -->
<script>
  const swiper = new Swiper('.mySwiper', {
    loop: true,
    slidesPerView: 2,
    spaceBetween: 16,
    slidesPerGroup: 1,
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