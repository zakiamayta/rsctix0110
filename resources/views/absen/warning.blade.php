@extends('layouts.app')

@section('title', 'Sudah Registrasi')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-yellow-50">
  <div class="bg-white p-8 rounded shadow-lg text-center max-w-md w-full">
    <h2 class="text-xl font-bold text-yellow-700 mb-4">⚠️ Warning</h2>
    <p class="text-gray-700">{{ $message }}</p>
  </div>
</div>
@endsection
