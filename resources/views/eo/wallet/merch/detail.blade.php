@extends('layouts.eo')

@section('title', 'Detail Withdrawal Merchandise')

@section('content')

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="fw-bold mb-0">
                Detail Withdrawal Merch
            </h3>
            <small class="text-muted">
                Informasi lengkap pengajuan pencairan komisi merchandise
            </small>
        </div>

        <a href="{{ route('eo.merch-wallet.dashboard') }}"
           class="btn btn-outline-secondary">
            Kembali
        </a>

    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <h5 class="mb-0 fw-bold">
                        {{ $withdrawal->event_title ?? 'Event Tidak Diketahui' }}
                    </h5>
                </div>

                @if($withdrawal->status == 'pending')
                    <span class="badge bg-warning text-dark px-3 py-2">
                        PENDING
                    </span>
                @elseif($withdrawal->status == 'approved')
                    <span class="badge bg-success px-3 py-2">
                        APPROVED
                    </span>
                @else
                    <span class="badge bg-danger px-3 py-2">
                        REJECTED
                    </span>
                @endif

            </div>

        </div>

        <div class="card-body p-4">

            <div class="row">

                <div class="col-md-6 mb-3">
                    <h6 class="text-muted small mb-1">Nominal Dana Diperoleh</h6>
                    <h4 class="fw-bold text-success">
                        Rp {{ number_format($withdrawal->amount, 0, ',', '.') }}
                    </h4>
                </div>

                <div class="col-md-6 mb-3">
                    <h6 class="text-muted small mb-1">Tanggal Pengajuan</h6>
                    <div class="fw-semibold">
                        {{ CarbonCarbon::parse($withdrawal->created_at)->format('d M Y, H:i') }} WIB
                    </div>
                </div>

            </div>

            <hr>

            <div class="row">

                <div class="col-12 mb-3">
                    <h6 class="text-muted small mb-2">Catatan Riwayat Transaksi</h6>
                    <div class="p-3 bg-light rounded text-dark" style="white-space: pre-line;">
                        {{ $withdrawal->note ?? 'Tidak ada catatan tambahan.' }}
                    </div>
                </div>

            </div>

            <hr>

            {{-- INVOICE --}}
            <div class="mb-4">
                <h6 class="fw-bold mb-3">Invoice Pengajuan</h6>
                @if($withdrawal->invoice_file)
                    <a
                        href="{{ asset('storage/' . $withdrawal->invoice_file) }}"
                        target="_blank"
                        class="btn btn-warning fw-bold"
                    >
                        Lihat Berkas Invoice
                    </a>
                @else
                    <span class="text-danger small">
                        Invoice berkas tidak tersedia atau hilang
                    </span>
                @endif
            </div>

            {{-- BUKTI TRANSFER --}}
            @if($withdrawal->transfer_proof)
            <div>
                <h6 class="fw-bold mb-3">Bukti Transfer Owner</h6>
                <a
                    href="{{ asset('storage/' . $withdrawal->transfer_proof) }}"
                    target="_blank"
                    class="btn btn-success fw-bold"
                >
                    Lihat Bukti Transfer Bank
                </a>
            </div>
            @endif

        </div>

    </div>

</div>

@endsection