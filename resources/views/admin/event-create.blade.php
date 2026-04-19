@extends('layouts.admin')

@section('title', 'Tambah Event')

@section('content')

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Tambah Event</h2>
    <p class="text-sm text-gray-500">Buat event beserta jadwal & tiket</p>
</div>

{{-- ERROR VALIDATION --}}
@if ($errors->any())
<div class="bg-red-100 border border-red-300 text-red-700 p-3 rounded mb-4">
    <ul class="text-sm">
        @foreach ($errors->all() as $error)
            <li>• {{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('admin.event.store') }}" method="POST"
      enctype="multipart/form-data"
      class="bg-white p-6 rounded-xl shadow space-y-6">
@csrf

{{-- EVENT --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <label class="text-sm font-semibold">Judul Event</label>
        <input type="text" name="title"
               class="w-full border p-2 rounded mt-1" required>
    </div>

    <div>
        <label class="text-sm font-semibold">Tanggal Event</label>
        <input type="date" name="date"
               class="w-full border p-2 rounded mt-1" required>
    </div>

    <div class="md:col-span-2">
        <label class="text-sm font-semibold">Lokasi</label>
        <input type="text" name="location"
               class="w-full border p-2 rounded mt-1" required>
    </div>

    <div class="md:col-span-2">
        <label class="text-sm font-semibold">Deskripsi</label>
        <textarea name="description"
                  class="w-full border p-2 rounded mt-1"></textarea>
    </div>

    {{-- POSTER --}}
    <div class="md:col-span-2">
        <label class="text-sm font-semibold">Poster Event</label>

        <input type="file" name="poster" accept="image/*"
               onchange="previewImage(event)"
               class="w-full border p-2 rounded mt-1">

        <p class="text-xs text-gray-400 mt-1">
            Format: JPG, PNG (Max 2MB)
        </p>

        <div class="mt-3">
            <img id="posterPreview"
                 class="w-48 h-64 object-cover rounded-lg border hidden">
        </div>
    </div>

</div>

{{-- JADWAL --}}
<div>
    <div class="flex justify-between items-center mb-3">
        <h3 class="font-bold text-lg text-gray-800">Jadwal Event</h3>
        <button type="button" onclick="addJadwal()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm">
            + Tambah Jadwal
        </button>
    </div>

    <div id="jadwal-wrapper" class="space-y-6"></div>
</div>

{{-- ACTION --}}
<div class="flex justify-between">
    <a href="{{ route('admin.event.index') }}"
       class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
        ← Kembali
    </a>

    <button type="submit"
            onclick="this.disabled=true; this.innerText='Menyimpan...'; this.form.submit();"
            class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded">
        Simpan Event
    </button>
</div>

</form>

{{-- SCRIPT --}}
<script>
let jadwalIndex = 0;

function addJadwal() {
    const wrapper = document.getElementById('jadwal-wrapper');

    const html = `
    <div class="border p-4 rounded-xl bg-gray-50 shadow-sm jadwal-item">

        <div class="flex justify-between mb-3">
            <h4 class="font-semibold text-gray-700">
                Jadwal #${jadwalIndex + 1}
            </h4>
            <button type="button"
                    onclick="this.closest('.jadwal-item').remove()"
                    class="text-red-500 text-sm hover:underline">
                Hapus
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
            <input type="text"
                   name="jadwal[${jadwalIndex}][info]"
                   placeholder="Contoh: Day 1 / Sesi 1"
                   class="border p-2 rounded" required>

            <input type="date"
                   name="jadwal[${jadwalIndex}][tanggal]"
                   class="border p-2 rounded" required>
        </div>

        <div class="bg-white p-3 rounded-lg border">
            <div class="flex justify-between mb-2">
                <p class="text-sm font-semibold text-gray-700">Tiket</p>
                <button type="button"
                        onclick="addTicket(${jadwalIndex})"
                        class="text-blue-600 text-sm hover:underline">
                    + Tambah Tiket
                </button>
            </div>

            <div id="ticket-wrapper-${jadwalIndex}" class="space-y-3"></div>
        </div>
    </div>
    `;

    wrapper.insertAdjacentHTML('beforeend', html);

    addTicket(jadwalIndex);
    jadwalIndex++;
}

function addTicket(jadwalIdx) {
    const wrapper = document.getElementById(`ticket-wrapper-${jadwalIdx}`);
    const ticketId = Date.now(); // unik

    const html = `
    <div class="border p-3 rounded-lg bg-gray-50 ticket-item">

        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-2">
            <input type="text"
                   name="jadwal[${jadwalIdx}][tickets][${ticketId}][name]"
                   placeholder="Nama Tiket"
                   class="border p-2 rounded" required>

            <input type="number"
                   name="jadwal[${jadwalIdx}][tickets][${ticketId}][price]"
                   placeholder="Harga"
                   class="border p-2 rounded" required>

            <input type="number"
                   name="jadwal[${jadwalIdx}][tickets][${ticketId}][stock]"
                   placeholder="Stock"
                   class="border p-2 rounded" required>

            <button type="button"
                    onclick="this.closest('.ticket-item').remove()"
                    class="bg-red-500 hover:bg-red-600 text-white rounded px-2">
                Hapus
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <input type="datetime-local"
                   name="jadwal[${jadwalIdx}][tickets][${ticketId}][start_sale]"
                   class="border p-2 rounded">

            <input type="datetime-local"
                   name="jadwal[${jadwalIdx}][tickets][${ticketId}][end_sale]"
                   class="border p-2 rounded">
        </div>
    </div>
    `;

    wrapper.insertAdjacentHTML('beforeend', html);
}

function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('posterPreview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        }

        reader.readAsDataURL(input.files[0]);
    }
}

// AUTO LOAD 1 JADWAL
document.addEventListener('DOMContentLoaded', function() {
    addJadwal();
});
</script>

@endsection