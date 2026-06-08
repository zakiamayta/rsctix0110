@extends('layouts.eo')
@section('title', 'Event Saya')

@section('content')

{{-- HEADER --}}
<div class="flex justify-between items-center mb-5">

    <div>
        <h2 class="text-xl font-bold"
            style="font-family:'Sora',sans-serif;color:#1A1208;">
            Event Saya
        </h2>

        <p class="text-xs text-gray-500 mt-1">
            Kelola event yang telah diajukan
        </p>
    </div>

    <a href="{{ route('eo.event.create') }}"
       class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-orange-600 hover:bg-orange-700 transition">
        + Buat Event
    </a>

</div>

{{-- FLASH --}}
@if(session('success'))
<div class="mb-4 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
    {{ session('error') }}
</div>
@endif

{{-- STATS --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">

    <div class="bg-white border rounded-xl p-4">
        <p class="text-xs text-gray-500">Total Event</p>
        <p class="text-xl font-bold mt-1">
            {{ $events->count() }}
        </p>
    </div>

    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
        <p class="text-xs text-green-700">Approved</p>
        <p class="text-xl font-bold text-green-700 mt-1">
            {{ $events->where('status','approved')->count() }}
        </p>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <p class="text-xs text-amber-700">Review</p>
        <p class="text-xl font-bold text-amber-700 mt-1">
            {{
                $events->whereIn('status',[
                    'pending_cancel',
                    'pending_reschedule'
                ])->count()
            }}
        </p>
    </div>

    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <p class="text-xs text-red-700">Rejected</p>
        <p class="text-xl font-bold text-red-700 mt-1">
            {{ $events->where('status','rejected')->count() }}
        </p>
    </div>

</div>

{{-- EVENT GRID --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

@forelse($events as $event)

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-md transition">

    {{-- POSTER --}}
    <div class="relative">

        @if($event->poster)
            <img src="{{ asset($event->poster) }}"
                 class="w-full h-40 object-cover">
        @else
            <div class="h-40 flex items-center justify-center bg-orange-50">
                <svg width="36"
                     height="36"
                     fill="none"
                     stroke="#E8470A"
                     stroke-width="1.5"
                     viewBox="0 0 24 24">
                    <path d="M21 10V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v3m18 0H3m18 0l-2 11H5L3 10"/>
                </svg>
            </div>
        @endif

        {{-- STATUS --}}
        <div class="absolute top-2 right-2">

            @php
                $statusClass = match($event->status){
                    'approved' => 'bg-green-100 text-green-700',
                    'pending_cancel' => 'bg-amber-100 text-amber-700',
                    'pending_reschedule' => 'bg-blue-100 text-blue-700',
                    'rejected' => 'bg-red-100 text-red-700',
                    'canceled' => 'bg-gray-100 text-gray-700',
                    default => 'bg-gray-100 text-gray-700'
                };
            @endphp

            <span class="px-2 py-1 rounded-full text-[11px] font-semibold {{ $statusClass }}">
                {{ str_replace('_',' ', ucfirst($event->status)) }}
            </span>

        </div>

    </div>

    {{-- CONTENT --}}
    <div class="p-4">

        <h3 class="font-bold text-sm line-clamp-2 mb-3"
            style="font-family:'Sora',sans-serif;">
            {{ $event->title }}
        </h3>

        <div class="space-y-2 text-xs text-gray-500">

            <div class="flex items-center gap-2">
                <span>📅</span>
                <span>
                    {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d M Y') }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <span>📍</span>
                <span class="truncate">
                    {{ $event->location }}
                </span>
            </div>

        </div>

        {{-- STATUS INFO --}}
        @if($event->status == 'pending_cancel')
            <div class="mt-3 text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2">
                Menunggu persetujuan pembatalan event.
            </div>
        @endif

        @if($event->status == 'pending_reschedule')
            <div class="mt-3 text-[11px] text-blue-700 bg-blue-50 border border-blue-200 rounded-lg p-2">
                Menunggu persetujuan perubahan jadwal.
            </div>
        @endif

        @if($event->status == 'rejected')
            <div class="mt-3 text-[11px] text-red-700 bg-red-50 border border-red-200 rounded-lg p-2">
                Event ditolak dan perlu diperbaiki.
            </div>
        @endif

    </div>

    {{-- ACTION --}}
    <div class="px-4 pb-4 flex gap-2">

        <button
            onclick="openEventModal({{ $event->id }}, 'detail')"
            class="flex-1 py-2 rounded-lg border border-gray-200 text-gray-700 text-xs font-semibold hover:bg-gray-50">
            Detail
        </button>

        @if($event->status == 'approved')

            <a href="{{ route('eo.event.edit', $event->id) }}"
               class="flex-1 py-2 rounded-lg bg-orange-600 text-white text-center text-xs font-semibold hover:bg-orange-700">
                Kelola
            </a>

        @elseif($event->status == 'rejected')

            <button
                onclick="openEventModal({{ $event->id }}, 'edit-rejected')"
                class="flex-1 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                Perbaiki
            </button>

        @endif

    </div>

</div>

@empty

<div class="col-span-full">

    <div class="bg-white border border-dashed border-gray-300 rounded-2xl p-12 text-center">

        <h3 class="font-semibold text-gray-700 mb-2">
            Belum ada event
        </h3>

        <p class="text-sm text-gray-500 mb-5">
            Mulai dengan membuat event pertamamu.
        </p>

        <a href="{{ route('eo.event.create') }}"
           class="inline-flex px-4 py-2 rounded-lg bg-orange-600 text-white font-semibold hover:bg-orange-700">
            + Buat Event
        </a>

    </div>

</div>

@endforelse

</div>

{{-- MODAL --}}
<div id="eventModal"
     class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">

    <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">

        <div class="flex justify-between items-center p-5 border-b sticky top-0 bg-white z-10">

            <h3 id="modalTitle"
                class="text-xl font-bold"
                style="font-family:'Sora',sans-serif;">
                Detail Event
            </h3>

            <button onclick="closeEventModal()"
                    class="text-xl text-gray-500 hover:text-red-500">
                ✕
            </button>

        </div>

        <div id="eventModalContent" class="p-5"></div>

    </div>

</div>

<script>
function openEventModal(eventId, type)
{
    const modal = document.getElementById('eventModal');
    const content = document.getElementById('eventModalContent');
    const title = document.getElementById('modalTitle');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    if(type === 'edit-rejected') {
        title.innerText = 'Perbaiki Event';
    } else {
        title.innerText = 'Detail Event';
    }

    content.innerHTML = `
        <div class="py-10 text-center text-gray-500">
            Memuat data...
        </div>
    `;

    let url = `/eo/event/${eventId}`;

    if(type === 'edit-rejected') {
        url = `/eo/event/${eventId}/edit-rejected`;
    }

    fetch(url)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(() => {
            content.innerHTML = `
                <div class="py-10 text-center text-red-500">
                    Gagal memuat data.
                </div>
            `;
        });
}

function closeEventModal()
{
    const modal = document.getElementById('eventModal');

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('eventModal');

    if (e.target === modal) {
        closeEventModal();
    }
});
</script>

@endsection