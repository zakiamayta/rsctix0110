<header class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg shadow-md w-full border-b border-gray-200">
  <div class="px-6 lg:px-16 xl:px-24 2xl:px-32">
    <div class="flex justify-between items-center h-16">

      <!-- 🔹 Logo -->
      <div class="flex items-center space-x-4">
        <a href="{{ url('/') }}">
          <img src="{{ asset('logoRSC.svg') }}" alt="Logo" class="h-14 w-30 object-contain">
        </a>
      </div>

      <!-- 🔹 Menu Desktop -->
      <nav class="hidden md:flex items-center space-x-6">
        <a href="{{ url('/') }}" class="text-gray-700 hover:text-orange-500 font-medium transition">
          Home
        </a>
        <a href="#upcoming-events" class="text-gray-700 hover:text-orange-500 font-medium transition">
          Events
        </a>

        <!-- 🔹 Search Bar -->
        <div class="relative">
          <input type="text" id="event-search" placeholder="Search events..."
            class="w-48 px-4 py-2 rounded-full border border-gray-300 bg-white text-gray-700 shadow-sm
                   focus:ring-2 focus:ring-orange-400 focus:border-orange-400
                   outline-none text-sm transition"/>
          <svg class="w-5 h-5 absolute right-3 top-2.5 text-gray-400 pointer-events-none"
               fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </div>

    @if(auth('user')->check())

    <div class="relative">

        <!-- AVATAR -->
        <button
            id="user-menu-button"
            class="flex items-center focus:outline-none"
        >
            <img
                src="{{ auth('user')->user()->avatar }}"
                alt="Avatar"
                class="w-10 h-10 rounded-full object-cover border border-gray-300"
                referrerpolicy="no-referrer"
            >
        </button>

        <!-- DROPDOWN -->
        <div
            id="user-dropdown"
            class="hidden absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-lg border z-50"
        >

            <div class="px-4 py-3 border-b">
                <p class="text-sm font-semibold text-gray-800">
                    {{ auth('user')->user()->name }}
                </p>
                <p class="text-xs text-gray-500">
                    {{ auth('user')->user()->email }}
                </p>
            </div>
                      <!-- 🔥 MENU USER -->
          <a href="{{ route('user.tickets') }}"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
              🎟️ My Tickets
          </a>

            <form method="POST" action="{{ route('user.logout') }}">
                @csrf
                <button
                    type="submit"
                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 rounded-b-xl"
                >
                    Logout
                </button>
            </form>
        </div>

    </div>

    @else

    <a href="{{ route('google.login') }}" class="btn-gradient-orange">
        Login
    </a>

    @endif

      </nav>

      <!-- 🔹 Mobile Menu Button -->
      <div class="md:hidden">
        <button id="menu-toggle"
                class="focus:outline-none p-2 rounded-md hover:bg-gray-100 transition">
          <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>

    </div>
  </div>

  <!-- 🔹 Mobile Menu -->
  <div id="mobile-menu"
       class="hidden md:hidden px-6 pb-4 bg-white border-t border-gray-200
              transition-all duration-300 ease-in-out">

    <a href="{{ url('/') }}"
       class="block py-2 text-gray-700 hover:text-orange-500 font-medium">
      Home
    </a>

    <a href="#upcoming-events"
       class="block py-2 text-gray-700 hover:text-orange-500 font-medium">
      Events
    </a>

    <!-- 🔹 Search Mobile -->
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

    <!-- 🔹 Tombol Get Tickets Mobile -->
    <a href="{{ route('ticket.form') }}"
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

    if (!button) return;

    button.addEventListener("click", function (e) {
        e.stopPropagation();
        dropdown.classList.toggle("hidden");
    });

    document.addEventListener("click", function () {
        dropdown.classList.add("hidden");
    });
});
</script>


  <!-- 🔹 Script -->
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const menuToggle = document.getElementById('menu-toggle');
      const menu = document.getElementById('mobile-menu');

      menuToggle.addEventListener('click', () => {
        menu.classList.toggle('hidden');
      });

      // 🔍 Event Search
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