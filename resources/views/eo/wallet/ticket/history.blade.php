@extends('layouts.eo')

@section('title','Riwayat Withdraw Tiket')

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
  .page-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 24px; gap: 16px; flex-wrap: wrap;
  }
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

  .btn-ghost {
    background: var(--rsc-surface2); color: var(--rsc-muted);
    border: 1px solid var(--rsc-border); border-radius: 9px;
    padding: 9px 18px; font-size: .82rem; font-weight: 700;
    cursor: pointer; font-family: 'Sora', sans-serif;
    transition: border-color .15s, color .15s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-ghost:hover { border-color: #B5AEA8; color: var(--rsc-text); }

  /* ── Table ── */
  .table-wrap {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
    overflow: hidden;
  }
  .rsc-table { width: 100%; border-collapse: collapse; min-width: 700px; }
  .rsc-table thead tr {
    background: var(--rsc-surface2);
    border-bottom: 1px solid var(--rsc-border);
  }
  .rsc-table th {
    padding: 11px 14px; text-align: left;
    font-size: .65rem; font-weight: 700;
    color: var(--rsc-muted); text-transform: uppercase; letter-spacing: 1px;
    white-space: nowrap;
  }
  .rsc-table th.text-center, .rsc-table td.text-center { text-align: center; }
  .rsc-table tbody tr {
    border-bottom: 1px solid var(--rsc-border);
    transition: background .12s;
  }
  .rsc-table tbody tr:last-child { border-bottom: none; }
  .rsc-table tbody tr:hover { background: #FAFAF8; }
  .rsc-table td {
    padding: 11px 14px; font-size: .82rem;
    color: var(--rsc-text); vertical-align: middle;
  }
  .td-bold { font-weight: 700; }
  .td-muted { color: var(--rsc-muted); }

  /* ── Status badges ── */
  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
    padding: 4px 10px; border-radius: 20px;
  }
  .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
  .badge-approved { background: #E8F5EE; color: #1A7A44; }
  .badge-rejected { background: #FEF2F2; color: #B91C1C; }
  .badge-pending  { background: #FEF3E2; color: #B45309; }

  /* ── Action button ── */
  .btn-detail {
    background: var(--rsc-accent-dim); color: var(--rsc-accent);
    border: none; border-radius: 7px;
    padding: 6px 16px; font-size: .74rem; font-weight: 700;
    font-family: 'Sora', sans-serif;
    text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
    transition: background .15s;
  }
  .btn-detail:hover { background: rgba(232,71,10,.15); color: var(--rsc-accent); }

  /* ── Empty state ── */
  .empty-cell { padding: 48px 24px; text-align: center; color: var(--rsc-muted); font-size: .88rem; font-weight: 600; }

  /* ── Pagination ── */
  .rsc-pagination { margin-top: 16px; font-size: .8rem; }

  @media (max-width: 560px) {
    .page-header { flex-direction: column; align-items: flex-start; }
  }
</style>

<div class="rsc-wrap">

    <div class="page-header">
        <h2><span class="accent-dot"></span>Riwayat Withdraw Tiket</h2>
        <a href="{{ route('eo.ticket-wallet.dashboard') }}" class="btn-ghost">
            Kembali ke Dashboard Saldo
        </a>
    </div>

    <div class="table-wrap">
        <div style="overflow-x:auto;">
            <table class="rsc-table">

                <thead>
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

                        <td class="td-muted">
                            {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') }}
                        </td>

                        <td>
                            {{ $item->event_name }}
                        </td>

                        <td class="td-bold">
                            Rp {{ number_format($item->amount, 0, ',', '.') }}
                        </td>

                        <td>
                            @if($item->status == 'approved')
                                <span class="badge badge-approved">
                                    <span class="badge-dot"></span>Approved
                                </span>
                            @elseif($item->status == 'rejected')
                                <span class="badge badge-rejected">
                                    <span class="badge-dot"></span>Rejected
                                </span>
                            @else
                                <span class="badge badge-pending">
                                    <span class="badge-dot"></span>Pending
                                </span>
                            @endif
                        </td>

                        <td class="text-center">
                            <a href="{{ route('eo.ticket-history.show', $item->id) }}" class="btn-detail">
                                Detail
                            </a>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="empty-cell">
                            Belum ada riwayat transaksi penarikan dana.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>
        </div>
    </div>

    @if(method_exists($withdrawals, 'links'))
        <div class="rsc-pagination">
            {{ $withdrawals->links() }}
        </div>
    @endif

</div>

@endsection