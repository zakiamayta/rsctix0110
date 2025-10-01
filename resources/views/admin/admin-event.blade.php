@extends('layouts.admin')

@section('title', 'Kelola Event')

@section('content')
<main class="container mx-auto px-4 py-8 md:px-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Kelola Event</h2>
        <button onclick="openAddModal()" 
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-semibold text-sm transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Event
        </button>
    </div>

    {{-- list event --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($events as $event)
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden transition hover:shadow-xl hover:-translate-y-1">
            <div class="relative w-full h-48 bg-gray-100 overflow-hidden">
<img src="{{ $event->poster ? asset($event->poster) : asset('images/no-image.png') }}" 
     alt="{{ $event->title }}" 
     class="w-full h-full object-cover">


            </div>
            <div class="p-6 flex flex-col justify-between h-[220px]">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2 truncate">{{ $event->title }}</h3>
                    <p class="text-sm text-gray-600 line-clamp-3">{{ $event->description }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        📅 {{ $event->date }} <br>
                        📍 {{ $event->location }}
                    </p>
                </div>
                <div class="flex gap-2 mt-4">
                    <button onclick="openDetailModal({{ $event->id }})" 
                        class="flex-1 bg-blue-100 text-blue-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-blue-200">Detail</button>
                    <button onclick="openEditModal({{ $event->id }})" 
                        class="flex-1 bg-green-100 text-green-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-green-200">Edit</button>
                    <form method="POST" action="{{ route('admin.event.destroy', $event->id) }}" 
                        onsubmit="return confirm('Hapus event ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                            class="flex-1 bg-red-100 text-red-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-red-200">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-16 text-gray-500 text-lg font-medium">
            Belum ada event terdaftar.
        </div>
        @endforelse
    </div>
</main>

{{-- Modal Add/Edit Event --}}
<div id="eventModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4">
    <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl p-8 overflow-y-auto max-h-[90vh]" id="modalContent">
        <h3 id="modalTitle" class="text-2xl font-bold mb-6">Tambah Event</h3>
        <form id="eventForm" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold">Judul Event</label>
                    <input type="text" name="title" id="eventTitle" class="form-input w-full rounded-lg border-gray-300 p-2" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Tanggal & Waktu</label>
                    <input type="datetime-local" name="date" id="eventDate" class="form-input w-full rounded-lg border-gray-300 p-2" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Lokasi</label>
                    <input type="text" name="location" id="eventLocation" class="form-input w-full rounded-lg border-gray-300 p-2" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Poster</label>
                    <input type="file" name="poster" class="form-input w-full rounded-lg border-gray-300 p-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold">Deskripsi</label>
                <textarea name="description" id="eventDescription" rows="3" class="form-textarea w-full rounded-lg border-gray-300 p-2"></textarea>
            </div>

            <h4 class="text-lg font-bold text-gray-700">Daftar Tiket</h4>
            <div id="ticketsContainer" class="space-y-4"></div>
            <button type="button" onclick="tambahTicket()" 
                class="w-full bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold px-4 py-2 rounded-lg transition">
                + Tambah Tiket
            </button>

            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeModal()" 
                    class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">Batal</button>
                <button type="submit" 
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Simpan Event</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Detail --}}
<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl p-8 overflow-y-auto max-h-[90vh]" id="detailModalContent">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold">Detail Event</h3>
            <button type="button" onclick="closeDetailModal()" class="text-gray-500 hover:text-gray-800">✕</button>
        </div>
        <div id="detailContent" class="text-sm text-gray-700 space-y-4"></div>
        <div class="flex justify-end mt-6">
            <button type="button" onclick="closeDetailModal()" 
                    class="px-6 py-3 bg-gray-200 hover:bg-gray-300 rounded-xl font-medium">Tutup</button>
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
    tambahTicket(); // minimal 1 tiket default
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
    let html = `<div class="ticketBlock border p-4 rounded-lg bg-gray-50 shadow-sm">
        <label class="block text-sm font-semibold">Nama Tiket</label>
        <input type="text" name="tickets[${ticketIndex}][name]" class="form-input w-full rounded-lg border-gray-300 mb-2" value="${ticket ? ticket.name : ''}" required>
        
        <label class="block text-sm font-semibold">Harga</label>
        <input type="number" name="tickets[${ticketIndex}][price]" class="form-input w-full rounded-lg border-gray-300 mb-2" value="${ticket ? ticket.price : ''}" required>
        
        <label class="block text-sm font-semibold">Stok</label>
        <input type="number" name="tickets[${ticketIndex}][stock]" class="form-input w-full rounded-lg border-gray-300 mb-2" value="${ticket ? ticket.stock : ''}" required>
    </div>`;
    document.getElementById('ticketsContainer').insertAdjacentHTML('beforeend', html);
    ticketIndex++;
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
            <h4 class="font-bold mt-6 mb-2">Daftar Tiket</h4>
        `;
        event.tickets.forEach(t => {
            html += `<div class="border p-3 rounded-lg bg-gray-50 mb-2">
                <p><strong>${t.name}</strong></p>
                <p>Harga: Rp${t.price}</p>
                <p>Stok: ${t.stock}</p>
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
