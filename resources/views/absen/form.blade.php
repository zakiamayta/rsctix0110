@extends('layouts.app')

@section('title', 'Konfirmasi Absensi')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100">
  <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
    <h2 class="text-xl font-bold text-center text-gray-800 mb-6">Konfirmasi Absensi</h2>

    @if(session('error'))
      <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">
        {{ session('error') }}
      </div>
    @endif

<form action="{{ route('absen.submit', ['kode' => $transaction->kode_unik]) }}" method="POST" class="space-y-4">
    @csrf


      <div>
        <label class="block text-sm font-medium text-gray-700">Password Petugas</label>
        <input type="password" name="password" required class="mt-1 w-full px-3 py-2 border rounded-md shadow-sm" />
      </div>    

      <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition">Konfirmasi dan Absen</button>
    </form>
  </div>
</div>
@endsection
