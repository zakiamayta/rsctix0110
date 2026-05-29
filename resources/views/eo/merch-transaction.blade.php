@extends('layouts.eo')

@push('styles')
<link href="{{ asset('css/admin_dashboard.css') }}" rel="stylesheet">
@endpush

@section('content')

<style>
  @import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500&display=swap');

  :root {
    --rsc-bg: #F7F4F1;
    --rsc-surface: #FFFFFF;
    --rsc-surface2: #F2EEE9;
    --rsc-border: #E2DBD4;
    --rsc-accent: #E8470A;
    --rsc-accent-dim: rgba(232,71,10,0.08);
    --rsc-text: #1A1208;
    --rsc-muted: #8A7E76;
    --radius: 14px;
  }

  .rsc-wrap * {
    font-family: 'DM Sans', sans-serif;
    box-sizing: border-box;
  }

  .rsc-wrap {
    background: var(--rsc-bg);
    min-height: 100vh;
    padding: 28px 24px 60px;
    color: var(--rsc-text);
  }

  .page-header {
    margin-bottom: 28px;
  }

  .page-header h2 {
    font-family: 'Sora', sans-serif;
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--rsc-text);
    letter-spacing: -.5px;
    margin: 0 0 4px;
  }

  .page-header p {
    color: var(--rsc-muted);
    font-size: .82rem;
    margin: 0;
  }

  .accent-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--rsc-accent);
    margin-right: 8px;
    vertical-align: middle;
  }

  .section-title {
    font-family: 'Sora', sans-serif;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--rsc-accent);
    margin: 0 0 18px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(to right, var(--rsc-border), transparent);
  }

  .rsc-card {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    padding: 22px 24px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
  }

  .stats-row {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 14px;
    margin-bottom: 20px;
  }

  .stat-card {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    padding: 20px 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }

  .stat-card.accent {
    background: var(--rsc-accent);
    border-color: var(--rsc-accent);
  }

  .stat-label {
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--rsc-muted);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .stat-card.accent .stat-label {
    color: rgba(255,255,255,.7);
  }

  .stat-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
  }

  .stat-value {
    font-family: 'Sora', sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--rsc-text);
    line-height: 1;
    margin-bottom: 3px;
  }

  .stat-card.accent .stat-value {
    color: #fff;
  }

  .stat-sub {
    font-size: .72rem;
    color: var(--rsc-muted);
  }

  .stat-card.accent .stat-sub {
    color: rgba(255,255,255,.6);
  }

  .stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .stat-icon-green {
    background: rgba(26,122,68,.1);
  }

  .stat-icon-red {
    background: rgba(185,28,28,.08);
  }

  .filter-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
  }

  .filter-grid .span-full {
    grid-column: 1 / -1;
  }

  .field-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .field-group label {
    font-size: .72rem;
    font-weight: 700;
    color: var(--rsc-muted);
    text-transform: uppercase;
    letter-spacing: .6px;
  }

  .rsc-input {
    background: var(--rsc-surface2);
    border: 1px solid var(--rsc-border);
    border-radius: 9px;
    color: var(--rsc-text);
    padding: 9px 12px;
    font-size: .84rem;
    width: 100%;
    outline: none;
    transition: border-color .18s, box-shadow .18s;
  }

  .rsc-input:focus {
    border-color: var(--rsc-accent);
    box-shadow: 0 0 0 3px var(--rsc-accent-dim);
  }

  .btn-primary {
    background: var(--rsc-accent);
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: 9px 20px;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    font-family: 'Sora', sans-serif;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .btn-primary:hover {
    opacity: .9;
  }

  .btn-ghost {
    background: var(--rsc-surface2);
    color: var(--rsc-muted);
    border: 1px solid var(--rsc-border);
    border-radius: 9px;
    padding: 9px 20px;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .btn-green {
    background: #15803D;
    color: white;
    border-radius: 9px;
    padding: 9px 18px;
    font-size: .82rem;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .btn-red-outline {
    background: #fff;
    color: #B91C1C;
    border: 1px solid #FECACA;
    border-radius: 9px;
    padding: 9px 18px;
    font-size: .82rem;
    font-weight: 700;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .action-row {
    display: flex;
    gap: 10px;
    margin-bottom: 18px;
    flex-wrap: wrap;
  }

  .table-wrap {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
  }

  .rsc-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
  }

  .rsc-table thead tr {
    background: var(--rsc-surface2);
    border-bottom: 1px solid var(--rsc-border);
  }

  .rsc-table th {
    padding: 11px 14px;
    text-align: left;
    font-size: .65rem;
    font-weight: 700;
    color: var(--rsc-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  .rsc-table td {
    padding: 11px 14px;
    font-size: .82rem;
    border-bottom: 1px solid var(--rsc-border);
  }

  .rsc-table tbody tr:hover {
    background: #FAFAF8;
  }

  .td-bold {
    font-weight: 700;
  }

  .td-muted {
    color: var(--rsc-muted);
  }

  .td-mono {
    font-family: monospace;
    color: var(--rsc-muted);
  }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 20px;
  }

  .badge-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
  }

  .badge-paid {
    background: #E8F5EE;
    color: #1A7A44;
  }

  .badge-unpaid {
    background: #FEF2F2;
    color: #B91C1C;
  }

  .btn-sm {
    font-size: .72rem;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 7px;
    border: none;
    cursor: pointer;
  }

  .btn-sm-indigo {
    background: #EEF2FF;
    color: #4338CA;
  }

  .empty-cell {
    padding: 48px 24px;
    text-align: center;
    color: var(--rsc-muted);
  }

  .rsc-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 50;
    padding: 24px;
  }

  .rsc-modal-backdrop.open {
    display: flex;
  }

  .rsc-modal {
    background: white;
    border-radius: var(--radius);
    width: 100%;
    max-width: 720px;
    padding: 28px;
    max-height: 80vh;
    overflow-y: auto;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
  }

  .modal-title {
    font-family: 'Sora', sans-serif;
    font-weight: 800;
  }

  .btn-close-modal {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid var(--rsc-border);
    background: var(--rsc-surface2);
    cursor: pointer;
  }

  .modal-table {
    width: 100%;
    border-collapse: collapse;
  }

  .modal-table th,
  .modal-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--rsc-border);
    font-size: .82rem;
  }

  .modal-table th {
    background: var(--rsc-surface2);
    text-align: left;
    font-size: .68rem;
    text-transform: uppercase;
    color: var(--rsc-muted);
  }

  @media (max-width: 900px) {
    .stats-row {
      grid-template-columns: 1fr;
    }

    .filter-grid {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 560px) {
    .filter-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="rsc-wrap">

  {{-- HEADER --}}
  <div class="page-header">
    <h2>
      <span class="accent-dot"></span>
      Transaksi Merchandise
    </h2>
    <p>Monitoring transaksi merchandise event milik Anda</p>
  </div>

  {{-- STATS --}}
  <div class="stats-row">

    <div class="stat-card accent">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:rgba(255,255,255,.7)"></span>
          Total Uang Masuk
        </div>

        <div class="stat-value">
          Rp{{ number_format($totalPaidAmount, 0, ',', '.') }}
        </div>

        <div class="stat-sub">
          Dari pembayaran berhasil
        </div>
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#1A7A44"></span>
          Pembayaran Berhasil
        </div>

        <div class="stat-value">
          {{ $totalPaidCount }}
        </div>

        <div class="stat-sub">
          Transaksi selesai
        </div>
      </div>

      <div class="stat-icon stat-icon-green">
        ✓
      </div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-label">
          <span class="stat-dot" style="background:#B91C1C"></span>
          Belum Dibayar
        </div>

        <div class="stat-value">
          {{ $totalUnpaidCount }}
        </div>

        <div class="stat-sub">
          Menunggu pembayaran
        </div>
      </div>

      <div class="stat-icon stat-icon-red">
        !
      </div>
    </div>

  </div>

  {{-- FILTER --}}
  <div class="rsc-card" style="margin-bottom:18px;">

    <div class="section-title">
      Filter Transaksi
    </div>

    <form method="GET"
          action="{{ route('eo.merch.transactions') }}"
          class="filter-grid">

      <div class="field-group">
        <label>Event</label>

        <select name="event_id" class="rsc-input">
          <option value="">Semua Event</option>

          @foreach($events as $event)
            <option value="{{ $event->id }}"
              {{ request('event_id') == $event->id ? 'selected' : '' }}>
              {{ $event->title }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="field-group">
        <label>Tanggal Mulai</label>

        <input type="date"
               name="start_date"
               value="{{ request('start_date') }}"
               class="rsc-input">
      </div>

      <div class="field-group">
        <label>Tanggal Selesai</label>

        <input type="date"
               name="end_date"
               value="{{ request('end_date') }}"
               class="rsc-input">
      </div>

      <div class="field-group">
        <label>Status</label>

        <select name="payment_status" class="rsc-input">
          <option value="">Semua</option>

          <option value="paid"
            {{ request('payment_status') == 'paid' ? 'selected' : '' }}>
            Paid
          </option>

          <option value="unpaid"
            {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>
            Unpaid
          </option>
        </select>
      </div>

      <div class="field-group">
        <label>Urutkan</label>

        <select name="sort_by" class="rsc-input">
          <option value="">Default</option>
          <option value="event_title">Judul Event</option>
          <option value="email">Email</option>
          <option value="name">Nama</option>
          <option value="payment_status">Status</option>
          <option value="checkout_time">Checkout</option>
        </select>
      </div>

      <div class="field-group">
        <label>Pencarian</label>

        <input type="text"
               name="q"
               value="{{ request('q') }}"
               placeholder="Cari email / nama"
               class="rsc-input">
      </div>

      <div class="span-full" style="display:flex; gap:10px; padding-top:4px;">

        <button type="submit" class="btn-primary">
          Terapkan Filter
        </button>

        <a href="{{ route('eo.merch.transactions') }}"
           class="btn-ghost">
          Reset
        </a>

      </div>

    </form>

  </div>

  {{-- EXPORT --}}
  <div class="action-row">

    <a href="{{ route('eo.merch.transactions.export.excel', request()->query()) }}"
       class="btn-green">
      Export Excel
    </a>

    <a href="{{ route('eo.merch.transactions.export.pdf', request()->query()) }}"
       target="_blank"
       class="btn-red-outline">
      Export PDF
    </a>

  </div>

  {{-- TABLE --}}
  <div class="table-wrap">

    <div style="overflow-x:auto;">

      <table class="rsc-table">

        <thead>
          <tr>
            <th>No</th>
            <th>Produk</th>
            <th>Email</th>
            <th>Checkout</th>
            <th>Paid Time</th>
            <th>Status</th>
            <th>Total</th>
            <th>Item</th>
            <th>Aksi</th>
          </tr>
        </thead>

        <tbody>

        @forelse($transactions as $transaction)

          <tr>

            <td class="td-muted">
              {{ $loop->iteration }}
            </td>

            <td class="td-bold">
              {{ $transaction->details->first()->product->name ?? '-' }}
            </td>

            <td class="td-mono">
              {{ $transaction->email }}
            </td>

            <td class="td-muted">
              {{ $transaction->checkout_time }}
            </td>

            <td class="td-muted">
              {{ $transaction->paid_time ?? '-' }}
            </td>

            <td>
              <span class="badge {{ $transaction->payment_status === 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
                <span class="badge-dot"></span>
                {{ ucfirst($transaction->payment_status) }}
              </span>
            </td>

            <td class="td-bold">
              Rp{{ number_format($transaction->total_amount, 0, ',', '.') }}
            </td>

            <td class="td-muted">
              {{ $transaction->details->sum('quantity') }} pcs
            </td>

            <td>
              <button onclick="showDetail({{ $transaction->id }})"
                      class="btn-sm btn-sm-indigo">
                Detail
              </button>
            </td>

          </tr>

        @empty

          <tr>
            <td colspan="9" class="empty-cell">
              Tidak ada transaksi merchandise
            </td>
          </tr>

        @endforelse

        </tbody>

      </table>

    </div>

  </div>

</div>

{{-- MODAL --}}
<div id="detailModal" class="rsc-modal-backdrop">

  <div class="rsc-modal">

    <div class="modal-header">

      <h3 class="modal-title">
        Detail Pembelian Merchandise
      </h3>

      <button onclick="closeModal()" class="btn-close-modal">
        ✕
      </button>

    </div>

    <div id="modalContent"></div>

  </div>

</div>

<script>

function showDetail(transactionId) {

    const transactions = @json($transactions);

    const transaction = transactions.find(t => t.id === transactionId);

    if (!transaction) return;

    let html = '';

    if (!transaction.details || transaction.details.length === 0) {

        html = `
            <p style="color:#8A7E76; font-size:.84rem;">
                Tidak ada detail pembelian.
            </p>
        `;

    } else {

        html = `
        <div style="overflow-x:auto;">

            <table class="modal-table">

                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Varian</th>
                        <th>Ukuran</th>
                        <th>Qty</th>
                        <th>Pembeli</th>
                        <th>Phone</th>
                    </tr>
                </thead>

                <tbody>

                    ${transaction.details.map(d => `

                        <tr>

                            <td style="font-weight:600;">
                                ${d.product?.name ?? '-'}
                            </td>

                            <td>
                                ${d.varian?.varian ?? '-'}
                            </td>

                            <td>
                                ${d.ukuran?.ukuran ?? '-'}
                            </td>

                            <td>
                                ${d.quantity}
                            </td>

                            <td>
                                ${d.buyer_name}
                            </td>

                            <td>
                                ${d.buyer_phone ?? '-'}
                            </td>

                        </tr>

                    `).join('')}

                </tbody>

            </table>

        </div>
        `;
    }

    document.getElementById('modalContent').innerHTML = html;

    document.getElementById('detailModal').classList.add('open');
}

function closeModal() {
    document.getElementById('detailModal').classList.remove('open');
}

document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

</script>

@endsection