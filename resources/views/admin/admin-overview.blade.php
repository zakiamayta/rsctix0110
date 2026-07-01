@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    // Helper format Rupiah
    $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');

    // Format ringkas untuk kartu KPI (jt / M)
    $short = function ($v) {
        $v = (float) $v;
        if ($v >= 1.0e9) return 'Rp ' . number_format($v / 1.0e9, 2, ',', '.') . ' M';
        if ($v >= 1.0e6) return 'Rp ' . number_format($v / 1.0e6, 1, ',', '.') . ' jt';
        if ($v >= 1.0e3) return 'Rp ' . number_format($v / 1.0e3, 0, ',', '.') . ' rb';
        return 'Rp ' . number_format($v, 0, ',', '.');
    };

    $statusMeta = [
        'approved'           => ['label' => 'Disetujui',        'color' => '#1A7A44', 'bg' => '#E8F5EE'],
        'pending'            => ['label' => 'Menunggu',         'color' => '#9A6200', 'bg' => '#FFF5E0'],
        'rejected'           => ['label' => 'Ditolak',          'color' => '#9C2222', 'bg' => '#FDECEC'],
        'cancelled'          => ['label' => 'Dibatalkan',       'color' => '#7A6E66', 'bg' => '#F0EDE9'],
        'pending_cancel'     => ['label' => 'Pengajuan Batal',  'color' => '#B45309', 'bg' => '#FEF3E2'],
        'pending_reschedule' => ['label' => 'Pengajuan Jadwal', 'color' => '#1D4ED8', 'bg' => '#E6EEFE'],
    ];
@endphp

<style>
    .ov-wrap { max-width: 1200px; margin: 0 auto; }
    .ov-grid { display: grid; gap: 16px; }
    .ov-card {
        background: var(--rsc-surface);
        border: 1px solid var(--rsc-border);
        border-radius: 14px;
        padding: 18px;
    }
    .ov-kpi-label { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--rsc-muted); }
    .ov-kpi-value { font-size: 1.55rem; font-weight: 800; color: var(--rsc-dark); line-height: 1.15; margin-top: 6px; }
    .ov-kpi-sub { font-size: .7rem; color: var(--rsc-subtle); margin-top: 5px; }
    .ov-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ov-icon svg { width: 22px; height: 22px; }
    .ov-section-title { font-size: .95rem; font-weight: 700; color: var(--rsc-dark); }
    .ov-section-sub { font-size: .72rem; color: var(--rsc-muted); margin-top: 2px; }
    .ov-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .ov-mini-label { font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: var(--rsc-muted); }
    .ov-mini-value { font-size: 1.05rem; font-weight: 800; color: var(--rsc-dark); margin-top: 4px; }
    .ov-table { width: 100%; border-collapse: collapse; }
    .ov-table th { text-align: left; font-size: .64rem; text-transform: uppercase; letter-spacing: .5px; color: var(--rsc-subtle); padding: 8px 10px; border-bottom: 1px solid var(--rsc-border); }
    .ov-table td { font-size: .8rem; color: var(--rsc-ink); padding: 11px 10px; border-bottom: 1px solid var(--rsc-border); }
    .ov-table tr:last-child td { border-bottom: none; }
    .ov-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: .7rem; font-weight: 600; }
    .ov-link { font-size: .72rem; font-weight: 600; color: var(--rsc-orange); display: inline-flex; align-items: center; gap: 4px; }
    .ov-rank { width: 22px; height: 22px; border-radius: 6px; background: var(--rsc-orange-light); color: var(--rsc-orange); font-size: .72rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
    .ov-pending-item { display: flex; align-items: center; justify-content: space-between; padding: 11px 0; border-bottom: 1px dashed var(--rsc-border); }
    .ov-pending-item:last-child { border-bottom: none; }
    .ov-badge-count { min-width: 26px; height: 24px; padding: 0 8px; border-radius: 7px; font-size: .8rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
    @media (max-width: 991px) {
        .ov-2col, .ov-mid { grid-template-columns: 1fr !important; }
    }
</style>

<div class="ov-wrap">

    {{-- Heading --}}
    <div style="margin-bottom: 20px;">
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--rsc-dark);">Ringkasan Platform</h2>
        <p style="font-size: .83rem; color: var(--rsc-muted); margin-top: 2px;">
            Gambaran menyeluruh performa &amp; finansial RSCTicket — dihitung langsung dari data transaksi.
        </p>
    </div>

    {{-- ══════════ KPI HERO ══════════ --}}
    <div class="ov-grid" style="grid-template-columns: repeat(auto-fit, minmax(215px, 1fr)); margin-bottom: 16px;">

        {{-- Pendapatan Platform --}}
        <div class="ov-card ov-row" style="align-items: flex-start;">
            <div>
                <div class="ov-kpi-label">Pendapatan Platform</div>
                <div class="ov-kpi-value">{{ $short($summary['platform_revenue']) }}</div>
                <div class="ov-kpi-sub">Biaya layanan tiket + merch</div>
            </div>
            <div class="ov-icon" style="background: var(--rsc-orange-light); color: var(--rsc-orange);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
        </div>

        {{-- Nilai Transaksi (GMV) --}}
        <div class="ov-card ov-row" style="align-items: flex-start;">
            <div>
                <div class="ov-kpi-label">Nilai Transaksi</div>
                <div class="ov-kpi-value">{{ $short($summary['total_gmv']) }}</div>
                <div class="ov-kpi-sub">Total dana mengalir (lunas)</div>
            </div>
            <div class="ov-icon" style="background: #E6EEFE; color: #1D4ED8;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>
                </svg>
            </div>
        </div>

        {{-- Tiket Terjual --}}
        <div class="ov-card ov-row" style="align-items: flex-start;">
            <div>
                <div class="ov-kpi-label">Tiket Terjual</div>
                <div class="ov-kpi-value">{{ number_format($summary['tickets_sold'], 0, ',', '.') }}</div>
                <div class="ov-kpi-sub">Dari transaksi lunas</div>
            </div>
            <div class="ov-icon" style="background: #E8F5EE; color: #1A7A44;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 7v4a2 2 0 0 0 0 4v4a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-4a2 2 0 0 0 0-4V7a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1z"/>
                    <line x1="13" y1="5" x2="13" y2="19"/>
                </svg>
            </div>
        </div>

        {{-- Transaksi Berhasil --}}
        <div class="ov-card ov-row" style="align-items: flex-start;">
            <div>
                <div class="ov-kpi-label">Transaksi Berhasil</div>
                <div class="ov-kpi-value">{{ number_format($summary['paid_count'], 0, ',', '.') }}</div>
                <div class="ov-kpi-sub">Tiket + merchandise lunas</div>
            </div>
            <div class="ov-icon" style="background: #F3E8FF; color: #7C3AED;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- ══════════ TREN + AKSI TERTUNDA ══════════ --}}
    <div class="ov-grid ov-mid" style="grid-template-columns: 2fr 1fr; margin-bottom: 16px;">

        {{-- Grafik Tren --}}
        <div class="ov-card">
            <div class="ov-row" style="margin-bottom: 14px;">
                <div>
                    <div class="ov-section-title">Tren Pendapatan</div>
                    <div class="ov-section-sub">Nilai transaksi lunas (tiket + merch) — 6 bulan terakhir</div>
                </div>
            </div>
            <div style="height: 260px;">
                <canvas id="revenueTrend"></canvas>
            </div>
        </div>

        {{-- Aksi Tertunda --}}
        <div class="ov-card">
            <div class="ov-section-title" style="margin-bottom: 4px;">Perlu Tindakan</div>
            <div class="ov-section-sub" style="margin-bottom: 6px;">Antrean yang menunggu keputusan</div>

            <div class="ov-pending-item">
                <div>
                    <div style="font-size: .82rem; font-weight: 600; color: var(--rsc-ink);">Persetujuan Event</div>
                    <a href="{{ route('admin.event.index') }}" class="ov-link">Tinjau &rarr;</a>
                </div>
                <span class="ov-badge-count" style="background: {{ $pending['events'] ? '#FFF5E0' : '#F0EDE9' }}; color: {{ $pending['events'] ? '#9A6200' : '#9A8F87' }};">
                    {{ $pending['events'] }}
                </span>
            </div>

            <div class="ov-pending-item">
                <div>
                    <div style="font-size: .82rem; font-weight: 600; color: var(--rsc-ink);">Refund Menunggu</div>
                    <a href="{{ route('admin.refunds.index') }}" class="ov-link">Proses &rarr;</a>
                </div>
                <span class="ov-badge-count" style="background: {{ $pending['refunds'] ? '#FDECEC' : '#F0EDE9' }}; color: {{ $pending['refunds'] ? '#9C2222' : '#9A8F87' }};">
                    {{ $pending['refunds'] }}
                </span>
            </div>

            <div class="ov-pending-item">
                <div>
                    <div style="font-size: .82rem; font-weight: 600; color: var(--rsc-ink);">Penarikan Dana EO</div>
                    <a href="{{ route('admin.finance.index') }}" class="ov-link">Lihat &rarr;</a>
                </div>
                <span class="ov-badge-count" style="background: {{ $pending['withdrawals'] ? '#E6EEFE' : '#F0EDE9' }}; color: {{ $pending['withdrawals'] ? '#1D4ED8' : '#9A8F87' }};">
                    {{ $pending['withdrawals'] }}
                </span>
            </div>
        </div>
    </div>

    {{-- ══════════ FINANSIAL ══════════ --}}
    <div class="ov-card" style="margin-bottom: 16px;">
        <div class="ov-section-title" style="margin-bottom: 2px;">Rincian Finansial</div>
        <div class="ov-section-sub" style="margin-bottom: 14px;">Pendapatan platform, biaya, dan dana yang keluar</div>

        <div class="ov-grid" style="grid-template-columns: repeat(auto-fit, minmax(165px, 1fr));">
            <div style="border-left: 3px solid var(--rsc-orange); padding-left: 12px;">
                <div class="ov-mini-label">Pendapatan (Service Tax)</div>
                <div class="ov-mini-value">{{ $rp($summary['platform_revenue']) }}</div>
            </div>
            <div style="border-left: 3px solid #1A7A44; padding-left: 12px;">
                <div class="ov-mini-label">Saldo Bersih Platform</div>
                <div class="ov-mini-value">{{ $rp($summary['platform_net']) }}</div>
            </div>
            <div style="border-left: 3px solid #9C2222; padding-left: 12px;">
                <div class="ov-mini-label">Biaya Refund (Xendit)</div>
                <div class="ov-mini-value">{{ $rp($summary['refund_fees']) }}</div>
            </div>
            <div style="border-left: 3px solid #B45309; padding-left: 12px;">
                <div class="ov-mini-label">Dana Refund ke Pembeli</div>
                <div class="ov-mini-value">{{ $rp($summary['refunded_buyers']) }}</div>
            </div>
            <div style="border-left: 3px solid #1D4ED8; padding-left: 12px;">
                <div class="ov-mini-label">Dana Cair ke EO</div>
                <div class="ov-mini-value">{{ $rp($summary['withdrawn_eo']) }}</div>
            </div>
            <div style="border-left: 3px solid #7A6E66; padding-left: 12px;">
                <div class="ov-mini-label">Utang EO Berjalan</div>
                <div class="ov-mini-value">{{ $rp($summary['eo_debt']) }}</div>
            </div>
        </div>
    </div>

    {{-- ══════════ STATISTIK RINGKAS + STATUS EVENT ══════════ --}}
    <div class="ov-grid ov-2col" style="grid-template-columns: 1fr 1fr; margin-bottom: 16px;">

        {{-- Populasi Platform --}}
        <div class="ov-card">
            <div class="ov-section-title" style="margin-bottom: 14px;">Populasi Platform</div>
            <div class="ov-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div style="text-align: center; padding: 8px; background: var(--rsc-bg); border-radius: 10px;">
                    <div style="font-size: 1.4rem; font-weight: 800; color: var(--rsc-dark);">{{ number_format($summary['total_events'], 0, ',', '.') }}</div>
                    <div class="ov-mini-label" style="margin-top: 2px;">Event</div>
                </div>
                <div style="text-align: center; padding: 8px; background: var(--rsc-bg); border-radius: 10px;">
                    <div style="font-size: 1.4rem; font-weight: 800; color: var(--rsc-dark);">{{ number_format($summary['total_eo'], 0, ',', '.') }}</div>
                    <div class="ov-mini-label" style="margin-top: 2px;">Organizer</div>
                </div>
                <div style="text-align: center; padding: 8px; background: var(--rsc-bg); border-radius: 10px;">
                    <div style="font-size: 1.4rem; font-weight: 800; color: var(--rsc-dark);">{{ number_format($summary['total_users'], 0, ',', '.') }}</div>
                    <div class="ov-mini-label" style="margin-top: 2px;">Pengguna</div>
                </div>
            </div>

            <div style="margin-top: 16px; display: flex; gap: 10px; flex-wrap: wrap;">
                <span class="ov-chip" style="background: #E8F5EE; color: #1A7A44;">
                    {{ $summary['ticket_paid'] }} tiket lunas
                </span>
                <span class="ov-chip" style="background: #FFF5E0; color: #9A6200;">
                    {{ $summary['ticket_unpaid'] }} belum bayar
                </span>
                <span class="ov-chip" style="background: #FDECEC; color: #9C2222;">
                    {{ $summary['ticket_refunded'] }} refund
                </span>
                <span class="ov-chip" style="background: #F3E8FF; color: #7C3AED;">
                    {{ $summary['merch_paid'] }} merch lunas
                </span>
            </div>
        </div>

        {{-- Status Event --}}
        <div class="ov-card">
            <div class="ov-section-title" style="margin-bottom: 14px;">Status Event</div>
            @php $maxStatus = max(1, (int) ($eventStatus->max() ?? 0)); @endphp
            @forelse ($statusMeta as $key => $meta)
                @php $count = (int) ($eventStatus[$key] ?? 0); @endphp
                @if ($count > 0)
                <div style="margin-bottom: 12px;">
                    <div class="ov-row" style="margin-bottom: 5px;">
                        <span style="font-size: .78rem; font-weight: 600; color: var(--rsc-ink);">{{ $meta['label'] }}</span>
                        <span style="font-size: .78rem; font-weight: 800; color: {{ $meta['color'] }};">{{ $count }}</span>
                    </div>
                    <div style="height: 7px; background: var(--rsc-bg); border-radius: 999px; overflow: hidden;">
                        <div style="height: 100%; width: {{ round($count / $maxStatus * 100) }}%; background: {{ $meta['color'] }}; border-radius: 999px;"></div>
                    </div>
                </div>
                @endif
            @empty
                <p style="font-size: .8rem; color: var(--rsc-subtle);">Belum ada data event.</p>
            @endforelse
        </div>
    </div>

    {{-- ══════════ TOP EVENT + TRANSAKSI TERBARU ══════════ --}}
    <div class="ov-grid ov-2col" style="grid-template-columns: 1fr 1fr;">

        {{-- Top Event --}}
        <div class="ov-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 16px 18px 10px;">
                <div class="ov-section-title">Event Terlaris</div>
                <div class="ov-section-sub">Berdasarkan pendapatan tiket (lunas)</div>
            </div>
            <table class="ov-table">
                <thead>
                    <tr>
                        <th style="width: 36px;">#</th>
                        <th>Event</th>
                        <th style="text-align: right;">Transaksi</th>
                        <th style="text-align: right;">Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topEvents as $i => $ev)
                        <tr>
                            <td><span class="ov-rank">{{ $i + 1 }}</span></td>
                            <td>
                                <div style="font-weight: 600; color: var(--rsc-dark);">{{ $ev->title }}</div>
                                <div style="font-size: .68rem; color: var(--rsc-subtle);">{{ $ev->eo_name ?? 'Tanpa EO' }}</div>
                            </td>
                            <td style="text-align: right; color: var(--rsc-muted);">{{ number_format($ev->trx_count, 0, ',', '.') }}</td>
                            <td style="text-align: right; font-weight: 700; color: var(--rsc-dark);">{{ $rp($ev->revenue) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align: center; color: var(--rsc-subtle); padding: 24px;">Belum ada penjualan tiket.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Transaksi Terbaru --}}
        <div class="ov-card" style="padding: 0; overflow: hidden;">
            <div class="ov-row" style="padding: 16px 18px 10px;">
                <div>
                    <div class="ov-section-title">Transaksi Terbaru</div>
                    <div class="ov-section-sub">Pembayaran tiket terakhir yang berhasil</div>
                </div>
                <a href="{{ route('admin.transactions') }}" class="ov-link">Semua &rarr;</a>
            </div>
            <table class="ov-table">
                <thead>
                    <tr>
                        <th>Pembeli</th>
                        <th>Event</th>
                        <th style="text-align: right;">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $trx)
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: var(--rsc-dark); max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $trx->email }}</div>
                                <div style="font-size: .68rem; color: var(--rsc-subtle);">{{ $trx->paid_time ? \Carbon\Carbon::parse($trx->paid_time)->translatedFormat('d M Y, H:i') : '-' }}</div>
                            </td>
                            <td style="color: var(--rsc-muted); max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $trx->event_title ?? '-' }}</td>
                            <td style="text-align: right; font-weight: 700; color: #1A7A44;">{{ $rp($trx->grand_total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="text-align: center; color: var(--rsc-subtle); padding: 24px;">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const el = document.getElementById('revenueTrend');
        if (!el || typeof Chart === 'undefined') return;

        const labels = @json($trendLabels);
        const data = @json($trendData);

        const ctx = el.getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 260);
        grad.addColorStop(0, 'rgba(249, 115, 22, 0.28)');
        grad.addColorStop(1, 'rgba(249, 115, 22, 0.01)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan',
                    data: data,
                    borderColor: '#f97316',
                    backgroundColor: grad,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#f97316',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (c) => 'Rp ' + new Intl.NumberFormat('id-ID').format(c.parsed.y)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (v) => {
                                if (v >= 1.0e9) return (v / 1.0e9) + ' M';
                                if (v >= 1.0e6) return (v / 1.0e6) + ' jt';
                                if (v >= 1.0e3) return (v / 1.0e3) + ' rb';
                                return v;
                            },
                            color: '#9A8F87', font: { size: 11 }
                        },
                        grid: { color: '#EDE8E3' }
                    },
                    x: {
                        ticks: { color: '#9A8F87', font: { size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    })();
</script>
@endpush
