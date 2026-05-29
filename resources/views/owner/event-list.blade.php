@extends('layouts.owner')

@section('title', 'Approval Event')

@section('content')

<h2 class="text-2xl font-bold mb-4">Approval Event</h2>

@if(session('success'))
<div class="bg-green-100 text-green-700 p-3 rounded mb-4">
    {{ session('success') }}
</div>
@endif

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
                    <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded">
                        Pending
                    </span>
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
                    Tidak ada event pending
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

</div>

@endsection