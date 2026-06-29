@extends('layouts.admin') 

@section('title', 'Pantauan Absensi Pengunjung')

@section('content')
    <h2 class="text-2xl font-bold mb-6">Pantauan Absensi Pengunjung</h2>

    {{-- 🔹 Ringkasan Absen --}}
    @php
        $totalSudah = $attendees->filter(fn($a) => $a->transaction?->is_registered)->count();
        $totalBelum = $attendees->filter(fn($a) => ! $a->transaction?->is_registered)->count();
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-green-100 border-l-4 border-green-500 p-4 rounded-lg flex items-center justify-between shadow">
            <div>
                <p class="text-sm text-green-700 font-semibold">Sudah Absen</p>
                <h3 class="text-2xl font-bold text-green-800">{{ $totalSudah }}</h3>
            </div>
            <div class="bg-green-500 text-white p-3 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>
        <div class="bg-red-100 border-l-4 border-red-500 p-4 rounded-lg flex items-center justify-between shadow">
            <div>
                <p class="text-sm text-red-700 font-semibold">Belum Absen</p>
                <h3 class="text-2xl font-bold text-red-800">{{ $totalBelum }}</h3>
            </div>
            <div class="bg-red-500 text-white p-3 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- 🔹 Form Filter & Sort --}}
    <div class="bg-white p-5 rounded-xl shadow-md mb-6">
        <form method="GET" action="{{ route('admin.absensi') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            {{-- Pilih Event --}}
            <div>
                <label for="event_id" class="block text-xs font-medium text-gray-700">Pilih Event</label>
                <select id="event_id" name="event_id"
                    class="form-select mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Semua Event --</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                            {{ $event->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Pencarian --}}
            <div>
                <label for="search" class="block text-xs font-medium text-gray-700">Pencarian</label>
                <input type="text" id="search" name="search" value="{{ $search ?? '' }}"
                    placeholder="Cari nama, email, atau no. telp"
                    class="border border-gray-300 mt-1 px-3 py-2 rounded-md w-full text-sm">
            </div>

            {{-- Status --}}
            <div>
                <label for="status" class="block text-xs font-medium text-gray-700">Status Absen</label>
                <select id="status" name="status"
                    class="border border-gray-300 mt-1 px-3 py-2 rounded-md w-full text-sm">
                    <option value="">Semua Status</option>
                    <option value="sudah" {{ ($status ?? '') === 'sudah' ? 'selected' : '' }}>Sudah Absen</option>
                    <option value="belum" {{ ($status ?? '') === 'belum' ? 'selected' : '' }}>Belum Absen</option>
                </select>
            </div>

            {{-- Tombol --}}
            <div class="flex gap-2 items-end md:col-span-2">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-semibold text-sm shadow-sm">
                    Filter
                </button>
                <a href="{{ route('admin.absensi') }}"
                    class="px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold text-sm shadow-sm">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- 🔹 Tabel Absensi --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 shadow-sm rounded-lg">
            <thead class="bg-blue-50 text-left text-sm font-semibold text-gray-700">
                <tr>
                    <th class="px-4 py-3 border-b">No</th>
                    <th class="px-4 py-3 border-b">Nama Event</th>
                    <th class="px-4 py-3 border-b">Email</th>
                    <th class="px-4 py-3 border-b">Nama Pengunjung</th>
                    <th class="px-4 py-3 border-b">Status Absen</th>
                    <th class="px-4 py-3 border-b">QR Code</th>
                    <th class="px-4 py-3 border-b text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse ($attendees as $index => $attendee)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-3 text-center">{{ $index + 1 }}</td>
                        <td class="px-4 py-3">{{ $attendee->transaction?->event?->title ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $attendee->transaction?->email ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $attendee->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($attendee->transaction?->is_registered)
                                <span class="text-green-600 font-semibold">Sudah Absen</span>
                            @else
                                <span class="text-red-600 font-semibold">Belum Absen</span>
                            @endif
                        </td>
                            <td class="px-4 py-3 text-center">
                                @if ($attendee->transaction?->qr_code)
                                    <a href="{{ route('absen.form', $attendee->transaction->kode_unik) }}" target="_blank"
                                        class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 transition">
                                        QR
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-center">
                                @if ($attendee->transaction)
                                    {{-- Detail Pembeli --}}
                                    <button onclick="showDetail({{ $attendee->id }})"
                                        class="p-2 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-600"
                                        title="Detail Pembeli">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                @endif

                                {{-- Tombol Absen/Batalkan --}}
                                @if (! $attendee->transaction?->is_registered)
                                    <form method="POST" action="{{ route('admin.absensi.manual', ['id' => $attendee->id]) }}">
                                        @csrf
                                        <button type="submit"
                                            class="p-2 rounded-full bg-green-100 hover:bg-green-200 text-green-600"
                                            title="Tandai Sudah Absen">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.absensi.batal', ['id' => $attendee->id]) }}">
                                        @csrf
                                        <button type="submit"
                                            class="p-2 rounded-full bg-red-100 hover:bg-red-200 text-red-600"
                                            title="Batalkan Absen">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center px-4 py-6 text-gray-500">
                            Tidak ada data peserta.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 🔹 Modal Detail --}}
    <div id="detailModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden items-center justify-center z-50">
        <div id="modalContentWrapper"
            class="bg-white p-6 rounded-lg shadow-xl max-w-lg w-full transform transition-all duration-300 scale-95 opacity-0">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Detail Pembeli Tiket</h3>
            <div id="modalContent" class="max-h-80 overflow-y-auto text-sm"></div>
            <button onclick="closeModal()"
                class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-semibold">
                Tutup
            </button>
        </div>
    </div>

    <script>
        const attendeeData = @json($attendees);

        function escapeHtml(str) {
            if (!str) return '-';
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;");
        }

        function showDetail(attendeeId) {
            const attendee = attendeeData.find(a => a.id === attendeeId);
            const modalContent = document.getElementById('modalContent');

            if (!attendee) {
                modalContent.innerHTML = '<p class="text-gray-500 italic text-center">Data peserta tidak ditemukan.</p>';
            } else {
                const t = attendee.transaction ?? {};
                modalContent.innerHTML = `
                    <table class="min-w-full text-sm">
                        <tr><td class="font-semibold pr-2 py-1">Email:</td><td>${escapeHtml(t.email)}</td></tr>
                        <tr><td class="font-semibold pr-2 py-1">Waktu Checkout:</td><td>${t.checkout_time ? new Date(t.checkout_time).toLocaleString() : '-'}</td></tr>
                        <tr><td class="font-semibold pr-2 py-1">Status Bayar:</td><td>${escapeHtml(t.payment_status)}</td></tr>
                        <tr><td class="font-semibold pr-2 py-1">Nama Peserta:</td><td>${escapeHtml(attendee.name)}</td></tr>
                        <tr><td class="font-semibold pr-2 py-1">No HP:</td><td>${escapeHtml(attendee.phone_number)}</td></tr>
                        <tr><td class="font-semibold pr-2 py-1">ID Tiket:</td><td>${escapeHtml(attendee.ticket_id)}</td></tr>
                    </table>
                `;
            }

            const modal = document.getElementById('detailModal');
            const wrapper = document.getElementById('modalContentWrapper');

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            setTimeout(() => {
                wrapper.classList.remove('scale-95', 'opacity-0');
                wrapper.classList.add('scale-100', 'opacity-100');
            }, 50);
        }

        function closeModal() {
            const modal = document.getElementById('detailModal');
            const wrapper = document.getElementById('modalContentWrapper');

            wrapper.classList.remove('scale-100', 'opacity-100');
            wrapper.classList.add('scale-95', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }
    </script>
@endsection
