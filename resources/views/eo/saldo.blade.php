@extends('layouts.eo')

@section('title', 'Saldo EO')

@section('content')

{{-- HEADER --}}
<div class="flex justify-between items-end mb-8">

    <div>
        <h1 class="text-2xl font-bold"
            style="font-family:'Sora',sans-serif; color:#1A1208;">
            Saldo EO
        </h1>

        <p class="text-sm mt-1 text-[#7A6E66]">
            Kelola saldo & pengajuan penarikan dana
        </p>
    </div>

    {{-- BUTTON --}}
    <button onclick="openWithdrawModal()"
            class="px-5 py-3 rounded-xl text-sm font-bold text-white"
            style="background:#E8470A;">
        + Tarik Saldo
    </button>

</div>

{{-- ALERT --}}
@if(session('success'))
<div class="mb-5 px-4 py-3 rounded-xl bg-green-100 text-green-700">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-5 px-4 py-3 rounded-xl bg-red-100 text-red-700">
    {{ session('error') }}
</div>
@endif

{{-- INFO REKENING EO --}}
<div class="bg-white border border-[#EDE8E3] rounded-2xl p-5 mb-8">

    <div class="flex items-start justify-between">

        <div>

            <h2 class="text-lg font-bold mb-3"
                style="font-family:'Sora',sans-serif; color:#1A1208;">
                Rekening Withdrawal
            </h2>

            @if(
                $eo->bank_name &&
                $eo->account_name &&
                $eo->account_number
            )

            <div class="space-y-1">

                <div class="text-sm font-semibold text-[#1A1208]">
                    {{ $eo->bank_name }}
                </div>

                <div class="text-sm text-gray-600">
                    {{ $eo->account_number }}
                </div>

                <div class="text-sm text-gray-500">
                    a/n {{ $eo->account_name }}
                </div>

            </div>

            @else

            <div class="text-sm text-red-500 font-medium">
                Data rekening belum dilengkapi.
            </div>

            @endif

        </div>

        <a href="{{ route('eo.profile') }}"
           class="px-4 py-2 rounded-xl text-sm font-semibold border border-[#EDE8E3] hover:bg-[#FAF8F6]">

            Edit Profile

        </a>

    </div>

</div>

{{-- STATS --}}
<div class="grid grid-cols-3 gap-4 mb-8">

    {{-- TOTAL PENDAPATAN --}}
    <div class="bg-[#E8470A] text-white rounded-2xl p-5">

        <p class="text-xs uppercase tracking-wider opacity-70 font-bold mb-2">
            Total Pendapatan
        </p>

        <h2 class="text-3xl font-bold">
            Rp {{ number_format($totalRevenue) }}
        </h2>

    </div>

    {{-- TOTAL WITHDRAW --}}
    <div class="bg-white rounded-2xl p-5 border border-[#EDE8E3]">

        <p class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-2">
            Total Withdrawal
        </p>

        <h2 class="text-3xl font-bold text-[#1A1208]">
            Rp {{ number_format($totalWithdraw) }}
        </h2>

    </div>

    {{-- SALDO --}}
    <div class="bg-white rounded-2xl p-5 border border-[#EDE8E3]">

        <p class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-2">
            Saldo Tersedia
        </p>

        <h2 class="text-3xl font-bold text-green-600">
            Rp {{ number_format($availableBalance) }}
        </h2>

    </div>

</div>

{{-- HISTORY --}}
<div class="bg-white rounded-2xl border border-[#EDE8E3] overflow-hidden">

    {{-- HEADER --}}
    <div class="p-5 border-b border-[#EDE8E3]">

        <h2 class="text-lg font-bold"
            style="font-family:'Sora',sans-serif; color:#1A1208;">
            Riwayat Withdrawal
        </h2>

    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto">

        <table class="w-full">

            <thead class="bg-[#FAF8F6]">

                <tr class="text-xs uppercase tracking-wider text-gray-500">

                    <th class="px-5 py-4 text-left">
                        Tanggal
                    </th>

                    <th class="px-5 py-4 text-left">
                        Rekening
                    </th>

                    <th class="px-5 py-4 text-center">
                        Nominal
                    </th>

                    <th class="px-5 py-4 text-center">
                        Status
                    </th>

                    <th class="px-5 py-4 text-center">
                        Bukti Transfer
                    </th>

                </tr>

            </thead>

            <tbody>

                @forelse($withdrawals as $withdraw)

                <tr class="border-t border-[#F1ECE8]">

                    {{-- DATE --}}
                    <td class="px-5 py-4 text-sm">

                        {{ $withdraw->created_at->format('d M Y H:i') }}

                    </td>

                    {{-- REKENING --}}
                    <td class="px-5 py-4">

                        <div class="text-sm font-semibold">
                            {{ $withdraw->eo->bank_name ?? '-' }}
                        </div>

                        <div class="text-xs text-gray-500">
                            {{ $withdraw->eo->account_number ?? '-' }}
                        </div>

                        <div class="text-xs text-gray-400">
                            {{ $withdraw->eo->account_name ?? '-' }}
                        </div>

                    </td>

                    {{-- AMOUNT --}}
                    <td class="px-5 py-4 text-center font-bold">

                        Rp {{ number_format($withdraw->amount) }}

                    </td>

                    {{-- STATUS --}}
                    <td class="px-5 py-4 text-center">

                        @if($withdraw->status == 'approved')

                        <span class="px-3 py-1 rounded-full text-xs font-bold
                                     bg-green-100 text-green-700">
                            APPROVED
                        </span>

                        @elseif($withdraw->status == 'pending')

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

                    </td>

                    {{-- PROOF --}}
                    <td class="px-5 py-4 text-center">

                        @if($withdraw->transfer_proof)

                        <button
                            onclick="openProofModal('{{ asset($withdraw->transfer_proof) }}')"
                            class="text-[#E8470A] text-sm font-bold hover:underline">

                            Lihat Bukti

                        </button>

                        @else

                        <span class="text-xs text-gray-400">
                            Belum ada
                        </span>

                        @endif

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="5"
                        class="text-center py-10 text-gray-400">

                        Belum ada withdrawal

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

{{-- MODAL BUKTI TRANSFER --}}
<div id="proofModal"
     class="fixed inset-0 bg-black/70 z-[60] hidden items-center justify-center p-4">

    <div class="relative max-w-4xl w-full">

        {{-- CLOSE --}}
        <button onclick="closeProofModal()"
                class="absolute -top-12 right-0 text-white text-3xl hover:text-red-400">
            ✕
        </button>

        {{-- IMAGE --}}
        <img id="proofImage"
             src=""
             class="w-full max-h-[85vh] object-contain rounded-2xl bg-white">

    </div>

</div>

{{-- MODAL WITHDRAW --}}
<div id="withdrawModal"
     class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">

    <div class="bg-white rounded-2xl w-full max-w-lg">

        {{-- HEADER --}}
        <div class="flex justify-between items-center p-5 border-b">

            <h2 class="text-lg font-bold"
                style="font-family:'Sora',sans-serif;">
                Ajukan Withdrawal
            </h2>

            <button onclick="closeWithdrawModal()"
                    class="text-gray-400 hover:text-red-500">
                ✕
            </button>

        </div>

        {{-- FORM --}}
        <form method="POST"
              action="{{ route('eo.withdraw.store') }}"
              class="p-5 space-y-4">

            @csrf

            {{-- INFO REKENING --}}
            <div class="bg-[#FAF8F6] border border-[#EDE8E3] rounded-xl p-4">

                <div class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-2">
                    Transfer ke rekening
                </div>

                <div class="text-sm font-semibold text-[#1A1208]">
                    {{ $eo->bank_name ?? '-' }}
                </div>

                <div class="text-sm text-gray-600">
                    {{ $eo->account_number ?? '-' }}
                </div>

                <div class="text-sm text-gray-500">
                    a/n {{ $eo->account_name ?? '-' }}
                </div>

            </div>

            {{-- AMOUNT --}}
            <div>

                <label class="text-sm font-semibold mb-1 block">
                    Jumlah Withdrawal
                </label>

                <input type="number"
                       name="amount"
                       required
                       min="10000"
                       class="w-full border border-[#EDE8E3] rounded-xl p-3">

            </div>

            {{-- NOTE --}}
            <div>

                <label class="text-sm font-semibold mb-1 block">
                    Catatan
                </label>

                <textarea name="note"
                          rows="3"
                          class="w-full border border-[#EDE8E3] rounded-xl p-3"></textarea>

            </div>

            {{-- ACTION --}}
            <div class="flex justify-end gap-3 pt-2">

                <button type="button"
                        onclick="closeWithdrawModal()"
                        class="px-4 py-2 rounded-xl border border-[#EDE8E3]">

                    Batal

                </button>

                <button class="px-5 py-2 rounded-xl text-white font-bold"
                        style="background:#E8470A;">

                    Ajukan Withdrawal

                </button>

            </div>

        </form>

    </div>

</div>

{{-- SCRIPT --}}
<script>

function openWithdrawModal()
{
    document.getElementById('withdrawModal')
        .classList.remove('hidden');

    document.getElementById('withdrawModal')
        .classList.add('flex');
}

function closeWithdrawModal()
{
    document.getElementById('withdrawModal')
        .classList.add('hidden');

    document.getElementById('withdrawModal')
        .classList.remove('flex');
}

function openProofModal(imageUrl)
{
    const modal = document.getElementById('proofModal');
    const image = document.getElementById('proofImage');

    image.src = imageUrl;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeProofModal()
{
    const modal = document.getElementById('proofModal');

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

window.addEventListener('click', function(e)
{
    const modal = document.getElementById('proofModal');

    if (e.target === modal) {
        closeProofModal();
    }
});

</script>

@endsection