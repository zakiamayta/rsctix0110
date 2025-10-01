@extends('layouts.app')

@section('title', 'Detail Event')

@section('content')
<div class="px-6 lg:px-16 xl:px-24 2xl:px-32 py-8">
    <div class="row g-4">

        {{-- 🔹 Kiri: Poster + Deskripsi/Line Up --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
                
                {{-- Poster --}}
                @if($event->poster)
                <img src="{{ asset($event->poster) }}"
                    alt="Poster {{ $event->title }}"
                    class="img-fluid w-100"
                    style="max-height: 420px; object-fit: cover;">
                @endif

                <div class="card-body p-4">
                    
                    {{-- Judul --}}
                    <h1 class="h3 fw-bold mb-3 logo-gradient">{{ $event->title }}</h1>

                    {{-- Info singkat --}}
                    <ul class="list-unstyled small mb-4 text-white">
                        <li class="mb-2 d-flex align-items-center">
                            <i class="bi bi-calendar-event me-2 text-orange"></i>
                            {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d F Y') }}
                        </li>
                        <li class="mb-2 d-flex align-items-center">
                            <i class="bi bi-geo-alt me-2 text-orange"></i>
                            {{ $event->location }}
                        </li>
                    </ul>

                    {{-- Tabs Deskripsi & Line Up --}}
                    <ul class="nav nav-tabs border-0 mb-3" id="eventTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" 
                                    id="desc-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#desc" 
                                    type="button" 
                                    role="tab">
                                <i class="bi bi-file-text me-1"></i> Deskripsi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" 
                                    id="lineup-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#lineup" 
                                    type="button" 
                                    role="tab">
                                <i class="bi bi-music-note-list me-1"></i> Line Up
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        {{-- Deskripsi --}}
                        <div class="tab-pane fade show active" id="desc" role="tabpanel">
                            <p class="lh-lg">
                                {{ $event->description }}
                            </p>
                        </div>

                        {{-- Line Up --}}
                        <div class="tab-pane fade" id="lineup" role="tabpanel">
                            <p class="lh-lg">
                                {{ $event->lineup ?? 'Line up akan diumumkan segera.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🔸 Kanan: Informasi Event --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg rounded-4 mb-4">
                <div class="card-body p-4">
                    
                    <h5 class="fw-bold mb-3 text-uppercase text-orange">Informasi Event</h5>
                    
                    <ul class="list-unstyled mb-4 small">
                        <!-- <li class="mb-2"><strong>Penyelenggara:</strong> {{ $event->organizer ?? '-' }}</li> -->
                        <li class="mb-2"><strong>Instagram:</strong> 
                            @if(!empty($event->instagram))
                                <a href="https://instagram.com/{{ ltrim($event->instagram, '@') }}" 
                                   target="_blank" 
                                   class="text-decoration-none d-flex align-items-center gap-2">
                                    <i class="bi bi-instagram text-orange"></i>
                                    <span class="text-orange">{{ $event->instagram }}</span>
                                </a>
                            @else
                                <span class="text-white">-</span>
                            @endif
                        </li>
                    </ul>

                    <div class="border-top pt-3">
                        <p class="mb-1 text-white">Harga tiket</p>
                        <h4 class="fw-bold text-orange mb-4">
                            Rp{{ number_format($minPrice ?? 0, 0, ',', '.') }}
                            @if($minPrice != $maxPrice)
                                - Rp{{ number_format($maxPrice ?? 0, 0, ',', '.') }}
                            @endif
                        </h4>

                        {{-- Tombol aksi --}}
                        <div class="d-grid gap-2">
                            <a href="{{ route('ticket.form', ['event_id' => $event->id]) }}" 
                               class="btn btn-orange-pill btn-lg">
                                <i class="bi bi-ticket-perforated"></i> Beli Tiket
                            </a>
                            <a href="{{ route('merchandise.index', ['event_id' => $event->id]) }}" 
                               class="btn btn-outline-orange btn-lg">
                                <i class="bi bi-bag"></i> Beli Merchandise
                            </a>
                            <!-- <button class="btn btn-outline-orange btn-lg" disabled>
                                <i class="bi bi-bag"></i> Beli Merchandise
                            </button> -->

                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection