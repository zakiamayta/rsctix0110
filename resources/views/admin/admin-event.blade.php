@extends('layouts.admin')

@section('title', 'Kelola Event')

@section('content')
<main class="container mx-auto px-6 sm:px-8 lg:px-12 py-10">

    <!-- Header -->
    <div class="mb-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 animate-fade-in-up">
        <div>
            <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 leading-tight">
                Kelola Event
            </h1>
        </div>
        <button onclick="openAddModal()" 
            class="btn-ripple inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-2xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Event
        </button>
    </div>

    {{-- List Event --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($events as $event)
        <div class="bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-transform transform hover:-translate-y-2 animate-fade-in-up flex flex-col overflow-hidden">
            <div class="relative w-full h-56 overflow-hidden rounded-t-3xl">
                <img src="{{ $event->poster ? asset($event->poster) : asset('images/no-image.png') }}" 
                     alt="{{ $event->title }}" 
                     class="w-full h-full object-cover object-center transition-transform duration-500 hover:scale-105">
            </div>
            <div class="p-6 flex flex-col flex-grow justify-between">
                <div>
                    <h3 class="text-2xl font-semibold text-gray-900 truncate" title="{{ $event->title }}">{{ $event->title }}</h3>
                    <p class="mt-2 text-gray-600 text-sm line-clamp-3 leading-relaxed">{{ $event->description }}</p>
                    <div class="mt-4 text-xs text-gray-500 space-y-1">
                        <p>📅 {{ \Carbon\Carbon::parse($event->date)->format('d M Y H:i') }}</p>
                        <p>📍 {{ $event->location }}</p>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button onclick="openDetailModal({{ $event->id }})" 
                        class="flex-1 px-4 py-2 rounded-xl text-sm font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition-shadow shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-300">
                        Detail
                    </button>
                    <button onclick="openEditModal({{ $event->id }})" 
                        class="flex-1 px-4 py-2 rounded-xl text-sm font-semibold bg-green-100 text-green-700 hover:bg-green-200 transition-shadow shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-green-300">
                        Edit
                    </button>
                    <form method="POST" action="{{ route('admin.event.destroy', $event->id) }}" 
                          onsubmit="return confirm('Hapus event ini?')" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                            class="w-full px-4 py-2 rounded-xl text-sm font-semibold bg-red-100 text-red-700 hover:bg-red-200 transition-shadow shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-red-300">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-24 text-gray-400 text-xl font-medium select-none">
            Belum ada event terdaftar.
        </div>
        @endforelse
    </div>
</main>

{{-- Modal Add/Edit Event --}}
<div id="eventModal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-[100] p-6">
    <div class="bg-white rounded-3xl shadow-2xl p-10 w-full max-w-5xl max-h-[90vh] overflow-y-auto animate-scale-in scrollbar-thin scrollbar-thumb-blue-400 scrollbar-track-gray-100">
        <h3 id="modalTitle" class="text-3xl font-extrabold mb-8 text-gray-900 tracking-tight">Tambah Event</h3>
        <form id="eventForm" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label for="eventTitle" class="block text-sm font-semibold mb-2 text-gray-700">Judul Event</label>
                    <input type="text" name="title" id="eventTitle" class="form-input w-full rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" required>
                </div>
                <div>
                    <label for="eventDate" class="block text-sm font-semibold mb-2 text-gray-700">Tanggal & Waktu</label>
                    <input type="datetime-local" name="date" id="eventDate" class="form-input w-full rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" required>
                </div>
                <div>
                    <label for="eventLocation" class="block text-sm font-semibold mb-2 text-gray-700">Lokasi</label>
                    <input type="text" name="location" id="eventLocation" class="form-input w-full rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" required>
                </div>
                <div>
                    <label for="poster" class="block text-sm font-semibold mb-2 text-gray-700">Poster</label>
                    <input type="file" name="poster" id="poster" class="form-input w-full rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" accept="image/*">
                </div>
            </div>

            <div>
                <label for="eventDescription" class="block text-sm font-semibold mb-2 text-gray-700">Deskripsi</label>
                <textarea name="description" id="eventDescription" rows="4" class="form-textarea w-full rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none"></textarea>
            </div>

            <h4 class="text-xl font-bold text-gray-900 mb-4 border-b border-gray-200 pb-2">Daftar Tiket</h4>
            <div id="ticketsContainer" class="space-y-6"></div>
            <button type="button" onclick="tambahTicket()" 
                class="w-full mt-4 px-5 py-3 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold transition shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-400">
                + Tambah Tiket
            </button>

            <div class="flex justify-end gap-4 pt-8">
                <button type="button" onclick="closeModal()" 
                    class="px-8 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Batal
                </button>
                <button type="submit" 
                    class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition focus:outline-none focus:ring-4 focus:ring-blue-500">
                    Simpan Event
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Detail --}}
<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-[100] p-6">
    <div class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-3xl max-h-[90vh] overflow-y-auto animate-scale-in scrollbar-thin scrollbar-thumb-blue-400 scrollbar-track-gray-100">
        <div class="flex justify-between items-center mb-8">
            <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">Detail Event</h3>
            <button type="button" onclick="closeDetailModal()" class="text-gray-500 hover:text-gray-800 text-3xl leading-none font-bold focus:outline-none" aria-label="Close detail modal">×</button>
        </div>
        <div id="detailContent" class="text-gray-700 space-y-6 text-base leading-relaxed"></div>
        <div class="flex justify-end mt-8">
            <button type="button" onclick="closeDetailModal()" 
                class="px-8 py-3 bg-gray-100 hover:bg-gray-200 rounded-xl font-semibold text-gray-700 transition focus:outline-none focus:ring-2 focus:ring-gray-300">
                Tutup
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let ticketIndex = 0;

function openAddModal() {
    document.getElementById('modalTitle').innerText = "Tambah Event";
    document.getElementById('eventForm').action = "{{ route('admin.event.store') }}";
    document.getElementById('formMethod').value = "POST";

    document.getElementById('eventTitle').value = "";
    document.getElementById('eventDate').value = "";
    document.getElementById('eventLocation').value = "";
    document.getElementById('eventDescription').value = "";

    document.getElementById('ticketsContainer').innerHTML = '';
    ticketIndex = 0;
    tambahTicket(); 
    document.getElementById('eventModal').classList.remove('hidden');
}

function openEditModal(id) {
    fetch(`/admin/event/${id}/edit`)
    .then(res => res.json())
    .then(event => {
        document.getElementById('modalTitle').innerText = "Edit Event";
        document.getElementById('eventForm').action = `/admin/event/${id}`;
        document.getElementById('formMethod').value = "PUT";

        document.getElementById('eventTitle').value = event.title;
        document.getElementById('eventDate').value = event.date.replace(' ', 'T');
        document.getElementById('eventLocation').value = event.location;
        document.getElementById('eventDescription').value = event.description ?? '';

        document.getElementById('ticketsContainer').innerHTML = '';
        ticketIndex = 0;
        event.tickets.forEach(t => tambahTicket(t));

        document.getElementById('eventModal').classList.remove('hidden');
    });
}

function tambahTicket(ticket = null) {
    let html = `
    <div class="ticketBlock border border-gray-300 p-5 rounded-2xl bg-gradient-to-r from-gray-50 to-gray-100 shadow-sm relative">
        <button type="button" onclick="hapusTicket(this)" 
            class="absolute top-3 right-3 flex items-center justify-center w-8 h-8 rounded-full bg-red-600 hover:bg-red-700 transition focus:outline-none focus:ring-2 focus:ring-red-400" 
            title="Hapus tiket" aria-label="Hapus tiket">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 0a1 1 0 00-1 1v1h6V4a1 1 0 00-1-1m-4 0h4" />
            </svg>
        </button>

        <label class="block text-sm font-semibold mb-1 text-gray-700">Nama Tiket</label>
        <input type="text" name="tickets[${ticketIndex}][name]" 
            class="form-input w-full rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-4 transition" 
            value="${ticket ? ticket.name : ''}" required>

        <label class="block text-sm font-semibold mb-1 text-gray-700">Harga (Rp)</label>
        <div class="flex items-center mb-4">
            <span class="px-4 py-2 bg-gray-200 border border-r-0 border-gray-300 rounded-l-lg text-gray-600 select-none">Rp</span>
            <input type="number" 
                name="tickets[${ticketIndex}][price]" 
                class="form-input w-full rounded-r-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" 
                value="${ticket ? ticket.price : ''}" 
                step="1000" min="0" required>
        </div>

        <label class="block text-sm font-semibold mb-1 text-gray-700">Stok</label>
        <input type="number" 
            name="tickets[${ticketIndex}][stock]" 
            class="form-input w-full rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" 
            value="${ticket ? ticket.stock : ''}" 
            min="1" required>
    </div>`;
    document.getElementById('ticketsContainer').insertAdjacentHTML('beforeend', html);
    ticketIndex++;
}


function hapusTicket(button) {
    button.closest('.ticketBlock').remove();
}

function closeModal() {
    document.getElementById('eventModal').classList.add('hidden');
}

function openDetailModal(id) {
    fetch(`/admin/event/${id}`)
    .then(res => res.json())
    .then(event => {
        let html = `
            <p><strong>Nama:</strong> ${event.title}</p>
            <p><strong>Tanggal:</strong> ${event.date}</p>
            <p><strong>Lokasi:</strong> ${event.location}</p>
            <p><strong>Deskripsi:</strong> ${event.description ?? '-'}</p>
            <h4 class="font-bold mt-8 mb-4 text-lg border-b border-gray-300 pb-1">Daftar Tiket</h4>
        `;
        event.tickets.forEach(t => {
            html += `<div class="border p-4 rounded-xl bg-gray-50 mb-3 shadow-sm">
                <p class="font-semibold text-gray-800">${t.name}</p>
                <p class="text-gray-700">Harga: Rp${t.price.toLocaleString('id-ID')}</p>
                <p class="text-gray-700">Stok: ${t.stock}</p>
            </div>`;
        });
        document.getElementById('detailContent').innerHTML = html;
        document.getElementById('detailModal').classList.remove('hidden');
    });
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
}
</script>
@endsection