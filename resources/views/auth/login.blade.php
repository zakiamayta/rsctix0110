<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
    <link
      rel="stylesheet"
      as="style"
      onload="this.rel='stylesheet'"
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&family=Noto+Sans:wght@400;500;700;900&display=swap"
    />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  </head>
  <body class="bg-slate-50 font-[Inter,'Noto Sans',sans-serif] min-h-screen">
    <div class="flex flex-col items-center justify-center min-h-screen px-4">
      <form method="POST" action="{{ route('login') }}" class="w-full max-w-[512px] bg-white p-6 rounded-lg shadow">
        @csrf
        <h2 class="text-[#0e141b] text-[28px] font-bold text-center mb-6">Admin Login</h2>

        {{-- Alert if login failed --}}
        @if (session('error'))
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <strong>Login gagal:</strong> {{ session('error') }}
          </div>
        @endif

        {{-- Email Field --}}
        <div class="mb-4">
          <label class="block text-[#0e141b] text-base font-medium mb-2">Email</label>
          <div class="flex items-center rounded-lg overflow-hidden border border-[#d0dbe7] bg-slate-50">
            <input
              type="email"
              name="email"
              placeholder="Enter your email"
              value="{{ old('email') }}"
              class="form-input w-full h-14 p-[15px] pr-2 text-[#0e141b] placeholder:text-[#4e7397] border-r-0 focus:ring-0 focus:outline-0"
              required
            />
            <div class="pr-[15px] text-[#4e7397]">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256">
                <path d="M224,48H32a8,8,0,0,0-8,8V192a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A8,8,0,0,0,224,48Zm-96,85.15L52.57,64H203.43ZM98.71,128,40,181.81V74.19Zm11.84,10.85,12,11.05a8,8,0,0,0,10.82,0l12-11.05,58,53.15H52.57ZM157.29,128,216,74.18V181.82Z"/>
              </svg>
            </div>
          </div>
          @error('email')
            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
          @enderror
        </div>

        {{-- Password Field --}}
        <div class="mb-4">
          <label class="block text-[#0e141b] text-base font-medium mb-2">Password</label>
          <div class="flex items-center rounded-lg overflow-hidden border border-[#d0dbe7] bg-slate-50">
            <input
              type="password"
              name="password"
              placeholder="Enter your password"
              class="form-input w-full h-14 p-[15px] pr-2 text-[#0e141b] placeholder:text-[#4e7397] border-r-0 focus:ring-0 focus:outline-0"
              required
            />
            <div class="pr-[15px] text-[#4e7397]">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256">
                <path d="M208,80H176V56a48,48,0,0,0-96,0V80H48A16,16,0,0,0,32,96V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V96A16,16,0,0,0,208,80ZM96,56a32,32,0,0,1,64,0V80H96ZM208,208H48V96H208V208Zm-68-56a12,12,0,1,1-12-12A12,12,0,0,1,140,152Z"/>
              </svg>
            </div>
          </div>
          @error('password')
            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
          @enderror
        </div>

        {{-- Remember Me --}}
        {{-- <div class="flex items-center justify-between mb-6">
          <label class="flex items-center text-[#0e141b] text-base">
            <input
              type="checkbox"
              name="remember"
              class="h-5 w-5 rounded border-2 border-[#d0dbe7] text-[#197fe5] focus:ring-0"
            />
            <span class="ml-2">Remember Me</span>
          </label>
          <a href="{{ route('password.request') }}" class="text-sm text-[#4e7397] underline">Forgot Password?</a>
        </div> --}}

        {{-- Submit --}}
        <button
          type="submit"
          class="w-full h-10 bg-[#197fe5] text-white rounded-lg font-bold tracking-wide"
        >
          Sign In
        </button>
      </form>
    </div>
  </body>
</html>
