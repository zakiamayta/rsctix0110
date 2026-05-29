@extends('layouts.eo')

@section('title', 'Profil EO')

@section('content')

{{-- HEADER --}}
<div class="flex justify-between items-end mb-8">

    <div>
        <h1 class="text-2xl font-bold"
            style="font-family:'Sora',sans-serif; color:#1A1208;">
            Profil EO
        </h1>

        <p class="text-sm mt-1 text-[#7A6E66]">
            Informasi Event Organizer
        </p>
    </div>

    {{-- BUTTON --}}
    <button onclick="openEditModal()"
            class="px-5 py-3 rounded-xl text-sm font-bold text-white"
            style="background:#E8470A;">
        Edit Profil
    </button>

</div>

{{-- ALERT --}}
@if(session('success'))
<div class="mb-5 px-4 py-3 rounded-xl bg-green-100 text-green-700">
    {{ session('success') }}
</div>
@endif

{{-- PROFILE CARD --}}
<div class="grid grid-cols-3 gap-6">

    {{-- LEFT --}}
    <div class="bg-white rounded-2xl border border-[#EDE8E3] p-6">

        <div class="flex flex-col items-center">

            {{-- LOGO --}}
            @if($eo->logo)

            <img src="{{ asset($eo->logo) }}"
                 class="w-40 h-40 rounded-2xl object-cover mb-5">

            @else

            <div class="w-40 h-40 rounded-2xl bg-[#FFF0EB]
                        flex items-center justify-center mb-5">

                <span class="text-6xl">🎫</span>

            </div>

            @endif

            {{-- NAME --}}
            <h2 class="text-xl font-bold text-center"
                style="font-family:'Sora',sans-serif; color:#1A1208;">

                {{ $eo->nama_badan_usaha }}

            </h2>

            {{-- STATUS --}}
            <div class="mt-3">

                @if($eo->status == 'approved')

                <span class="px-3 py-1 rounded-full text-xs font-bold
                             bg-green-100 text-green-700">

                    APPROVED

                </span>

                @elseif($eo->status == 'pending')

                <span class="px-3 py-1 rounded-full text-xs font-bold
                             bg-yellow-100 text-yellow-700">

                    PENDING

                </span>

                @else

                <span class="px-3 py-1 rounded-full text-xs font-bold
                             bg-red-100 text-red-700">

                    REJECTED

                </span>

                @endif

            </div>

        </div>

    </div>

    {{-- RIGHT --}}
    <div class="col-span-2 space-y-6">

        {{-- INFORMASI EO --}}
        <div class="bg-white rounded-2xl border border-[#EDE8E3] p-6">

            <h2 class="font-bold text-lg mb-5"
                style="font-family:'Sora',sans-serif; color:#1A1208;">

                Informasi EO

            </h2>

            <div class="grid grid-cols-2 gap-5">

                {{-- PHONE --}}
                <div>

                    <p class="text-xs uppercase tracking-wider
                              text-gray-400 font-bold mb-2">

                        Nomor HP

                    </p>

                    <p class="text-sm font-semibold text-[#1A1208]">
                        {{ $eo->user->phone ?? '-' }}
                    </p>

                </div>

            </div>

        </div>

        {{-- REKENING --}}
        <div class="bg-white rounded-2xl border border-[#EDE8E3] p-6">

            <h2 class="font-bold text-lg mb-5"
                style="font-family:'Sora',sans-serif; color:#1A1208;">

                Rekening Withdrawal

            </h2>

            <div class="grid grid-cols-3 gap-5">

                {{-- BANK --}}
                <div>

                    <p class="text-xs uppercase tracking-wider
                              text-gray-400 font-bold mb-2">

                        Nama Bank

                    </p>

                    <p class="text-sm font-semibold text-[#1A1208]">
                        {{ $eo->bank_name ?? '-' }}
                    </p>

                </div>

                {{-- ACCOUNT NAME --}}
                <div>

                    <p class="text-xs uppercase tracking-wider
                              text-gray-400 font-bold mb-2">

                        Nama Pemilik

                    </p>

                    <p class="text-sm font-semibold text-[#1A1208]">
                        {{ $eo->account_name ?? '-' }}
                    </p>

                </div>

                {{-- ACCOUNT NUMBER --}}
                <div>

                    <p class="text-xs uppercase tracking-wider
                              text-gray-400 font-bold mb-2">

                        Nomor Rekening

                    </p>

                    <p class="text-sm font-semibold text-[#1A1208]">
                        {{ $eo->account_number ?? '-' }}
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

{{-- MODAL EDIT --}}
<div id="editModal"
     class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">

    <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">

        {{-- HEADER --}}
        <div class="flex justify-between items-center p-5 border-b">

            <h2 class="text-lg font-bold"
                style="font-family:'Sora',sans-serif;">
                Edit Profil EO
            </h2>

            <button onclick="closeEditModal()"
                    class="text-gray-400 hover:text-red-500">
                ✕
            </button>

        </div>

        {{-- FORM --}}
        <form method="POST"
              action="{{ route('eo.profile.update') }}"
              enctype="multipart/form-data"
              class="p-5 space-y-5">

            @csrf

            {{-- LOGO --}}
            <div>

                <label class="text-sm font-semibold mb-2 block">
                    Logo EO
                </label>

                <input type="file"
                       name="logo"
                       class="w-full border border-[#EDE8E3]
                              rounded-xl p-3">

            </div>

            <div class="grid grid-cols-2 gap-5">

                {{-- NAME --}}
                <div class="col-span-2">

                    <label class="text-sm font-semibold mb-2 block">
                        Nama EO
                    </label>

                    <input type="text"
                        name="nama_badan_usaha"
                        value="{{ $eo->nama_badan_usaha }}"
                        class="w-full border border-[#EDE8E3]
                                rounded-xl p-3">

                </div>

                {{-- PHONE --}}
                <div>

                    <label class="text-sm font-semibold mb-2 block">
                        Nomor HP
                    </label>

                    <input type="text"
                        name="phone"
                        value="{{ $eo->user->phone ?? '' }}"
                        class="w-full border border-[#EDE8E3]
                                rounded-xl p-3">
                </div>


            </div>

            {{-- BANK --}}
            <div class="border-t pt-5">

                <h3 class="font-bold mb-4">
                    Rekening Withdrawal
                </h3>

                <div class="grid grid-cols-3 gap-5">

                    <div>

                        <label class="text-sm font-semibold mb-2 block">
                            Nama Bank
                        </label>

                        <input type="text"
                               name="bank_name"
                               value="{{ $eo->bank_name }}"
                               class="w-full border border-[#EDE8E3]
                                      rounded-xl p-3">

                    </div>

                    <div>

                        <label class="text-sm font-semibold mb-2 block">
                            Nama Pemilik
                        </label>

                        <input type="text"
                               name="account_name"
                               value="{{ $eo->account_name }}"
                               class="w-full border border-[#EDE8E3]
                                      rounded-xl p-3">

                    </div>

                    <div>

                        <label class="text-sm font-semibold mb-2 block">
                            Nomor Rekening
                        </label>

                        <input type="text"
                               name="account_number"
                               value="{{ $eo->account_number }}"
                               class="w-full border border-[#EDE8E3]
                                      rounded-xl p-3">

                    </div>

                </div>

            </div>

            {{-- BUTTON --}}
            <div class="flex justify-end gap-3 pt-4">

                <button type="button"
                        onclick="closeEditModal()"
                        class="px-5 py-2 rounded-xl border border-[#EDE8E3]">

                    Batal

                </button>

                <button class="px-5 py-2 rounded-xl text-white font-bold"
                        style="background:#E8470A;">

                    Simpan Perubahan

                </button>

            </div>

        </form>

    </div>

</div>

{{-- SCRIPT --}}
<script>

function openEditModal()
{
    document.getElementById('editModal')
        .classList.remove('hidden');

    document.getElementById('editModal')
        .classList.add('flex');
}

function closeEditModal()
{
    document.getElementById('editModal')
        .classList.add('hidden');

    document.getElementById('editModal')
        .classList.remove('flex');
}

</script>

@endsection