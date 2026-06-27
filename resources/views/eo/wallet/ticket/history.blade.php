@extends('layouts.eo')

@section('title','Riwayat Withdraw')

@section('content')

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Riwayat Withdraw</h3>
        <a href="{{ route('eo.ticket-wallet.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            Kembali ke Dashboard Saldo
        </a>
    </div>

    <div class="card shadow-sm border-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">
                <tr>
                    <th>Tanggal Pengajuan</th>
                    <th>Nama Event</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
                </thead>

                <tbody>

                @forelse($withdrawals as $item)

                    <tr>

                        <td>
                            {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') }}
                        </td>

                        <td>
                            {{ $item->event_name }}
                        </td>

                        <td class="fw-bold text-dark">
                            Rp {{ number_format($item->amount, 0, ',', '.') }}
                        </td>

                        <td>
                            @if($item->status == 'approved')
                                <span class="badge bg-success px-3 py-2">
                                    Approved
                                </span>
                            @elseif($item->status == 'rejected')
                                <span class="badge bg-danger px-3 py-2">
                                    Rejected
                                </span>
                            @else
                                <span class="badge bg-warning text-dark px-3 py-2">
                                    Pending
                                </span>
                            @endif
                        </td>
                        
                        <td class="text-center">
                            <a href="{{ route('eo.ticket-history.show', $item->id) }}" class="btn btn-sm btn-primary">
                                Detail
                            </a>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            Belum ada riwayat transaksi penarikan dana.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

    @if(method_exists($withdrawals, 'links'))
        <div class="mt-3">
            {{ $withdrawals->links() }}
        </div>
    @endif

</div>

@endsection