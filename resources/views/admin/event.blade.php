@extends('layouts.admin')

@section('title', 'Manajemen Event')

@section('content')

<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Event</h2>
        <p class="text-sm text-gray-500">Semua event yang tersedia</p>
    </div>

    <!-- <a href="{{ route('admin.event.create') }}"
       class="px-4 py-2 bg-orange-600 text-white rounded-lg shadow hover:bg-orange-700">
        + Tambah Event
    </a> -->
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-3 text-left">Poster</th>
                <th class="p-3 text-left">Judul</th>
                <th class="p-3">Tanggal</th>
                <th class="p-3">Lokasi</th>
                <th class="p-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $event)
            <tr class="border-t">
                <td class="p-3">
                    @if($event->poster)
                        <img src="{{ asset($event->poster) }}" class="w-16 rounded">
                    @endif
                </td>
                <td class="p-3 font-medium">{{ $event->title }}</td>
                <td class="p-3 text-center">{{ $event->date }}</td>
                <td class="p-3 text-center">{{ $event->location }}</td>
                <td class="p-3 text-center">
                    <a href="#" class="text-orange-500">Edit</a>
                    |
                    <form action="{{ route('admin.event.destroy', $event->id) }}"
                          method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button class="text-red-500">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center p-6 text-gray-400">
                    Belum ada event
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection