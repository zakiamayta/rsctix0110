{{-- POSTER --}}
@if($event->poster)
    <img src="{{ asset($event->poster) }}"
         class="w-full h-56 object-cover rounded-xl mb-4">
@endif

<div class="space-y-4">

    {{-- ALERT STATUS --}}
    @if($event->status == 'rejected')
        <div class="p-3 text-sm text-red-700 rounded-xl bg-red-50 border border-red-200">
            <strong>Event Ditolak.</strong>
            {{ $event->rejection_reason ?? 'Tidak ada alasan penolakan.' }}
        </div>

    @elseif($event->status == 'pending_cancel')
        <div class="p-3 text-sm text-amber-700 rounded-xl bg-amber-50 border border-amber-200">
            <strong>Pengajuan Pembatalan Sedang Direview Owner.</strong>
        </div>

    @elseif($event->status == 'pending_reschedule')
        <div class="p-3 text-sm text-blue-700 rounded-xl bg-blue-50 border border-blue-200">
            <strong>Pengajuan Reschedule Sedang Direview Owner.</strong>

            @if($event->proposed_date)
                <div class="mt-1 text-xs">
                    Jadwal baru yang diajukan:
                    <b>{{ \Carbon\Carbon::parse($event->proposed_date)->translatedFormat('d F Y H:i') }}</b>
                </div>
            @endif
        </div>
    @endif

    {{-- HEADER EVENT --}}
    <div>

        <div class="flex items-start justify-between gap-3 mb-2">

            <h2 class="text-xl font-bold leading-tight"
                style="font-family:'Sora',sans-serif;">
                {{ $event->title }}
            </h2>

            @if($event->status == 'approved')
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                    Approved
                </span>

            @elseif($event->status == 'pending')
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">
                    Pending
                </span>

            @elseif($event->status == 'rejected')
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                    Rejected
                </span>

            @elseif($event->status == 'pending_cancel')
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">
                    Pending Cancel
                </span>

            @elseif($event->status == 'pending_reschedule')
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
                    Pending Reschedule
                </span>

            @elseif($event->status == 'cancelled')
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                    Canceled
                </span>
            @endif

        </div>

        <p class="text-sm text-gray-500">
            {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d F Y H:i') }}
        </p>

    </div>

    {{-- INFORMASI EVENT --}}
    <div class="grid md:grid-cols-2 gap-3">

        <div class="bg-gray-50 rounded-xl p-3">
            <div class="text-xs text-gray-500">Organizer</div>
            <div class="font-semibold">{{ $event->organizer }}</div>
        </div>

        <div class="bg-gray-50 rounded-xl p-3">
            <div class="text-xs text-gray-500">Instagram</div>
            <div class="font-semibold">{{ $event->instagram ?: '-' }}</div>
        </div>

        <div class="bg-gray-50 rounded-xl p-3">
            <div class="text-xs text-gray-500">Lokasi</div>
            <div class="font-semibold">{{ $event->location }}</div>
        </div>

        <div class="bg-gray-50 rounded-xl p-3">
            <div class="text-xs text-gray-500">Minimal Umur</div>
            <div class="font-semibold">
                {{ $event->min_age ? $event->min_age.' Tahun' : '-' }}
            </div>
        </div>

        <div class="bg-gray-50 rounded-xl p-3 md:col-span-2">
            <div class="text-xs text-gray-500">Maksimal Tiket per Email</div>
            <div class="font-semibold">
                {{ $event->max_tickets_per_email }} Tiket
            </div>
        </div>

    </div>

    {{-- DESKRIPSI --}}
    <div>

        <h3 class="font-semibold mb-2">
            Deskripsi Event
        </h3>

        <div class="bg-gray-50 border rounded-xl p-3 text-sm text-gray-700 leading-relaxed">
            {{ $event->description ?: '-' }}
        </div>

    </div>

    {{-- LINEUP --}}
    @if($event->lineup)

    <div>

        <h3 class="font-semibold mb-2">
            Lineup
        </h3>

        <div class="bg-gray-50 border rounded-xl p-3 text-sm text-gray-700">
            {{ $event->lineup }}
        </div>

    </div>

    @endif

    {{-- JADWAL --}}
    <div>

        <h3 class="font-semibold text-lg mb-3">
            Jadwal & Tiket
        </h3>

        <div class="space-y-3">

            @foreach($event->jadwals as $jadwal)

            <div class="border rounded-xl p-3">

                <div class="mb-3">

                    <div class="font-semibold">
                        {{ $jadwal->info }}
                    </div>

                    <div class="text-xs text-gray-500">
                        {{ \Carbon\Carbon::parse($jadwal->tanggal)->translatedFormat('d F Y H:i') }}
                    </div>

                </div>

                <div class="space-y-2">

                    @foreach($jadwal->tickets as $ticket)

                    <div class="flex justify-between items-center bg-gray-50 rounded-lg px-3 py-2">

                        <div>

                            <div class="font-medium text-sm">
                                {{ $ticket->name }}
                            </div>

                            <div class="text-xs text-gray-500">
                                Stock {{ $ticket->stock }}
                            </div>

                        </div>

                        <div class="font-bold text-orange-600">
                            Rp {{ number_format($ticket->price,0,',','.') }}
                        </div>

                    </div>

                    @endforeach

                </div>

            </div>

            @endforeach

        </div>

    </div>

    {{-- ACTION --}}
    <div class="pt-4 border-t flex flex-wrap gap-2 justify-end">

        @if($event->status == 'approved')

            <form method="POST"
                  action="{{ route('eo.event.request-cancel', $event->id) }}">
                @csrf
                @method('PUT')

                <button
                    onclick="return confirm('Ajukan pembatalan event ini?')"
                    class="px-4 py-2 text-sm rounded-xl bg-red-600 text-white hover:bg-red-700">
                    Request Cancel
                </button>
            </form>
                <button
                    onclick="openRescheduleModal({{ $event->id }})"
                    class="px-4 py-2 text-sm rounded-xl bg-blue-600 text-white">
                    Request Reschedule
                </button>

        @elseif($event->status == 'rejected')

            <button
                onclick="openEventModal({{ $event->id }}, 'edit-rejected')"
                class="px-4 py-2 text-sm rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">
                Perbaiki Event
            </button>

        @endif

    </div>

</div>
<script>
function openEventModal(eventId, type)
{
    console.log('TYPE =', type);

    const modal = document.getElementById('eventModal');
    const content = document.getElementById('eventModalContent');
    const title = document.getElementById('modalTitle');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    let url = '';

    if (type === 'edit-rejected') {
        url = `/eo/event/${eventId}/edit-rejected`;
        title.innerText = 'Perbaiki Event';
    }
    else {
        url = `/eo/event/${eventId}`;
        title.innerText = 'Detail Event';
    }

    fetch(url)
        .then(res => res.text())
        .then(html => {
            content.innerHTML = html;
        });
}

function openRescheduleModal(eventId)
{
    console.log('RESCHEDULE', eventId);

    fetch(`/eo/event/${eventId}/reschedule`)
        .then(res => res.text())
        .then(html => {
            document.getElementById('eventModalContent').innerHTML = html;
        });
}
</script>