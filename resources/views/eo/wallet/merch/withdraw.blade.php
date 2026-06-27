@extends('layouts.eo')

@section('title','Ajukan Withdraw Merchandise')

@section('content')

<div class="container py-4">

    <h3 class="mb-4 fw-bold">
        Ajukan Pencairan Dana Merch
    </h3>

    <div class="card border-0 shadow-sm">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-3">
                {{ $wallet['event_name'] }}
            </h5>

            <div class="mb-4 p-3 bg-light rounded">
                <span class="text-muted d-block small">Saldo Hak Tarik Maksimal:</span>
                <strong class="text-success fs-5">
                    Rp {{ number_format($wallet['available_balance'], 0, ',', '.') }}
                </strong>
            </div>

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('eo.merch-withdrawal.store') }}"
                enctype="multipart/form-data"
            >
                @csrf

                <input type="hidden" name="event_id" value="{{ $wallet['event_id'] }}">

                <div class="mb-3">
                    <label class="form-label fw-bold">Nominal Pengajuan (IDR)</label>
                    <input
                        type="number"
                        name="amount"
                        class="form-control"
                        min="100000"
                        max="{{ $wallet['available_balance'] }}"
                        value="{{ old('amount') }}"
                        placeholder="Contoh: 500000"
                        required
                    >
                    <small class="text-muted">
                        Batas Minimal Pengajuan Rp 100.000
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Upload Berkas Invoice Resmi</label>
                    <input
                        type="file"
                        name="invoice"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                        required
                    >
                    <small class="text-muted d-block">Format didukung: PDF, JPG, JPEG, PNG (Maks 2MB)</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Catatan Tambahan (Opsional)</label>
                    <textarea
                        name="note"
                        class="form-control"
                        rows="4"
                        placeholder="Tulis catatan atau info detail tambahan bila diperlukan..."
                    >{{ old('note') }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        Kirim Pengajuan Dana
                    </button>
                    <a href="{{ route('eo.merch-wallet.dashboard') }}" class="btn btn-light px-4 border">
                        Batal
                    </a>
                </div>

            </form>

        </div>

    </div>

</div>

@endsection