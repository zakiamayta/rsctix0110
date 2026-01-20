@extends('layouts.eo')

@section('title', 'Tambah Event')

@section('content')
<main class="max-w-5xl mx-auto px-6 py-10">
    <h1 class="text-3xl font-extrabold mb-8">Tambah Event</h1>

    <form method="POST"
        action="{{ route('eo.event.store') }}"
        enctype="multipart/form-data">
        @csrf
        @include('eo.partials.form')


        <div class="flex justify-end gap-4">
            <a href="{{ route('eo.event.index') }}" class="px-6 py-3 bg-gray-200 rounded-xl">
                Batal
            </a>
            <button class="px-6 py-3 bg-blue-600 text-white rounded-xl">
                Simpan Event
            </button>
        </div>
    </form>
</main>
@endsection
