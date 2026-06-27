@extends('layouts.eo')

@section('title', 'Detail Withdrawal')

@section('content')

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="fw-bold mb-0">
                Detail Withdrawal
            </h3>
            <small class="text-muted">
                Informasi lengkap pengajuan pencairan dana tiket
            </small>
        </div>

        <a href="{{ route('eo.ticket-history.index') }}"
           class="btn btn-outline-secondary">
            Kembali
        </a>

    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <h5 class="mb-0 fw-bold">
                        {{ $withdrawal->event_name ?? ($withdrawal->event->title ?? '-') }}
                    </h5>
                </div>

                @if($withdrawal->status == 'pending')
                    <span class="badge bg-warning text-dark px-3 py-2 fw-bold">
                        PENDING
                    </span>
                @elseif($withdrawal->status == 'approved')
                    <span class="badge bg-success px-3 py-2 fw-bold">
                        APPROVED
                    </span>
                @else
                    <span class="badge bg-danger px-3 py-2 fw-bold">
                        REJECTED
                    </span>
                @endif

            </div>

        </div>

        <div class="card-body">

            <div class="row g-4 mb-4">
                
                <div class="col-md-6">
                    <h6 class="text-muted small uppercase fw-bold mb-2">Informasi Penarikan</h6>
                    <div class="p-3 bg-light rounded">
                        <div class="mb-2">
                            <span class="text-muted">Nominal Pengajuan:</span><br>
                            <strong class="fs-5 text-dark">Rp {{ number_format($withdrawal->amount, 0, ',', '.') }}</strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted">Tanggal Pengajuan:</span><br>
                            <span>{{ \Carbon\Carbon::parse($withdrawal->created_at)->format('d F Y H:i') }}</span>
                        </div>
                        <div>
                            <span class="text-muted">Catatan/Log Sistem:</span><br>
                            <span class="text-secondary small">{{ $withdrawal->note ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="text-muted small uppercase fw-bold mb-2">Tujuan Transfer Bank</h6>
                    <div class="p-3 bg-light rounded">
                        <div class="mb-2">
                            <span class="text-muted">Nama Bank:</span><br>
                            <strong class="text-dark">{{ $withdrawal->bank_name ?? '-' }}</strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted">Nomor Rekening:</span><br>
                            <strong class="text-dark">{{ $withdrawal->account_number ?? '-' }}</strong>
                        </div>
                        <div>
                            <span class="text-muted">Nama Pemilik Rekening:</span><br>
                            <strong class="text-dark">{{ $withdrawal->account_name ?? '-' }}</strong>
                        </div>
                    </div>
                </div>

            </div>

            <hr class="my-4">

            {{-- INVOICE --}}
            <div class="mb-4">
                <h6 class="fw-bold mb-2">
                    Invoice Pengajuan
                </h6>
                @if($withdrawal->invoice_file)
                    <a
                        href="{{ asset('storage/'.$withdrawal->invoice_file) }}"
                        target="_blank"
                        class="btn btn-warning fw-bold text-dark"
                    >
                        Lihat Invoice
                    </a>
                @else
                    <span class="text-danger small bg-danger-subtle px-2 py-1 rounded">
                        Invoice tidak tersedia
                    </span>
                @endif
            </div>

            {{-- BUKTI TRANSFER --}}
            @if($withdrawal->transfer_proof)
            <div>
                <h6 class="fw-bold mb-2">
                    Bukti Transfer Owner
                </h6>
                <a
                    href="{{ asset($withdrawal->transfer_proof) }}"
                    target="_blank"
                    class="btn btn-success fw-bold"
                >
                    Lihat Bukti Transfer
                </a>
            </div>
            @endif

        </div>

    </div>

</div>

@endsection