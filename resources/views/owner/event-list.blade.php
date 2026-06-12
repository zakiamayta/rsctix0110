@extends('layouts.owner')

@section('title', 'Approval Event')

@section('content')

<h2 class="text-2xl font-bold mb-4">Approval Event</h2>


<div class="bg-white shadow rounded-xl overflow-hidden">

    <table class="w-full text-sm">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-3">Judul</th>
                <th class="p-3">EO</th>
                <th class="p-3">Tanggal</th>
                <th class="p-3">Status</th>
                <th class="p-3">Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse($events as $event)
            <tr class="border-t">
                <td class="p-3">{{ $event->title }}</td>
                <td class="p-3">{{ $event->eo->nama_badan_usaha ?? '-' }}</td>
                <td class="p-3">{{ $event->date }}</td>
                <td class="p-3">

    @if($event->status == 'pending')

        <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded">
            Pending Event
        </span>

    @elseif($event->status == 'pending_cancel')

        <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded font-semibold">
            Pending Cancel
        </span>

    @elseif($event->status == 'pending_reschedule')

        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded font-semibold">
            Pending Reschedule
        </span>

    @elseif($event->status == 'approved')

        <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">
            Approved
        </span>

    @elseif($event->status == 'cancelled')

        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">
            Cancelled
        </span>

    @elseif($event->status == 'rejected')

        <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded">
            Rejected
        </span>

    @endif

</td>
                <td class="p-3 space-x-2">

                    <a href="{{ route('owner.events.show', $event->id) }}"
                       class="px-3 py-1 bg-blue-500 text-white rounded text-xs">
                         Detail
                    </a>

                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="p-4 text-center text-gray-500">
                    Tidak ada event pending atau pengajuan pembatalan
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

</div>

@endsection