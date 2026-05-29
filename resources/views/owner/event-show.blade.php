@extends('layouts.owner')

@section('title', 'Detail Event')

@section('content')

<div class="max-w-5xl mx-auto">

    {{-- HEADER --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Detail Pengajuan Event
        </h1>

        <p class="text-sm text-gray-500">
            Review event sebelum approval
        </p>
    </div>

    {{-- ALERT --}}
    @if(session('success'))
        <div class="mb-4 bg-green-100 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- CARD --}}
    <div class="bg-white rounded-2xl shadow p-6">

        {{-- POSTER --}}
        @if($event->poster)
            <div class="mb-6">
                <img src="{{ asset($event->poster) }}"
                     class="w-full max-h-[400px] object-cover rounded-xl border">
            </div>
        @endif

        {{-- DATA EVENT --}}
        <div class="grid md:grid-cols-2 gap-6">

            <div>
                <label class="text-sm text-gray-500">Judul Event</label>
                <p class="font-semibold text-lg">
                    {{ $event->title }}
                </p>
            </div>

            <div>
                <label class="text-sm text-gray-500">Nama EO</label>
                <p class="font-semibold">
                    {{ $event->eo->nama_badan_usaha ?? '-' }}
                </p>
            </div>

            <div>
                <label class="text-sm text-gray-500">Organizer</label>
                <p class="font-semibold">
                    {{ $event->organizer }}
                </p>
            </div>

            <div>
                <label class="text-sm text-gray-500">Lokasi</label>
                <p class="font-semibold">
                    {{ $event->location }}
                </p>
            </div>

            <div>
                <label class="text-sm text-gray-500">Tanggal Event</label>
                <p class="font-semibold">
                    {{ $event->date }}
                </p>
            </div>

            <div>
                <label class="text-sm text-gray-500">Status</label>

                @if($event->status == 'pending')
                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs">
                        Pending
                    </span>
                @elseif($event->status == 'approved')
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs">
                        Approved
                    </span>
                @else
                    <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs">
                        Rejected
                    </span>
                @endif
            </div>

        </div>

        {{-- DESKRIPSI --}}
        <div class="mt-6">
            <label class="text-sm text-gray-500">Deskripsi Event</label>

            <div class="mt-2 border rounded-xl p-4 bg-gray-50 text-gray-700 leading-relaxed">
                {{ $event->description }}
            </div>
        </div>

    </div>

    {{-- JADWAL & TIKET --}}
    <div class="bg-white rounded-2xl shadow p-6 mt-6">

        <h2 class="text-lg font-bold mb-4">
            Jadwal & Ticket
        </h2>

        @foreach($event->tickets->groupBy('jadwal_id') as $jadwalId => $tickets)

            <div class="border rounded-xl p-4 mb-4">

                <div class="mb-3">
                    <p class="font-semibold text-gray-800">
                        {{ optional($tickets->first()->jadwal)->info }}
                    </p>

                    <p class="text-sm text-gray-500">
                        {{ optional($tickets->first()->jadwal)->tanggal }}
                    </p>
                </div>

                <div class="overflow-x-auto">

                    <table class="w-full text-sm">

                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Nama Ticket</th>
                                <th class="text-left py-2">Harga</th>
                                <th class="text-left py-2">Stock</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($tickets as $ticket)

                                <tr class="border-b last:border-0">

                                    <td class="py-2">
                                        {{ $ticket->name }}
                                    </td>

                                    <td class="py-2">
                                        Rp{{ number_format($ticket->price) }}
                                    </td>

                                    <td class="py-2">
                                        {{ $ticket->stock }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        @endforeach

    </div>

    {{-- ACTION --}}
    @if($event->status == 'pending')

    <div class="flex gap-3 mt-6">

        <form method="POST"
              action="{{ route('owner.events.approve', $event->id) }}">

            @csrf

            <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl">
                Approve Event
            </button>

        </form>

        <form method="POST"
              action="{{ route('owner.events.reject', $event->id) }}">

            @csrf

            <button class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-xl">
                Reject Event
            </button>

        </form>

    </div>

    @endif

</div>

@endsection