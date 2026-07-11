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

            {{-- TANGGAL BARU RESCHEDULE --}}
            @if($event->status == 'pending_reschedule')
            <div>
                <label class="text-sm text-gray-500">
                    Tanggal Baru Yang Diajukan
                </label>

                <p class="font-semibold text-blue-600">
                    {{ $event->proposed_date }}
                </p>
            </div>
            @endif

            <div>
                <label class="text-sm text-gray-500 mb-1 block">Status</label>

                @if($event->status == 'pending')
                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs">
                        Pending Approval
                    </span>

                @elseif($event->status == 'approved')
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs">
                        Approved
                    </span>

                @elseif($event->status == 'pending_cancel')
                    <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-semibold">
                        Pending Cancel
                    </span>

                @elseif($event->status == 'pending_reschedule')
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold">
                        Pending Reschedule
                    </span>

                @elseif($event->status == 'cancelled')
                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs">
                        Cancelled
                    </span>

                @elseif($event->status == 'rejected')
                    <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs">
                        Rejected
                    </span>
                @endif
            </div>

        </div>

        {{-- ALASAN RESCHEDULE --}}
        @if($event->status == 'pending_reschedule')
        <div class="mt-6">
            <label class="text-sm text-gray-500">
                Alasan Reschedule
            </label>

            <div class="mt-2 border rounded-xl p-4 bg-blue-50 text-blue-800">
                {{ $event->reschedule_reason }}
            </div>
        </div>
        @endif

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

    {{-- ACTION BUTTON OWNER --}}
    <div class="flex gap-3 mt-6 flex-wrap">

        {{-- APPROVAL EVENT BARU --}}
        @if($event->status == 'pending')

            <form method="POST" action="{{ route('owner.events.approve', $event->id) }}">
                @csrf
                <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-xl text-sm font-semibold">
                    Approve Event
                </button>
            </form>

            <form method="POST" action="{{ route('owner.events.reject', $event->id) }}"
                  class="w-full sm:w-auto">
                @csrf
                <textarea name="rejected_reason" rows="3" required
                          placeholder="Tulis alasan penolakan event untuk EO..."
                          class="w-full sm:w-80 rounded-xl border px-3 py-2 text-sm mb-2 @error('rejected_reason') border-red-500 @enderror">{{ old('rejected_reason') }}</textarea>
                @error('rejected_reason')
                    <p class="text-red-500 text-xs mb-2">{{ $message }}</p>
                @enderror
                <button class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-xl text-sm font-semibold block">
                    Reject Event
                </button>
            </form>

        {{-- APPROVAL CANCEL --}}
        @elseif($event->status == 'pending_cancel')

            <form method="POST" action="{{ route('owner.events.confirm-cancel', $event->id) }}">
                @csrf
                @method('PUT')

                <button class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-xl text-sm font-semibold">
                    Setujui Pembatalan Event
                </button>
            </form>

            <form method="POST" action="{{ route('owner.events.reject-cancel', $event->id) }}">
                @csrf
                @method('PUT')

                <button class="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 px-6 py-2 rounded-xl text-sm font-semibold">
                    Tolak Pembatalan
                </button>
            </form>

        {{-- APPROVAL RESCHEDULE --}}
        @elseif($event->status == 'pending_reschedule')

            <form method="POST"
                  action="{{ route('owner.events.approve-reschedule', $event->id) }}">
                @csrf
                @method('PUT')

                <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl text-sm font-semibold">
                    Setujui Reschedule
                </button>
            </form>

            <form method="POST"
                  action="{{ route('owner.events.reject-reschedule', $event->id) }}"
                  class="w-full sm:w-auto">
                @csrf
                @method('PUT')

                <textarea name="reschedule_rejected_reason" rows="3" required
                          placeholder="Tulis alasan penolakan reschedule untuk EO..."
                          class="w-full sm:w-80 rounded-xl border px-3 py-2 text-sm mb-2 @error('reschedule_rejected_reason') border-red-500 @enderror">{{ old('reschedule_rejected_reason') }}</textarea>
                @error('reschedule_rejected_reason')
                    <p class="text-red-500 text-xs mb-2">{{ $message }}</p>
                @enderror
                <button class="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 px-6 py-2 rounded-xl text-sm font-semibold block">
                    Tolak Reschedule
                </button>
            </form>

        @endif

    </div>

</div>

@endsection