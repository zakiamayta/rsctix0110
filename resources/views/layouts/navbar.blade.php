<header class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg shadow-md w-full border-b border-gray-200">
  <div class="px-6 lg:px-16 xl:px-24 2xl:px-32">
    <div class="flex justify-between items-center h-16 gap-4">

      <!-- Logo -->
      <a href="{{ url('/') }}" class="flex-shrink-0">
        <img src="{{ asset('logoRSC.svg') }}" alt="Logo" class="h-12 w-auto object-contain">
      </a>

      <!-- Menu Desktop -->
      <nav class="hidden md:flex items-center gap-6 flex-1 justify-end">
        <a href="{{ url('/') }}" class="text-gray-700 hover:text-orange-500 font-medium transition whitespace-nowrap">
          Home
        </a>
        <a href="#upcoming-events" class="text-gray-700 hover:text-orange-500 font-medium transition whitespace-nowrap">
          Events
        </a>

        <!-- Search Bar -->
        <div class="relative w-full max-w-[180px] lg:max-w-[220px]">
          <input type="text" id="event-search" placeholder="Search events..."
            class="w-full px-4 py-2 rounded-full border border-gray-300 bg-white text-gray-700 shadow-sm
                   focus:ring-2 focus:ring-orange-400 focus:border-orange-400
                   outline-none text-sm transition"/>
          <svg class="w-5 h-5 absolute right-3 top-2.5 text-gray-400 pointer-events-none"
               fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </div>

        @if(auth()->check())
          <div class="relative flex-shrink-0">
            <button id="user-menu-button" class="flex items-center focus:outline-none">
              <img
                src="{{ auth()->user()->avatar }}"
                alt="Avatar"
                class="w-10 h-10 rounded-full object-cover border border-gray-300"
                referrerpolicy="no-referrer">
            </button>

            <div
    id="user-dropdown"
    class="hidden absolute right-0 mt-3
           w-56 max-w-[85vw]
           bg-white rounded-xl
           shadow-lg border z-50 overflow-hidden">

    {{-- Header --}}
    <div class="px-4 py-3 border-b">

        <div class="flex items-center gap-3 min-w-0">

            <img
                src="{{ auth()->user()->avatar }}"
                class="w-10 h-10 rounded-full object-cover border flex-shrink-0"
                referrerpolicy="no-referrer">

            <div class="min-w-0">

                <p class="text-sm font-semibold text-gray-800 break-words leading-tight">
                    {{ auth()->user()->name }}
                </p>

                <p class="text-xs text-gray-500 break-all leading-tight">
                    {{ auth()->user()->email }}
                </p>

            </div>

        </div>

    </div>

    {{-- Menu --}}
    <a
        href="{{ route('user.tickets') }}"
        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
        🎟️ Riwayat Pembelian
    </a>

    @php
        $eo = \App\Models\Eo::where('user_id', auth()->id())->first();
    @endphp

    @if(auth()->user()->role === 'eo' && $eo && $eo->status === 'approved')
        <a
            href="{{ route('eo.dashboard') }}"
            class="block px-4 py-2 text-sm text-orange-600 hover:bg-orange-50 font-semibold">
            🚀 Dashboard EO
        </a>
    @endif

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button
            type="submit"
            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">

            Logout

        </button>
    </form>

</div>  
          </div>
        @else
          <a href="{{ route('login') }}" class="btn-gradient-orange flex-shrink-0">
            Login
          </a>
        @endif
      </nav>

      <!-- Mobile: avatar (jika login) + tombol menu -->
      <div class="flex md:hidden items-center gap-3">
        @if(auth()->check())
          <button id="user-menu-button-mobile" class="flex items-center focus:outline-none">
            <img
              src="{{ auth()->user()->avatar }}"
              alt="Avatar"
              class="w-9 h-9 rounded-full object-cover border border-gray-300"
              referrerpolicy="no-referrer">
          </button>
        @endif

        <button id="menu-toggle" class="focus:outline-none p-2 rounded-md hover:bg-gray-100 transition">
          <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>

    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobile-menu"
       class="hidden md:hidden px-6 pb-4 bg-white border-t border-gray-200
              transition-all duration-300 ease-in-out">

    <a href="{{ url('/') }}" class="block py-2 text-gray-700 hover:text-orange-500 font-medium">
      Home
    </a>
    <a href="#upcoming-events" class="block py-2 text-gray-700 hover:text-orange-500 font-medium">
      Events
    </a>

    <!-- Search Mobile -->
    <div class="mt-2 relative">
      <input type="text" id="event-search-mobile" placeholder="Search events..."
        class="w-full px-4 py-2 rounded-full border border-gray-300 bg-white text-gray-700 shadow-sm
               focus:ring-2 focus:ring-orange-400 focus:border-orange-400
               outline-none text-sm transition"/>
      <svg class="w-5 h-5 absolute right-3 top-2.5 text-gray-400 pointer-events-none"
           fill="none" stroke="currentColor" stroke-width="2"
           viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/>
        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
    </div>

    @if(auth()->check())
      {{-- Detail user untuk mobile, ditoggle lewat avatar di pojok kanan atas mobile --}}
      <div id="user-dropdown-mobile" class="hidden mt-3 bg-gray-50 rounded-xl border">
        <div class="px-4 py-3 border-b">
          <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
          <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
        </div>

        <a href="{{ route('user.tickets') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
          🎟️ Riwayat Pembelian
        </a>

        @php
          $eoMobile = \App\Models\Eo::where('user_id', auth()->id())->first();
        @endphp

        @if(auth()->user()->role === 'eo' && $eoMobile && $eoMobile->status === 'approved')
          <a href="{{ route('eo.dashboard') }}"
             class="block px-4 py-2 text-sm text-orange-600 hover:bg-orange-50 font-semibold">
            🚀 Dashboard EO
          </a>
        @endif

        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 rounded-b-xl">
            Logout
          </button>
        </form>
      </div>
    @else
      <a href="{{ route('login') }}" class="block text-center mt-3 btn-gradient-orange">
        Login
      </a>
    @endif

    <!-- Tombol Get Tickets Mobile -->
    <a href="{{ url('/#upcoming-events') }}"
       class="block text-center mt-3
              bg-gradient-to-r from-orange-500 to-yellow-400
              hover:from-orange-600 hover:to-yellow-500
              text-white font-semibold px-5 py-2 rounded-full shadow-md
              transition-transform transform hover:scale-105">
      Get Tickets
    </a>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const button = document.getElementById("user-menu-button");
      const dropdown = document.getElementById("user-dropdown");

      if (button && dropdown) {
        button.addEventListener("click", function (e) {
          e.stopPropagation();
          dropdown.classList.toggle("hidden");
        });
      }

      const buttonMobile = document.getElementById("user-menu-button-mobile");
      const dropdownMobile = document.getElementById("user-dropdown-mobile");
      const mobileMenu = document.getElementById("mobile-menu");

      if (buttonMobile && dropdownMobile && mobileMenu) {
        buttonMobile.addEventListener("click", function (e) {
          e.stopPropagation();
          mobileMenu.classList.remove("hidden");
          dropdownMobile.classList.toggle("hidden");
        });
      }

      document.addEventListener("click", function () {
        if (dropdown) dropdown.classList.add("hidden");
      });
    });
  </script>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const menuToggle = document.getElementById('menu-toggle');
      const menu = document.getElementById('mobile-menu');

      menuToggle.addEventListener('click', () => {
        menu.classList.toggle('hidden');
      });

      const searchInputs = [
        document.getElementById('event-search'),
        document.getElementById('event-search-mobile')
      ];

      const cards = document.querySelectorAll('.event-card');
      const sectionUpcoming = document.getElementById("upcoming-events");

      function filterEvents(value) {
        cards.forEach(card => {
          const title = card.querySelector('.event-title')
            ?.textContent.toLowerCase() || '';
          card.style.display = title.includes(value) ? '' : 'none';
        });
      }

      searchInputs.forEach(input => {
        if (!input) return;

        input.addEventListener('input', () => {
          filterEvents(input.value.toLowerCase());
        });

        input.addEventListener('keydown', e => {
          if (e.key === 'Enter') {
            e.preventDefault();
            filterEvents(input.value.toLowerCase());
            if (sectionUpcoming) {
              sectionUpcoming.scrollIntoView({ behavior: "smooth" });
            }
          }
        });
      });
    });
  </script>
</header>