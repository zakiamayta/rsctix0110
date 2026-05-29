@extends('layouts.eo')

@section('title', 'Status Persetujuan')

@section('content')

{{-- HEADER --}}
<div class="flex justify-between items-end mb-6">
    <div>
        <h2 class="text-xl font-bold"
            style="font-family:'Sora',sans-serif; color:#1A1208;">
            Status Persetujuan
        </h2>

        <p class="text-xs mt-1"
           style="color:#7A6E66;">
            Pantau status approval seluruh event yang telah diajukan
        </p>
    </div>
</div>

{{-- STATS --}}
<div class="grid grid-cols-4 gap-3 mb-6">

    <div class="rounded-xl p-4"
         style="background:#E8470A; color:#fff;">
        <p class="text-xs font-bold uppercase tracking-widest mb-1"
           style="opacity:.7;">
            Total Event
        </p>

        <p class="text-2xl font-bold"
           style="font-family:'Sora',sans-serif;">
            {{ $events->count() }}
        </p>
    </div>

    <div class="rounded-xl p-4"
         style="background:#fff; border:1px solid #EDE8E3;">
        <p class="text-xs font-bold uppercase tracking-widest mb-1"
           style="color:#7A6E66;">
            Approved
        </p>

        <p class="text-2xl font-bold"
           style="font-family:'Sora',sans-serif; color:#1A1208;">
            {{ $events->where('status','approved')->count() }}
        </p>
    </div>

    <div class="rounded-xl p-4"
         style="background:#fff; border:1px solid #EDE8E3;">
        <p class="text-xs font-bold uppercase tracking-widest mb-1"
           style="color:#7A6E66;">
            Pending
        </p>

        <p class="text-2xl font-bold"
           style="font-family:'Sora',sans-serif; color:#1A1208;">
            {{ $events->where('status','pending')->count() }}
        </p>
    </div>

    <div class="rounded-xl p-4"
         style="background:#fff; border:1px solid #EDE8E3;">
        <p class="text-xs font-bold uppercase tracking-widest mb-1"
           style="color:#7A6E66;">
            Rejected
        </p>

        <p class="text-2xl font-bold"
           style="font-family:'Sora',sans-serif; color:#1A1208;">
            {{ $events->where('status','rejected')->count() }}
        </p>
    </div>

</div>

{{-- GRID --}}
<div class="grid grid-cols-3 gap-4">

@forelse($events as $event)

<div class="rounded-xl overflow-hidden transition-all duration-150 hover:-translate-y-0.5"
     style="background:#fff; border:1px solid #EDE8E3;">

    {{-- POSTER --}}
    <div class="h-36 overflow-hidden"
         style="background:#FFF0EB;">

        @if($event->poster)
            <img src="{{ asset($event->poster) }}"
                 class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center">

                <svg width="36"
                     height="36"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="#E8470A"
                     stroke-width="1.5"
                     opacity="0.35">

                    <path d="M21 10V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v3m18 0H3m18 0l-2 11H5L3 10"/>
                </svg>

            </div>
        @endif

    </div>

    {{-- BODY --}}
    <div class="p-3">

        {{-- BADGE --}}
        @php
            $badges = [
                'approved' => [
                    'bg' => '#E8F5EE',
                    'color' => '#1A7A44',
                    'label' => 'Approved'
                ],

                'pending' => [
                    'bg' => '#FFF5E0',
                    'color' => '#9A6200',
                    'label' => 'Pending'
                ],

                'rejected' => [
                    'bg' => '#FDECEC',
                    'color' => '#9C2222',
                    'label' => 'Rejected'
                ],
            ];

            $b = $badges[$event->status]
                ?? [
                    'bg' => '#F1EFE8',
                    'color' => '#5F5E5A',
                    'label' => strtoupper($event->status)
                ];
        @endphp

        <span
            class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wider px-2 py-1 rounded mb-2"
            style="background:{{ $b['bg'] }};
                   color:{{ $b['color'] }};
                   font-size:9px;
                   letter-spacing:.8px;">

            <span style="width:5px;
                         height:5px;
                         border-radius:50%;
                         background:currentColor;
                         display:inline-block;">
            </span>

            {{ $b['label'] }}
        </span>

        {{-- TITLE --}}
        <h3 class="font-bold text-sm leading-snug mb-2 line-clamp-2"
            style="font-family:'Sora',sans-serif; color:#1A1208;">

            {{ $event->title }}
        </h3>

        {{-- DATE --}}
        <p class="text-xs flex items-center gap-1 mb-2"
           style="color:#7A6E66;">

            <svg width="11"
                 height="11"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2">

                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>

            </svg>

            {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d F Y') }}
        </p>

        {{-- LOCATION --}}
        <p class="text-xs line-clamp-1"
           style="color:#7A6E66;">
            📍 {{ $event->location }}
        </p>

    </div>

    {{-- FOOTER --}}
    <div class="px-3 pb-3">

        @if($event->status === 'rejected')
    <button
        onclick="openEditRejectedModal({{ $event->id }})"
        class="w-full text-center text-xs font-bold py-2 rounded-md"
        style="border:1px solid #E8470A; color:#E8470A;">
        Edit & Re-Submit
    </button>
@else
    <button
        onclick="openEventModal({{ $event->id }})"
        class="w-full text-center text-xs font-bold py-2 rounded-md"
        style="border:1px solid #EDE8E3; color:#7A6E66;">
        Lihat Detail
    </button>
@endif

    </div>

</div>

@empty

{{-- EMPTY STATE --}}
<div class="col-span-3 py-16 text-center"
     style="color:#7A6E66;">

    <svg width="40"
         height="40"
         viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="1"
         class="mx-auto mb-3 opacity-30">

        <rect x="3" y="4" width="18" height="18" rx="2"/>
        <line x1="16" y1="2" x2="16" y2="6"/>
        <line x1="8" y1="2" x2="8" y2="6"/>
        <line x1="3" y1="10" x2="21" y2="10"/>

    </svg>

    <p class="text-sm font-semibold mb-1">
        Belum ada event
    </p>

    <p class="text-xs">
        Event yang diajukan akan muncul di sini
    </p>

</div>

@endforelse

</div>

{{-- MODAL --}}
<div id="eventModal"
     class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">

    <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">

        {{-- HEADER --}}
        <div class="flex justify-between items-center p-5 border-b">

            <h3 class="text-xl font-bold"
                style="font-family:'Sora',sans-serif; color:#1A1208;">

                Detail Event
            </h3>

            <button onclick="closeEventModal()"
                    class="text-gray-500 hover:text-red-500 text-xl">
                ✕
            </button>

        </div>

        {{-- CONTENT --}}
        <div id="eventModalContent"
             class="p-5">

            {{-- AJAX CONTENT --}}

        </div>

    </div>

</div>

<div id="editRejectedModal"
     class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">

    <div class="bg-white rounded-2xl w-full max-w-7xl max-h-[90vh] overflow-y-auto">

        <div class="p-5 border-b flex justify-between">
            <h3 class="font-bold">Edit Event (Re-Submit)</h3>
            <button onclick="closeEditRejectedModal()">✕</button>
        </div>

        <div id="editRejectedContent">
            {{-- AJAX LOAD HERE --}}
        </div>

    </div>
</div>


<script>
function openEditRejectedModal(id)
{
    fetch(`/eo/event/${id}/edit-rejected`)
        .then(res => res.text())
        .then(html => {

            document.getElementById('editRejectedContent').innerHTML = html;

            document.getElementById('editRejectedModal')
                .classList.remove('hidden');
        });
}

function closeEditRejectedModal()
{
    document.getElementById('editRejectedModal')
        .classList.add('hidden');
}

function openEventModal(eventId)
{
    const modal = document.getElementById('eventModal');
    const content = document.getElementById('eventModalContent');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    content.innerHTML = `
        <div class="py-10 text-center text-gray-500">
            Loading...
        </div>
    `;

    fetch(`/eo/event/${eventId}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        });
}

function closeEventModal()
{
    const modal = document.getElementById('eventModal');

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

window.addEventListener('click', function(e)
{
    const modal = document.getElementById('eventModal');

    if (e.target === modal) {
        closeEventModal();
    }
});
</script>

@endsection