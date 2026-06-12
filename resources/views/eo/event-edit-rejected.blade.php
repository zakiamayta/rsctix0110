@extends('layouts.eo')

@section('content')

<div class="mb-6">
    <h2 class="text-xl font-bold"
        style="font-family:'Sora',sans-serif;">
        Edit & Re-Submit Event
    </h2>

    <p class="text-sm text-gray-500">
        Perbaiki event sesuai catatan admin lalu kirim kembali.
    </p>
</div>

@include('eo.partials.resubmit-form')

@endsection