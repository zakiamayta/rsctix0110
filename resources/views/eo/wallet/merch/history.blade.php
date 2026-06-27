@extends('layouts.eo')

@section('title','Riwayat Withdraw Merchandise')

@section('content')

<div class="container py-4">

    <h3 class="mb-4 fw-bold">
        Riwayat Withdraw Merch
    </h3>

    <div class="card border-0 shadow-sm">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light text-uppercase small font-monospace">
                <tr>
                    <th class="ps-3">Tanggal</th>
                    <th>Nama Event</th>
                    <th>Nominal Penarikan</th>
                    <th>Status Berjalan</th>
                    <th class="text-center">Aksi</th>
                </tr>
                </thead>

                <tbody>

                @forelse($history as $item)

                    <tr>

                        <td class="ps-3">
                            {{ CarbonCarbon::parse($item->created_at)->format('d M Y H:i') }}
                        </td>

                        <td class="fw-semibold">
                            {{ $item->event_name ?? 'Event Tidak Diketahui' }}
                        </td>

                        <td class="text-success fw-bold">
                            Rp {{ number_format($item->amount, 0, ',', '.') }}
                        </td>

                        <td>
                            @if($item->status == 'approved')
                                <span class="badge bg-success px-2 py-1.5">
                                    Approved
                                </span>
                            @elif($item->status == 'rejected')
                                <span class="badge bg-danger px-2 py-1.5">
                                    Rejected
                                </span>
                            @else
                                <span class="badge bg-warning text-dark px-2 py-1.5">
                                    Pending
                                </span>
                            @endif
                        </td>

                        <td class="text-center">
                            <a href="{{ route('eo.merch-withdrawal.show', $item->id) }}" class="btn btn-sm btn-outline-primary px-3">
                                Detail
                            </a>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            Belum ditemukan riwayat transaksi penarikan merch.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection