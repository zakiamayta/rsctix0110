<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

  <link rel="icon" type="image/svg" href="{{ asset('logoplain.svg') }}">

  <title>@yield('title', 'RSCtix')</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <link rel="stylesheet" href="{{ asset('css/light-theme.css') }}?v={{ time() }}">

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Swiper -->
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8fafc;
    }
  </style>

  @stack('styles')
</head>

<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col">

  {{-- Navbar --}}
  @include('layouts.navbar')

  {{-- Main Content --}}
  <main class="flex-grow">
    @yield('content')
  </main>

  {{-- Footer --}}
  <footer class="bg-white pt-10 pb-4 border-top shadow-sm">
    <div class="container px-4">
      <div class="row g-4">

        <!-- Logo & Deskripsi -->
        <div class="col-md-4">
          <h3 class="fw-bold text-orange mb-2">
            <a href="{{ url('/') }}" class="text-decoration-none text-orange">RSCtix</a>
          </h3>

          <p class="text-muted small mb-2">
            Platform pembelian tiket konser terpercaya dan mudah digunakan.
          </p>

          <p class="small mb-1">
            📧 <a href="mailto:rscsosmed@gmail.com" class="text-decoration-none text-muted hover-orange">
              rscsosmed@gmail.com
            </a>
          </p>

          <p class="small">
            📞 <a href="tel:+6285230088828" class="text-decoration-none text-muted hover-orange">
              +62 852-3008-8828
            </a>
          </p>
        </div>

        <!-- Navigasi -->
        <div class="col-md-4">
          <h5 class="fw-semibold mb-3">Informasi</h5>
          <ul class="list-unstyled small">
            <li class="mb-1">
              <a href="{{ url('/about-us') }}" class="text-muted text-decoration-none hover-orange">
                Tentang Kami
              </a>
            </li>
            <li class="mb-1">
              <a href="{{ url('/privacy-policy') }}" class="text-muted text-decoration-none hover-orange">
                Kebijakan Privasi
              </a>
            </li>
            <li>
              <a href="{{ url('/terms') }}" class="text-muted text-decoration-none hover-orange">
                Syarat & Ketentuan
              </a>
            </li>
          </ul>
        </div>

        <!-- Sosial Media -->
        <div class="col-md-4">
          <h5 class="fw-semibold mb-3">Ikuti Kami</h5>
          <div class="d-flex gap-3">
            <a href="https://instagram.com/rupasuaracahaya" target="_blank" class="text-muted hover-orange fs-5">
              <i class="fab fa-instagram"></i>
            </a>
            <a href="https://facebook.com/rupasuaracahaya" target="_blank" class="text-muted hover-orange fs-5">
              <i class="fab fa-facebook"></i>
            </a>
          </div>
        </div>

      </div>

      <hr class="my-4">

      <div class="text-center small text-muted">
        &copy; {{ date('Y') }} <span class="fw-semibold text-orange">RSCtix</span>. All rights reserved.
      </div>
    </div>
  </footer>

  @stack('scripts')
</body>
</html>
