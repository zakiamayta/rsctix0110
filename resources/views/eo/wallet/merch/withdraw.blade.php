@extends('layouts.eo')

@section('title','Ajukan Withdraw Merchandise')

@section('content')

<style>
  @import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500&display=swap');

  :root {
    --rsc-bg: #F7F4F1;
    --rsc-surface: #FFFFFF;
    --rsc-surface2: #F2EEE9;
    --rsc-border: #E2DBD4;
    --rsc-accent: #f97316;
    --rsc-accent-dim: rgba(232,71,10,0.08);
    --rsc-text: #1A1208;
    --rsc-muted: #8A7E76;
    --radius: 14px;
  }

  .rsc-wrap * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }

  .rsc-wrap {
    background: var(--rsc-bg);
    min-height: 100vh;
    padding: 28px 24px 60px;
    color: var(--rsc-text);
  }

  /* ── Page header ── */
  .page-header { margin-bottom: 24px; }
  .page-header h2 {
    font-family: 'Sora', sans-serif;
    font-size: 1.6rem; font-weight: 800;
    color: var(--rsc-text); letter-spacing: -.5px; margin: 0;
  }
  .accent-dot {
    display: inline-block; width: 8px; height: 8px;
    border-radius: 50%; background: var(--rsc-accent);
    margin-right: 8px; vertical-align: middle;
  }

  /* ── Full width layout ── */
  .withdraw-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 20px;
    align-items: start;
  }

  /* ── Cards ── */
  .rsc-card {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
  }

  /* ── Left: summary panel ── */
  .summary-panel { padding: 24px; }
  .event-title {
    font-family: 'Sora', sans-serif;
    font-size: 1.05rem; font-weight: 800; color: var(--rsc-text);
    margin: 0 0 18px;
  }
  .balance-box {
    background: var(--rsc-surface2);
    border: 1px solid var(--rsc-border);
    border-radius: 10px;
    padding: 16px 18px;
    margin-bottom: 16px;
  }
  .balance-label {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: var(--rsc-muted); margin-bottom: 6px;
  }
  .balance-value {
    font-family: 'Sora', sans-serif;
    font-size: 1.4rem; font-weight: 800; color: #1A7A44;
  }
  .summary-note {
    font-size: .78rem; color: var(--rsc-muted); line-height: 1.6;
    padding-top: 8px; border-top: 1px solid var(--rsc-border);
  }
  .summary-note ul { margin: 8px 0 0; padding-left: 18px; }
  .summary-note li { margin-bottom: 4px; }

  /* ── Right: form panel ── */
  .form-panel { padding: 28px; }
  .section-title {
    font-family: 'Sora', sans-serif;
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1.5px;
    color: var(--rsc-accent);
    margin: 0 0 20px;
    display: flex; align-items: center; gap: 8px;
  }
  .section-title::after {
    content: ''; flex: 1; height: 1px;
    background: linear-gradient(to right, var(--rsc-border), transparent);
  }

  /* ── Alerts ── */
  .rsc-alert {
    background: #FEF2F2; border: 1px solid #FECACA; color: #B91C1C;
    border-radius: 10px; padding: 12px 16px; font-size: .85rem;
    margin-bottom: 18px;
  }
  .rsc-alert ul { margin: 0; padding-left: 18px; }

  /* ── Form fields ── */
  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
  }
  .field-group { margin-bottom: 18px; }
  .field-group.span-full { grid-column: 1 / -1; }
  .field-group label {
    display: block; font-size: .8rem; font-weight: 700;
    color: var(--rsc-text); margin-bottom: 6px;
  }
  .rsc-input, .rsc-textarea, .rsc-file {
    background: var(--rsc-surface2);
    border: 1px solid var(--rsc-border);
    border-radius: 9px;
    color: var(--rsc-text);
    padding: 10px 13px;
    font-size: .87rem;
    width: 100%;
    transition: border-color .18s, box-shadow .18s;
    outline: none;
    font-family: 'DM Sans', sans-serif;
  }
  .rsc-input::placeholder, .rsc-textarea::placeholder { color: #BEB5AD; }
  .rsc-input:focus, .rsc-textarea:focus {
    border-color: var(--rsc-accent);
    box-shadow: 0 0 0 3px var(--rsc-accent-dim);
  }
  .rsc-textarea { resize: vertical; min-height: 120px; }
  .field-hint { display: block; font-size: .74rem; color: var(--rsc-muted); margin-top: 6px; }

  /* ── Buttons ── */
  .form-actions { display: flex; gap: 10px; margin-top: 6px; }
  .btn-submit {
    background: #15803D; color: #fff;
    border: none; border-radius: 9px;
    padding: 11px 26px; font-size: .85rem; font-weight: 700;
    cursor: pointer; font-family: 'Sora', sans-serif;
    transition: opacity .15s;
  }
  .btn-submit:hover { opacity: .87; }
  .btn-cancel {
    background: var(--rsc-surface2); color: var(--rsc-muted);
    border: 1px solid var(--rsc-border); border-radius: 9px;
    padding: 11px 26px; font-size: .85rem; font-weight: 700;
    font-family: 'Sora', sans-serif;
    text-decoration: none; display: inline-flex; align-items: center;
    transition: border-color .15s, color .15s;
  }
  .btn-cancel:hover { border-color: #B5AEA8; color: var(--rsc-text); }

  @media (max-width: 900px) {
    .withdraw-layout { grid-template-columns: 1fr; }
    .form-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="rsc-wrap">

    <div class="page-header">
        <h2><span class="accent-dot"></span>Ajukan Pencairan Dana Merch</h2>
    </div>

    <div class="withdraw-layout">

        {{-- Kolom kiri: ringkasan event & saldo --}}
        <div class="rsc-card summary-panel">
            <h5 class="event-title">
                {{ $wallet['event_name'] }}
            </h5>

            <div class="balance-box">
                <span class="balance-label">Saldo Hak Tarik Maksimal</span>
                <div class="balance-value">
                    Rp {{ number_format($wallet['available_balance'], 0, ',', '.') }}
                </div>
            </div>

            <div class="summary-note">
                Ketentuan pengajuan:
                <ul>
                    <li>Batas minimal pengajuan Rp 100.000</li>
                    <li>Maksimal sesuai saldo hak tarik</li>
                    <li>Wajib melampirkan invoice resmi (PDF/JPG/PNG, maks 2MB)</li>
                </ul>
            </div>
        </div>

        {{-- Kolom kanan: form pengajuan --}}
        <div class="rsc-card form-panel">
            <div class="section-title">Form Pengajuan Withdraw Merch</div>

            @if(session('error'))
                <div class="rsc-alert">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rsc-alert">
                    <ul>
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

                <div class="form-grid">

                    <div class="field-group">
                        <label>Nominal Pengajuan (IDR)</label>
                        <input
                            type="number"
                            name="amount"
                            class="rsc-input"
                            min="100000"
                            max="{{ $wallet['available_balance'] }}"
                            value="{{ old('amount') }}"
                            placeholder="Contoh: 500000"
                            required
                        >
                        <small class="field-hint">
                            Batas Minimal Pengajuan Rp 100.000
                        </small>
                    </div>

                    <div class="field-group">
                        <label>Upload Berkas Invoice Resmi</label>
                        <input
                            type="file"
                            name="invoice"
                            class="rsc-file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            required
                        >
                        <small class="field-hint">Format didukung: PDF, JPG, JPEG, PNG (Maks 2MB)</small>
                    </div>

                    <div class="field-group span-full">
                        <label>Catatan Tambahan (Opsional)</label>
                        <textarea
                            name="note"
                            class="rsc-textarea"
                            rows="4"
                            placeholder="Tulis catatan atau info detail tambahan bila diperlukan..."
                        >{{ old('note') }}</textarea>
                    </div>

                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        Kirim Pengajuan Dana
                    </button>
                    <a href="{{ route('eo.merch-wallet.dashboard') }}" class="btn-cancel">
                        Batal
                    </a>
                </div>

            </form>
        </div>

    </div>

</div>

@endsection