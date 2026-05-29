@extends('layouts.owner')

@section('title', 'Detail Withdrawal')

@section('content')

<div class="max-w-4xl mx-auto">

    {{-- HEADER --}}
    <div class="flex justify-between items-start mb-6">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">
                Detail Penarikan Saldo
            </h2>

            <p class="text-sm text-gray-500 mt-1">
                Approval penarikan saldo EO
            </p>
        </div>

        {{-- STATUS --}}
        @if($withdrawal->status == 'pending')

            <span class="px-4 py-2 rounded-full text-sm font-bold
                         bg-yellow-100 text-yellow-700">
                PENDING
            </span>

        @elseif($withdrawal->status == 'approved')

            <span class="px-4 py-2 rounded-full text-sm font-bold
                         bg-green-100 text-green-700">
                APPROVED
            </span>

        @else

            <span class="px-4 py-2 rounded-full text-sm font-bold
                         bg-red-100 text-red-700">
                REJECTED
            </span>

        @endif

    </div>

    {{-- CARD --}}
    <div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">

        {{-- BODY --}}
        <div class="p-6 space-y-6">

            {{-- EO --}}
            <div>
                <p class="text-xs uppercase text-gray-400 font-bold mb-1">
                    Event Organizer
                </p>

                <h3 class="text-lg font-bold text-gray-800">
                    {{ $withdrawal->eo->nama_badan_usaha }}
                </h3>
            </div>

            {{-- JUMLAH --}}
            <div>
                <p class="text-xs uppercase text-gray-400 font-bold mb-1">
                    Jumlah Penarikan
                </p>

                <h3 class="text-3xl font-bold text-orange-500">
                    Rp {{ number_format($withdrawal->amount) }}
                </h3>
            </div>

            {{-- INFORMASI REKENING EO --}}
            <div class="grid grid-cols-3 gap-4">

                {{-- BANK --}}
                <div class="bg-gray-50 rounded-xl p-4">

                    <p class="text-xs text-gray-400 mb-1">
                        Bank
                    </p>

                    <p class="font-bold text-gray-800">
                        {{ $withdrawal->eo->bank_name ?? '-' }}
                    </p>

                </div>

                {{-- NAMA REKENING --}}
                <div class="bg-gray-50 rounded-xl p-4">

                    <p class="text-xs text-gray-400 mb-1">
                        Nama Rekening
                    </p>

                    <p class="font-bold text-gray-800">
                        {{ $withdrawal->eo->account_name ?? '-' }}
                    </p>

                </div>

                {{-- NOMOR REKENING --}}
                <div class="bg-gray-50 rounded-xl p-4">

                    <p class="text-xs text-gray-400 mb-1">
                        Nomor Rekening
                    </p>

                    <p class="font-bold text-gray-800">
                        {{ $withdrawal->eo->account_number ?? '-' }}
                    </p>

                </div>

            </div>

            {{-- NOTE --}}
            <div>
                <p class="text-xs uppercase text-gray-400 font-bold mb-2">
                    Catatan EO
                </p>

                <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-700">
                    {{ $withdrawal->note ?? '-' }}
                </div>
            </div>

            {{-- BUKTI TRANSFER --}}
            @if($withdrawal->transfer_proof)

            <div>
                <p class="text-xs uppercase text-gray-400 font-bold mb-2">
                    Bukti Transfer
                </p>

                <img src="{{ asset($withdrawal->transfer_proof) }}"
                     class="rounded-xl border max-w-md">
            </div>

            @endif

            {{-- APPROVED DATE --}}
            @if($withdrawal->approved_at)

            <div>
                <p class="text-xs uppercase text-gray-400 font-bold mb-1">
                    Approved At
                </p>

                <p class="text-sm text-gray-700">
                    {{ $withdrawal->approved_at }}
                </p>
            </div>

            @endif

        </div>

    </div>

    {{-- ACTION --}}
    @if($withdrawal->status == 'pending')

    <div class="grid grid-cols-2 gap-4 mt-6">

        {{-- APPROVE --}}
        <form method="POST"
              action="{{ route('owner.withdrawals.approve', $withdrawal->id) }}"
              enctype="multipart/form-data"
              class="bg-white border border-gray-200 rounded-2xl p-5">

            @csrf

            <h3 class="font-bold text-lg mb-4 text-green-600">
                Approve Withdrawal
            </h3>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">
                    Upload Bukti Transfer
                </label>

                <input type="file"
                       name="transfer_proof"
                       required
                       class="w-full border rounded-lg p-2">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">
                    Catatan
                </label>

                <textarea name="note"
                          rows="3"
                          class="w-full border rounded-lg p-3"></textarea>
            </div>

            <button class="w-full bg-green-600 hover:bg-green-700
                           text-white font-bold py-3 rounded-xl">
                Approve Withdrawal
            </button>

        </form>

        {{-- REJECT --}}
        <form method="POST"
              action="{{ route('owner.withdrawals.reject', $withdrawal->id) }}"
              class="bg-white border border-gray-200 rounded-2xl p-5">

            @csrf

            <h3 class="font-bold text-lg mb-4 text-red-600">
                Reject Withdrawal
            </h3>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">
                    Alasan Penolakan
                </label>

                <textarea name="note"
                          rows="6"
                          required
                          class="w-full border rounded-lg p-3"></textarea>
            </div>

            <button class="w-full bg-red-600 hover:bg-red-700
                           text-white font-bold py-3 rounded-xl">
                Reject Withdrawal
            </button>

        </form>

    </div>

    @endif

</div>

@endsection