@extends('layouts.eo')

@section('title', 'Edit Event')

@section('content')
<main class="max-w-5xl mx-auto px-6 py-10">
    <h1 class="text-3xl font-extrabold mb-8">Edit Event</h1>

    <form action="{{ route('eo.event.update', $event->id) }}"
          method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        @include('eo.partials.form', ['event' => $event])

        <div class="flex justify-end gap-4">
            <a href="{{ route('eo.event.index') }}" class="px-6 py-3 bg-gray-200 rounded-xl">
                Batal
            </a>
            <button class="px-6 py-3 bg-green-600 text-white rounded-xl">
                Update Event
            </button>
        </div>
    </form>
</main>
@endsection
