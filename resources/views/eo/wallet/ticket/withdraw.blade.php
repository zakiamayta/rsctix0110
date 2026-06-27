@extends('layouts.eo')

@section('title','Ajukan Withdraw')

@section('content')

<div class="container py-4">

    <h3 class="mb-4">
        Ajukan Pencairan Dana
    </h3>

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <h5>
                {{ $wallet['event_name'] ?? 'Detail Event' }}
            </h5>

            <div class="mb-3">
                Saldo Tersedia :
                <strong class="text-success">
                    Rp {{ number_format($wallet['available_balance'] ?? 0, 0, ',', '.') }}
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
                action="{{ route('eo.ticket-withdraw.store') }}"
                enctype="multipart/form-data"
            >
                @csrf

                <input
                    type="hidden"
                    name="event_id"
                    value="{{ $wallet['event_id'] ?? '' }}"
                >

                <div class="mb-3">
                    <label class="form-label fw-bold">Nominal Penarikan (IDR)</label>
                    <input
                        type="number"
                        name="amount"
                        class="form-control"
                        placeholder="Contoh: 500000"
                        min="100000"
                        max="{{ $wallet['available_balance'] ?? 0 }}"
                        value="{{ old('amount') }}"
                        required
                    >
                    <small class="text-muted">
                        Maksimal: Rp {{ number_format($wallet['available_balance'] ?? 0, 0, ',', '.') }} (Min. Rp 100.000)
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Upload Invoice</label>
                    <input
                        type="file"
                        name="invoice"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                        required
                    >
                    <small class="text-muted">Format: PDF, JPG, JPEG, PNG (Maks. 2MB)</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Catatan</label>
                    <textarea
                        name="note"
                        class="form-control"
                        rows="4"
                        placeholder="Tulis catatan opsional atau instruksi tambahan..."
                    >{{ old('note') }}</textarea>
                </div>

                <button
                    type="submit"
                    class="btn btn-warning px-4 fw-bold"
                >
                    Kirim Pengajuan
                </button>

            </form>

        </div>

    </div>

</div>

@endsection