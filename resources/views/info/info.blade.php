@extends('layouts.app')

@section('title', 'Detail Event')

@section('content')
<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 py-8 bg-light">
    <div class="row g-4">

        {{-- ðŸ”¹ Kiri: Poster + Deskripsi --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
                
                {{-- Poster --}}
                @if($event->poster)
                <div style="width:100%; aspect-ratio:16/9; overflow:hidden;">
                    <img src="{{ asset($event->poster) }}"
                        alt="Poster Event"
                        style="width:100%; height:100%; object-fit:cover;">
                </div>
                @endif

                <div class="card-body p-4">
                    
                    {{-- Judul --}}
                    <h1 class="h3 fw-bold mb-3 text-dark">
                        {{ $event->title }}
                    </h1>

                    {{-- Info singkat --}}
                    <ul class="list-unstyled small mb-4 text-muted">
                        <li class="mb-2 d-flex align-items-center">
                            <i class="bi bi-calendar-event me-2 text-orange"></i>
                            {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d F Y') }}
                        </li>
                        <li class="mb-2 d-flex align-items-center">
                            <i class="bi bi-geo-alt me-2 text-orange"></i>
                            {{ $event->location }}
                        </li>
                    </ul>

                    {{-- Tabs --}}
                    <ul class="nav nav-tabs border-0 mb-3" id="eventTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-semibold"
                                    id="desc-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#desc"
                                    type="button">
                                <i class="bi bi-file-text me-1"></i> Deskripsi
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-semibold"
                                    id="lineup-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#lineup"
                                    type="button">
                                <i class="bi bi-music-note-list me-1"></i> Line Up
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content text-dark">
                        <div class="tab-pane fade show active" id="desc">
                            <p class="lh-lg">
                                {{ $event->description }}
                            </p>
                        </div>

                        <div class="tab-pane fade" id="lineup">
                            <p class="lh-lg">
                                {{ $event->lineup ?? 'Line up akan diumumkan segera.' }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{--Kanan: Informasi Event --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-4">
                    
                    <h5 class="fw-bold mb-3 text-uppercase text-orange">
    Jadwal Event
</h5>

<ul class="list-unstyled mb-4 small text-dark">
    @forelse($jadwals as $jadwal)
        <li class="mb-3">
            <a href="#"
   onclick="handleJadwalClick(event, '{{ route('ticket.form', ['event_id' => $event->id, 'jadwal_id' => $jadwal->id]) }}')"
   class="text-decoration-none d-block p-2 rounded hover-bg-light">

                <div class="fw-semibold text-dark">
                    {{ $jadwal->info }}
                </div>

                <div class="text-muted small">
                    {{ \Carbon\Carbon::parse($jadwal->tanggal)->translatedFormat('d F Y H:i') }}
                </div>
            </a>
        </li>
    @empty
        <li class="text-muted">Jadwal belum tersedia</li>
    @endforelse
</ul>
                    
                    <ul class="list-unstyled mb-4 small text-dark">
                        <li class="mb-2">
                            <strong>Instagram:</strong>
                            @if(!empty($event->instagram))
                                <a href="https://instagram.com/{{ ltrim($event->instagram, '@') }}"
                                   target="_blank"
                                   class="text-decoration-none d-flex align-items-center gap-2 mt-1">
                                    <i class="bi bi-instagram text-orange"></i>
                                    <span class="text-orange">{{ $event->instagram }}</span>
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </li>
                    </ul>

                    <div class="border-top pt-3">
                        <p class="mb-1 text-muted">Harga tiket</p>
                        <h4 class="fw-bold text-orange mb-4">
                            Rp{{ number_format($minPrice ?? 0, 0, ',', '.') }}
                            @if($minPrice != $maxPrice)
                                - Rp{{ number_format($maxPrice ?? 0, 0, ',', '.') }}
                            @endif
                        </h4>
<!-- 
                        {{-- Tombol --}}
                        <div class="d-grid gap-2">
                            <a href="{{ route('ticket.form', ['event_id' => $event->id]) }}"
                               class="btn btn-orange-pill btn-lg">
                                <i class="bi bi-ticket-perforated"></i> Beli Tiket
                            </a>

                            <a href="{{ route('merchandise.index', ['event_id' => $event->id]) }}"
                               class="btn btn-outline-orange btn-lg">
                                <i class="bi bi-bag"></i> Beli Merchandise
                            </a>
                        </div> -->
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<script>
    const isLoggedIn = @json(auth()->guard('user')->check());

    function handleJadwalClick(e, url) {
        e.preventDefault();

        if (!isLoggedIn) {
            const modal = new bootstrap.Modal(document.getElementById('loginModal'));
            modal.show();
        } else {
            window.location.href = url;
        }
    }
</script>
<!-- Modal Login Required -->
<div class="modal fade" id="loginModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      
      <div class="modal-body text-center p-4">
        
        <div class="mb-3">
          <i class="bi bi-lock-fill text-orange" style="font-size:40px;"></i>
        </div>

        <h5 class="fw-bold mb-2">Login Diperlukan</h5>
        <p class="text-muted mb-4">
          Anda harus login terlebih dahulu untuk membeli tiket.
        </p>

        <div class="d-flex justify-content-center gap-2">
          <button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
            Batal
          </button>

          <a href="{{ route('google.login') }}" class="btn btn-orange-pill px-4">
            Login Sekarang
          </a>
        </div>

      </div>

    </div>
  </div>
</div>
@endsection
