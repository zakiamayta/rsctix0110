@extends('layouts.owner')

@section('title', 'Approval Withdrawal')

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold">
        Approval Penarikan Saldo
    </h1>

    <p class="text-sm text-gray-500 mt-1">
        Kelola pengajuan penarikan saldo EO
    </p>
</div>

@if(session('success'))
<div class="mb-5 bg-green-100 text-green-700 px-4 py-3 rounded-xl">
    {{ session('success') }}
</div>
@endif

<div class="bg-white rounded-2xl overflow-hidden border">

    <table class="w-full">

        <thead class="bg-gray-50">
            <tr class="text-sm text-gray-500">
                <th class="px-5 py-4 text-left">EO</th>
                <th class="px-5 py-4 text-center">Jumlah</th>
                <th class="px-5 py-4 text-center">Tanggal</th>
                <th class="px-5 py-4 text-center">Status</th>
                <th class="px-5 py-4 text-center">Action</th>
            </tr>
        </thead>

        <tbody>

            @forelse($withdrawals as $withdrawal)

            <tr class="border-t">

                <td class="px-5 py-4">
                    {{ $withdrawal->eo->nama_badan_usaha ?? '-' }}
                </td>

                <td class="px-5 py-4 text-center font-bold">
                    Rp {{ number_format($withdrawal->amount) }}
                </td>

                <td class="px-5 py-4 text-center">
                    {{ $withdrawal->created_at->format('d M Y H:i') }}
                </td>

                <td class="px-5 py-4 text-center">

                    @if($withdrawal->status == 'pending')

                        <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold">
                            PENDING
                        </span>

                    @elseif($withdrawal->status == 'approved')

                        <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold">
                            APPROVED
                        </span>

                    @else

                        <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold">
                            REJECTED
                        </span>

                    @endif

                </td>

                <td class="px-5 py-4 text-center">

                    <a href="{{ route('owner.withdrawals.show', $withdrawal->id) }}"
                       class="px-4 py-2 rounded-lg bg-orange-500 text-white text-sm font-bold">
                        Detail
                    </a>

                </td>

            </tr>

            @empty

            <tr>
                <td colspan="5" class="text-center py-10 text-gray-400">
                    Belum ada pengajuan penarikan
                </td>
            </tr>

            @endforelse

        </tbody>

    </table>

</div>

@endsection