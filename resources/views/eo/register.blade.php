@extends('layouts.app')

@section('title', 'Daftar Event Organizer')

@section('content')

<div class="min-h-screen bg-[#FAF8F6] py-10">

    <div class="max-w-3xl mx-auto">

        {{-- HEADER --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#1A1208]"
                style="font-family:'Sora',sans-serif;">
                Registrasi Event Organizer
            </h1>

            <p class="text-sm text-gray-500 mt-2">
                Lengkapi data untuk mulai membuat dan mengelola event
            </p>
        </div>

        <form action="{{ route('eo.store') }}" method="POST"
              enctype="multipart/form-data"
              class="bg-white rounded-2xl shadow-lg border border-[#EDE8E3] overflow-hidden">

            @csrf

            {{-- ===================== --}}
            {{-- SECTION 1: LEGAL --}}
            {{-- ===================== --}}
            <div class="p-6 border-b bg-[#FFF9F5]">

                <h2 class="text-lg font-bold text-[#1A1208] mb-4">
                    🏢 Informasi Legal
                </h2>

                <div class="grid grid-cols-1 gap-4">

                    <input type="text"
                           name="nama_badan_usaha"
                           placeholder="Nama Badan Usaha"
                           class="w-full p-3 rounded-xl border focus:ring-2 focus:ring-[#f97316]"
                           required>

                    <textarea name="alamat_badan_usaha"
                              placeholder="Alamat Badan Usaha"
                              class="w-full p-3 rounded-xl border focus:ring-2 focus:ring-[#f97316]"
                              rows="3"
                              required></textarea>

                    <input type="text"
                           name="penanggung_jawab"
                           placeholder="Nama Penanggung Jawab"
                           class="w-full p-3 rounded-xl border focus:ring-2 focus:ring-[#f97316]"
                           required>

                    <div>
                        <label class="text-sm font-semibold text-gray-600">
                            Dokumen Legal (PDF / Image)
                        </label>

                        <input type="file"
                               name="dokumen_badan_usaha"
                               class="w-full mt-2 p-3 border rounded-xl bg-white"
                               accept=".pdf,.jpg,.png,.jpeg"
                               required>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-600">
                            Upload KTP
                        </label>

                        <input type="file"
                               name="ktp_penanggung_jawab"
                               class="w-full mt-2 p-3 border rounded-xl bg-white"
                               accept=".pdf,.jpg,.png,.jpeg"
                               required>
                    </div>

                </div>
            </div>

            {{-- ===================== --}}
            {{-- SECTION 2: BANK --}}
            {{-- ===================== --}}
            <div class="p-6">

                <h2 class="text-lg font-bold text-[#1A1208] mb-4">
                    💳 Informasi Rekening
                </h2>

                {{-- BANK SEARCH --}}
                <div class="relative mb-4">

                    <input type="text"
                           name="bank_name"
                           list="bankList"
                           placeholder="Cari bank (contoh: BCA, Mandiri...)"
                           class="w-full p-3 pl-10 rounded-xl border focus:ring-2 focus:ring-[#f97316]"
                           required>

                    <span class="absolute left-3 top-3.5 text-gray-400">
                        🔎
                    </span>

                    <datalist id="bankList">
                        @foreach(array_merge(config('banks.primary'), config('banks.others')) as $bank)
                            <option value="{{ $bank }}"></option>
                        @endforeach
                    </datalist>

                </div>

                <div class="grid grid-cols-1 gap-4">

                    <input type="text"
                           name="account_name"
                           placeholder="Nama Pemilik Rekening"
                           class="w-full p-3 rounded-xl border focus:ring-2 focus:ring-[#f97316]"
                           required>

                    <input type="text"
                           name="account_number"
                           placeholder="Nomor Rekening"
                           class="w-full p-3 rounded-xl border focus:ring-2 focus:ring-[#f97316]"
                           required>

                </div>

            </div>

            {{-- ACTION --}}
            <div class="p-6 bg-[#FAF8F6] flex justify-between items-center">

                <a href="{{ route('home') }}"
                   class="px-5 py-3 rounded-xl border bg-white hover:bg-gray-50 text-sm font-semibold">

                    ← Kembali

                </a>

                <button class="px-6 py-3 rounded-xl text-white font-bold text-sm"
                        style="background:#f97316;">

                    Daftar EO

                </button>

            </div>

        </form>

    </div>
</div>

@endsection