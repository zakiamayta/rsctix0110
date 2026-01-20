@extends('layouts.eo')

@section('title', $event->title)

@section('content')
<main class="max-w-6xl mx-auto px-6 py-10">

    {{-- Poster --}}
    <div class="mb-10">
        <img
            src="{{ $event->poster ? asset($event->poster) : asset('images/no-image.png') }}"
            alt="Poster {{ $event->title }}"
            class="w-full h-[420px] object-cover rounded-3xl shadow-lg">
    </div>

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-4xl font-bold tracking-tight mb-3">
            {{ $event->title }}
        </h1>

        <div class="flex flex-wrap gap-6 text-gray-600 text-sm">

            <div class="flex items-center gap-2">
                {{-- Calendar Icon --}}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ $event->date->format('d M Y H:i') }}</span>
            </div>

            <div class="flex items-center gap-2">
                {{-- Location Icon --}}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 10a3 3 0 110-6 3 3 0 010 6z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 22s8-4.5 8-11a8 8 0 10-16 0c0 6.5 8 11 8 11z" />
                </svg>
                <span>{{ $event->location }}</span>
            </div>

            @if($event->event_url)
                <a href="{{ $event->event_url }}" target="_blank"
                   class="flex items-center gap-2 text-blue-600 hover:underline">
                    {{-- Link Icon --}}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M13.828 10.172a4 4 0 015.656 5.656l-3 3a4 4 0 01-5.656-5.656" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10.172 13.828a4 4 0 01-5.656-5.656l3-3a4 4 0 015.656 5.656" />
                    </svg>
                    <span>Website Event</span>
                </a>
            @endif

            @if($event->instagram)
                <div class="flex items-center gap-2">
                    {{-- Instagram Icon --}}
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <rect width="20" height="20" x="2" y="2" rx="5" ry="5"/>
                        <circle cx="12" cy="12" r="3.5"/>
                        <circle cx="17.5" cy="6.5" r="1"/>
                    </svg>
                    <span>{{ '@' . ltrim($event->instagram, '@') }}</span>
                </div>
            @endif

        </div>
    </div>

    {{-- Info Section --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">

        {{-- Event Info --}}
        <div class="bg-white rounded-2xl p-6 shadow">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">
                Informasi Event
            </h3>

            <ul class="space-y-3 text-sm text-gray-700">
                <li><strong>Organizer:</strong> {{ $event->organizer ?? '-' }}</li>
                <li><strong>Lineup:</strong> {{ $event->lineup ?? '-' }}</li>
                <li><strong>Usia Minimum:</strong> {{ $event->min_age ? $event->min_age.' tahun' : '-' }}</li>
                <li>
                    <strong>Status:</strong>
                    <span class="ml-2 px-3 py-1 rounded-full text-xs font-medium
                        {{ $event->status === 'published'
                            ? 'bg-green-100 text-green-700'
                            : 'bg-yellow-100 text-yellow-700' }}">
                        {{ ucfirst($event->status) }}
                    </span>
                </li>
            </ul>
        </div>

        {{-- Ticket Rules --}}
        <div class="bg-white rounded-2xl p-6 shadow">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">
                Aturan Tiket
            </h3>

            <ul class="space-y-3 text-sm text-gray-700">
                <li><strong>Maksimal Tiket / Email:</strong> {{ $event->max_tickets_per_email }}</li>
                <li>
                    <strong>Mulai Penjualan:</strong><br>
                    {{ $event->ticket_sale_start?->format('d M Y H:i') ?? '-' }}
                </li>
                <li>
                    <strong>Mulai Penukaran:</strong><br>
                    {{ $event->ticket_redeem_start?->format('d M Y H:i') ?? '-' }}
                </li>
            </ul>
        </div>

    </div>

    {{-- Description --}}
    <div class="bg-white rounded-2xl p-6 shadow mb-12">
        <h3 class="text-lg font-semibold mb-4 border-b pb-2">
            Deskripsi Event
        </h3>

        <div class="prose max-w-none text-gray-700">
            {!! nl2br(e($event->description)) !!}
        </div>
    </div>

    {{-- Back --}}
    <a href="{{ route('eo.event.index') }}"
       class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        Kembali ke Event
    </a>

</main>
@endsection
