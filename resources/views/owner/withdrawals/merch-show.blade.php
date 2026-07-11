@extends('layouts.owner')

@section('title', 'Detail Withdrawal Merchandise')

@section('content')

<div class="max-w-6xl mx-auto">

    <div class="flex justify-between items-start mb-6">

        <div>
            <h1 class="text-2xl font-bold">
                Detail Pengajuan Withdrawal Merchandise
            </h1>

            <p class="text-gray-500 text-sm mt-1">
                Review dan approval pencairan dana penjualan merchandise EO
            </p>
        </div>

        @if($withdrawal->status == 'pending')

            <span class="px-4 py-2 rounded-full bg-yellow-100 text-yellow-700 font-bold text-sm">
                PENDING
            </span>

        @elseif($withdrawal->status == 'approved')

            <span class="px-4 py-2 rounded-full bg-green-100 text-green-700 font-bold text-sm">
                APPROVED
            </span>

        @else

            <span class="px-4 py-2 rounded-full bg-red-100 text-red-700 font-bold text-sm">
                REJECTED
            </span>

        @endif

    </div>

    {{-- Alert Error / Validation --}}
    @if(session('error'))
    <div class="mb-5 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
        {{ session('error') }}
    </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- LEFT --}}
        <div class="lg:col-span-2">

            <div class="bg-white rounded-2xl border overflow-hidden">

                <div class="p-6 border-b">

                    <h2 class="font-bold text-lg">
                        Informasi Penarikan Merchandise
                    </h2>

                </div>

                <div class="p-6 space-y-5">

                    <div>
                        <label class="text-xs uppercase text-gray-400 font-bold">
                            Event
                        </label>

                        <div class="font-bold text-lg">
                            {{ $withdrawal->event->title ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <label class="text-xs uppercase text-gray-400 font-bold">
                            Event Organizer
                        </label>

                        <div class="font-semibold">
                            {{ $withdrawal->eo->nama_badan_usaha ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <label class="text-xs uppercase text-gray-400 font-bold">
                            Jumlah Withdrawal
                        </label>

                        <div class="text-3xl font-bold text-orange-500">
                            Rp {{ number_format($withdrawal->amount,0,',','.') }}
                        </div>
                    </div>

                    <div>
                        <label class="text-xs uppercase text-gray-400 font-bold">
                            Catatan EO
                        </label>

                        <div class="bg-gray-50 p-4 rounded-xl mt-2">
                            {{ $withdrawal->note ?: '-' }}
                        </div>
                    </div>

                    @if($withdrawal->owner_note)

                    <div>
                        <label class="text-xs uppercase text-gray-400 font-bold">
                            Catatan Owner
                        </label>

                        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-xl mt-2">
                            {{ $withdrawal->owner_note }}
                        </div>
                    </div>

                    @endif

                    <div>
                        <label class="text-xs uppercase text-gray-400 font-bold">
                            Tanggal Pengajuan
                        </label>

                        <div>
                            {{ $withdrawal->created_at->format('d M Y H:i') }}
                        </div>
                    </div>

                    @if($withdrawal->approved_at)

                    <div>
                        <label class="text-xs uppercase text-gray-400 font-bold">
                            Tanggal Approval
                        </label>

                        <div>
                            {{ \Carbon\Carbon::parse($withdrawal->approved_at)->format('d M Y H:i') }}
                        </div>
                    </div>

                    @endif

                </div>

            </div>

            {{-- INVOICE --}}
            <div class="bg-white rounded-2xl border mt-6 overflow-hidden">

                <div class="p-6 border-b">

                    <h2 class="font-bold">
                        Invoice EO (Merchandise)
                    </h2>

                </div>

                <div class="p-6">

                    @if($withdrawal->invoice_file)

                        <a
                            href="{{ asset($withdrawal->invoice_file) }}"
                            target="_blank"
                            class="inline-flex items-center px-5 py-3 bg-orange-500 text-white rounded-xl font-semibold"
                        >
                            Lihat Invoice
                        </a>

                    @else

                        <div class="text-red-500">
                            Invoice tidak ditemukan
                        </div>

                    @endif

                </div>

            </div>

            {{-- BUKTI TRANSFER --}}
            @if($withdrawal->transfer_proof)

            <div class="bg-white rounded-2xl border mt-6 overflow-hidden">

                <div class="p-6 border-b">

                    <h2 class="font-bold">
                        Bukti Transfer Owner
                    </h2>

                </div>

                <div class="p-6">

                    <a
                        href="{{ asset($withdrawal->transfer_proof) }}"
                        target="_blank"
                        class="inline-flex items-center px-5 py-3 bg-green-600 text-white rounded-xl font-semibold"
                    >
                        Lihat Bukti Transfer
                    </a>

                </div>

            </div>

            @endif

        </div>

        {{-- RIGHT --}}
        <div>

            {{-- WALLET --}}
            <div class="bg-white rounded-2xl border overflow-hidden">

                <div class="p-5 border-b">
                    <h2 class="font-bold">
                        Wallet Merchandise Event
                    </h2>
                </div>

                <div class="p-5 space-y-4">

                    <div>
                        <div class="text-xs text-gray-400">
                            Available Balance
                        </div>

                        <div class="font-bold text-green-600 text-lg">
                            Rp {{ number_format($wallet->available_balance ?? 0,0,',','.') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-400">
                            Held Balance
                        </div>

                        <div class="font-bold text-orange-500">
                            Rp {{ number_format($wallet->held_balance ?? 0,0,',','.') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-400">
                            Negative Balance
                        </div>

                        <div class="font-bold text-red-500">
                            Rp {{ number_format($wallet->negative_balance ?? 0,0,',','.') }}
                        </div>
                    </div>

                </div>

            </div>

            {{-- REKENING EO --}}
            <div class="bg-white rounded-2xl border mt-6 overflow-hidden">

                <div class="p-5 border-b">
                    <h2 class="font-bold">
                        Rekening Tujuan EO
                    </h2>
                </div>

                <div class="p-5 space-y-3">

                    <div>
                        <div class="text-xs text-gray-400">
                            Bank
                        </div>

                        <div class="font-semibold">
                            {{ $withdrawal->eo->bank_name ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-400">
                            Nama Rekening
                        </div>

                        <div class="font-semibold">
                            {{ $withdrawal->eo->account_name ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-400">
                            Nomor Rekening
                        </div>

                        <div class="font-semibold">
                            {{ $withdrawal->eo->account_number ?? '-' }}
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    @if($withdrawal->status == 'pending')

    <div class="grid md:grid-cols-2 gap-6 mt-8">

        {{-- APPROVE --}}
        <form
            action="{{ route('owner.withdrawals.merch.approve', $withdrawal->id) }}"
            method="POST"
            enctype="multipart/form-data"
            class="bg-white rounded-2xl border p-6"
        >
            @csrf

            <h2 class="font-bold text-green-600 text-lg mb-4">
                Approve Withdrawal Merchandise
            </h2>

            <div class="mb-4">

                <label class="block mb-2 text-sm font-medium">
                    Bukti Transfer
                </label>

                <input
                    type="file"
                    name="transfer_proof"
                    required
                    class="w-full border rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-green-500"
                >

            </div>

            <div class="mb-5">

                <label class="block mb-2 text-sm font-medium">
                    Catatan Approval
                </label>

                <textarea
                    name="owner_note"
                    rows="4"
                    class="w-full border rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-green-500"
                ></textarea>

            </div>

            <button
                type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl font-bold transition-colors"
            >
                Approve & Cairkan Dana
            </button>

        </form>

        {{-- REJECT --}}
        <form
            action="{{ route('owner.withdrawals.merch.reject', $withdrawal->id) }}"
            method="POST"
            class="bg-white rounded-2xl border p-6"
        >
            @csrf

            <h2 class="font-bold text-red-600 text-lg mb-4">
                Reject Withdrawal Merchandise
            </h2>

            <div class="mb-5">

                <label class="block mb-2 text-sm font-medium">
                    Alasan Penolakan
                </label>

                <textarea
                    name="owner_note"
                    required
                    rows="8"
                    class="w-full border rounded-lg p-3 text-sm focus:outline-none focus:ring-1 focus:ring-red-500"
                    placeholder="Wajib menuliskan alasan penarikan dana ditolak..."
                ></textarea>

            </div>

            <button
                type="submit"
                class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl font-bold transition-colors"
            >
                Reject Withdrawal
            </button>

        </form>

    </div>

    @endif

</div>

@endsection