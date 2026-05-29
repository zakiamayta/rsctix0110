@extends('layouts.owner')

@section('title', 'Dashboard Owner')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center">

        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Dashboard Owner
            </h1>

            <p class="text-sm text-gray-500">
                Ringkasan platform & approval
            </p>
        </div>

    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">

        <div class="bg-white rounded-2xl p-5 border">
            <p class="text-sm text-gray-500">Total User</p>

            <h2 class="text-3xl font-bold mt-2">
                {{ $totalUsers }}
            </h2>
        </div>

        <div class="bg-white rounded-2xl p-5 border">
            <p class="text-sm text-gray-500">Total EO</p>

            <h2 class="text-3xl font-bold mt-2">
                {{ $totalEO }}
            </h2>
        </div>

        <div class="bg-white rounded-2xl p-5 border">
            <p class="text-sm text-gray-500">Total Event</p>

            <h2 class="text-3xl font-bold mt-2 text-[#E8470A]">
                {{ $totalEvents }}
            </h2>
        </div>

        <div class="bg-white rounded-2xl p-5 border">
            <p class="text-sm text-gray-500">Pending Approval</p>

            <h2 class="text-3xl font-bold mt-2 text-yellow-500">
                {{ $pendingEO + $pendingEvents }}
            </h2>
        </div>

    </div>

    {{-- STATUS --}}
    <div class="grid lg:grid-cols-2 gap-5">

        {{-- EO --}}
        <div class="bg-white rounded-2xl border p-5">

            <h2 class="font-bold text-lg mb-4">
                Status EO
            </h2>

            <div class="space-y-3">

                <div class="flex justify-between">
                    <span>Approved</span>

                    <span class="font-bold text-green-600">
                        {{ $approvedEO }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span>Pending</span>

                    <span class="font-bold text-yellow-500">
                        {{ $pendingEO }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span>Rejected</span>

                    <span class="font-bold text-red-500">
                        {{ $rejectedEO }}
                    </span>
                </div>

            </div>

        </div>

        {{-- EVENTS --}}
        <div class="bg-white rounded-2xl border p-5">

            <h2 class="font-bold text-lg mb-4">
                Status Event
            </h2>

            <div class="space-y-3">

                <div class="flex justify-between">
                    <span>Approved</span>

                    <span class="font-bold text-green-600">
                        {{ $approvedEvents }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span>Pending</span>

                    <span class="font-bold text-yellow-500">
                        {{ $pendingEvents }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span>Rejected</span>

                    <span class="font-bold text-red-500">
                        {{ $rejectedEvents }}
                    </span>
                </div>

            </div>

        </div>

    </div>

    {{-- QUICK ACTION --}}
    <div class="grid md:grid-cols-2 gap-5">

        <a href="{{ route('owner.eo.index') }}"
           class="bg-white border rounded-2xl p-5 hover:border-[#E8470A] transition">

            <h3 class="font-bold text-lg">
                Approval EO
            </h3>

            <p class="text-sm text-gray-500 mt-1">
                Review pengajuan Event Organizer
            </p>

        </a>

        <a href="{{ route('owner.events.index') }}"
           class="bg-white border rounded-2xl p-5 hover:border-[#E8470A] transition">

            <h3 class="font-bold text-lg">
                Approval Event
            </h3>

            <p class="text-sm text-gray-500 mt-1">
                Review pengajuan event
            </p>

        </a>

    </div>

    {{-- RECENT EO --}}
    <div class="bg-white rounded-2xl border p-5">

        <div class="flex justify-between items-center mb-4">

            <h2 class="font-bold text-lg">
                EO Terbaru
            </h2>

            <a href="{{ route('owner.eo.index') }}"
               class="text-sm text-[#E8470A]">
                Lihat Semua
            </a>

        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead>
                    <tr class="border-b text-left">
                        <th class="py-3">Nama</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($recentEO as $eo)

                    <tr class="border-b">

                        <td class="py-3">
                            {{ $eo->name }}
                        </td>

                        <td>
                            {{ $eo->email }}
                        </td>

                        <td>
                            {{ ucfirst($eo->status) }}
                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

    {{-- RECENT EVENT --}}
    <div class="bg-white rounded-2xl border p-5">

        <div class="flex justify-between items-center mb-4">

            <h2 class="font-bold text-lg">
                Event Terbaru
            </h2>

            <a href="{{ route('owner.events.index') }}"
               class="text-sm text-[#E8470A]">
                Lihat Semua
            </a>

        </div>

        <div class="space-y-4">

            @foreach($recentEvents as $event)

            <div class="border rounded-xl p-4">

                <div class="flex justify-between">

                    <div>
                        <h3 class="font-semibold">
                            {{ $event->title }}
                        </h3>

                        <p class="text-sm text-gray-500">
                            {{ $event->eo->nama_badan_usaha ?? '-' }}
                        </p>
                    </div>

                    <div>
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

            </div>

            @endforeach

        </div>

    </div>

</div>

@endsection