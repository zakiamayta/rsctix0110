{{-- POSTER --}}
@if($event->poster)
<img src="{{ asset($event->poster) }}"
     class="w-full h-72 object-cover rounded-xl mb-5">
@endif

<div class="space-y-5">

    {{-- INFO --}}
    <div>
        <h2 class="text-2xl font-bold mb-2"
            style="font-family:'Sora',sans-serif;">
            {{ $event->title }}
        </h2>

        <p class="text-sm text-gray-500">
            {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d F Y H:i') }}
        </p>
    </div>

    {{-- DETAIL --}}
    <div class="grid grid-cols-2 gap-4 text-sm">

        <div>
            <p class="font-semibold">Organizer</p>
            <p>{{ $event->organizer }}</p>
        </div>

        <div>
            <p class="font-semibold">Instagram</p>
            <p>{{ $event->instagram ?? '-' }}</p>
        </div>

        <div>
            <p class="font-semibold">Lokasi</p>
            <p>{{ $event->location }}</p>
        </div>

        <div>
            <p class="font-semibold">Minimal Umur</p>
            <p>{{ $event->min_age ?? '-' }}</p>
        </div>

        <div>
            <p class="font-semibold">Maks Tiket / Email</p>
            <p>{{ $event->max_tickets_per_email }}</p>
        </div>

        <div>
            <p class="font-semibold">Status</p>
            <p class="capitalize">{{ $event->status }}</p>
        </div>

    </div>

    {{-- DESCRIPTION --}}
    <div>
        <p class="font-semibold mb-2">Deskripsi</p>

        <div class="text-sm text-gray-600 leading-relaxed">
            {{ $event->description }}
        </div>
    </div>

    {{-- LINEUP --}}
    @if($event->lineup)
    <div>
        <p class="font-semibold mb-2">Lineup</p>

        <div class="text-sm text-gray-600">
            {{ $event->lineup }}
        </div>
    </div>
    @endif

    {{-- JADWAL --}}
    <div>
        <h3 class="font-bold text-lg mb-3">
            Jadwal & Tiket
        </h3>

        <div class="space-y-4">

            @foreach($event->jadwals as $jadwal)

            <div class="border rounded-xl p-4">

                <div class="mb-3">
                    <p class="font-bold">
                        {{ $jadwal->info }}
                    </p>

                    <p class="text-sm text-gray-500">
                        {{ \Carbon\Carbon::parse($jadwal->tanggal)->translatedFormat('d F Y H:i') }}
                    </p>
                </div>

                {{-- TICKETS --}}
                <div class="space-y-2">

                    @foreach($jadwal->tickets as $ticket)

                    <div class="flex justify-between items-center border rounded-lg px-3 py-2">

                        <div>
                            <p class="font-semibold text-sm">
                                {{ $ticket->name }}
                            </p>

                            <p class="text-xs text-gray-500">
                                Stock: {{ $ticket->stock }}
                            </p>
                        </div>

                        <div class="text-sm font-bold text-orange-500">
                            Rp {{ number_format($ticket->price,0,',','.') }}
                        </div>

                    </div>

                    @endforeach

                </div>

            </div>

            @endforeach

        </div>
    </div>

</div>