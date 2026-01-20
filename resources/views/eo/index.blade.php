@extends('layouts.eo')

@section('title', 'Kelola Event')

@php
    use Illuminate\Support\Facades\Storage;
    use Carbon\Carbon;
@endphp

@section('content')
<main class="w-full px-6 lg:px-8 py-8">

    <div class="mb-8 flex justify-between items-center">
        <h1 class="text-3xl font-extrabold">Kelola Event</h1>

        <a href="{{ route('eo.event.create') }}"
           class="px-6 py-3 bg-blue-600 text-white rounded-2xl font-semibold">
            + Tambah Event
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-8">
        @forelse($events as $event)
        <div class="bg-white rounded-3xl shadow overflow-hidden">

            <img
                src="{{ asset($event->poster) }}"
                     alt="Poster Event"
                class="h-56 w-full object-cover"
                alt="{{ $event->title }}">

            <div class="p-6">
                <h3 class="text-xl font-semibold truncate">
                    {{ $event->title }}
                </h3>

                <p class="text-sm text-gray-600 mt-2 line-clamp-3">
                    {{ $event->description ?? '-' }}
                </p>

                <div class="mt-4 text-xs text-gray-500 space-y-1">
                    <p>📅 {{ Carbon::parse($event->date)->format('d M Y H:i') }}</p>
                    <p>📍 {{ $event->location }}</p>
                </div>

                <div class="flex gap-2 mt-6">
                    <a href="{{ route('eo.event.show', $event->id) }}"
                       class="flex-1 text-center px-4 py-2 bg-blue-100 rounded-xl">
                        Detail
                    </a>

                    <a href="{{ route('eo.event.edit', $event->id) }}"
                       class="flex-1 text-center px-4 py-2 bg-green-100 rounded-xl">
                        Edit
                    </a>

                    <form method="POST"
                          action="{{ route('eo.event.destroy', $event->id) }}"
                          onsubmit="return confirm('Hapus event ini?')"
                          class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button class="w-full px-4 py-2 bg-red-100 rounded-xl">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center text-gray-400 py-20">
            Belum ada event
        </div>
        @endforelse
    </div>
</main>
@endsection
