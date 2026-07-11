{{-- resources/views/admin/monitoring/eo-show.blade.php --}}
@extends('layouts.admin') {{-- sesuaikan dengan layout admin yang sudah kamu pakai --}}

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6" style="font-family:'Poppins',sans-serif;">

    <a href="{{ route('admin.monitoring.index') }}"
       class="inline-flex items-center gap-2 text-xs font-medium mb-4 transition-colors"
       style="color:#6b7280;">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Kembali ke Daftar EO
    </a>

    {{-- ===================== HEADER PROFIL EO (compact, 1 baris) ===================== --}}
    <div class="card rounded-xl p-4 mb-4 relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1" style="background: linear-gradient(to right, #f97316, #facc15);"></div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 flex items-center justify-center"
                 style="background:#fff7ed; border:2px solid #fed7aa;">
                @if ($eo->logo)
                    <img src="{{ asset($eo->logo) }}" alt="{{ $eo->nama_badan_usaha }}" class="w-full h-full object-cover">
                @else
                    <span class="text-sm font-bold" style="color:#f97316;">{{ strtoupper(substr($eo->nama_badan_usaha, 0, 2)) }}</span>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-base font-bold" style="color:#111827;">{{ $eo->nama_badan_usaha }}</h1>
                    @php
                        $statusStyle = match ($eo->status) {
                            'approved' => ['bg-green-100', 'text-green-700', 'Approved'],
                            'pending'  => ['bg-yellow-100', 'text-yellow-700', 'Pending'],
                            'rejected' => ['bg-red-100', 'text-red-700', 'Rejected'],
                            default    => ['bg-gray-100', 'text-gray-600', ucfirst($eo->status ?? '-')],
                        };
                    @endphp
                    <span class="text-[9px] font-semibold px-2 py-0.5 rounded-full {{ $statusStyle[0] }} {{ $statusStyle[1] }}">{{ $statusStyle[2] }}</span>
                </div>
                <p class="text-xs mt-0.5" style="color:#9ca3af;">
                    {{ $eoEmail ?? '-' }} &middot; Bergabung {{ \Carbon\Carbon::parse($eo->created_at)->translatedFormat('d M Y') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ===================== BARIS INFO: 4 KOLOM SEJAJAR (auto sama tinggi via CSS Grid) ===================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        {{-- RINGKASAN FINANSIAL --}}
        <div class="card rounded-xl p-4 flex flex-col">
            <h2 class="text-xs font-bold mb-3 flex items-center gap-1.5" style="color:#111827;">
                <svg class="w-3.5 h-3.5" style="color:#f97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m0-2c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Ringkasan Finansial
            </h2>
            <div class="space-y-2 flex-1">
                <div class="flex items-center justify-between text-xs">
                    <span style="color:#9ca3af;">Pendapatan</span>
                    <span class="font-bold" style="color:#111827;">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between text-xs pt-2" style="border-top:1px dashed #e5e7eb;">
                    <span style="color:#9ca3af;">Saldo Dompet</span>
                    <span class="font-bold" style="color:#f97316;">Rp {{ number_format($walletBalance, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between text-xs pt-2" style="border-top:1px dashed #e5e7eb;">
                    <span style="color:#9ca3af;">Tanggungan Utang</span>
                    <span class="font-bold" style="color: {{ $outstandingDebt > 0 ? '#ef4444' : '#111827' }};">
                        Rp {{ number_format($outstandingDebt, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- LEGALITAS --}}
        <div class="card rounded-xl p-4 flex flex-col">
            <h2 class="text-xs font-bold mb-3 flex items-center gap-1.5" style="color:#111827;">
                <svg class="w-3.5 h-3.5" style="color:#f97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Legalitas
            </h2>
            <div class="flex-1">
                <p class="text-[10px] uppercase font-semibold" style="color:#9ca3af;">Penanggung Jawab</p>
                <p class="text-xs font-medium mb-2" style="color:#374151;">{{ $eo->penanggung_jawab ?? '-' }}</p>
                <p class="text-[10px] uppercase font-semibold" style="color:#9ca3af;">Alamat</p>
                <p class="text-xs font-medium" style="color:#374151; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"
                   title="{{ $eo->alamat_badan_usaha }}">
                    {{ $eo->alamat_badan_usaha ?? '-' }}
                </p>
            </div>
            <div class="grid grid-cols-2 gap-1.5 mt-3 pt-3" style="border-top:1px dashed #e5e7eb;">
                @if ($eo->dokumen_badan_usaha)
                    <a href="{{ asset($eo->dokumen_badan_usaha) }}" target="_blank"
                       class="text-center text-[10px] font-semibold py-1.5 rounded-lg" style="background:#fff7ed; color:#f97316;">
                        Dok. Usaha
                    </a>
                @else
                    <span class="text-center text-[10px] py-1.5 rounded-lg" style="background:#f9fafb; color:#9ca3af;">Dok. Usaha -</span>
                @endif
                @if ($eo->ktp_penanggung_jawab)
                    <a href="{{ asset($eo->ktp_penanggung_jawab) }}" target="_blank"
                       class="text-center text-[10px] font-semibold py-1.5 rounded-lg" style="background:#fff7ed; color:#f97316;">
                        KTP PJ
                    </a>
                @else
                    <span class="text-center text-[10px] py-1.5 rounded-lg" style="background:#f9fafb; color:#9ca3af;">KTP -</span>
                @endif
            </div>
        </div>

        {{-- REKENING --}}
        <div class="card rounded-xl p-4 flex flex-col">
            <h2 class="text-xs font-bold mb-3 flex items-center gap-1.5" style="color:#111827;">
                <svg class="w-3.5 h-3.5" style="color:#f97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Rekening Withdrawal
            </h2>
            <div class="space-y-2 flex-1">
                <div>
                    <p class="text-[10px] uppercase font-semibold" style="color:#9ca3af;">Bank</p>
                    <p class="text-xs font-medium" style="color:#374151;">{{ $eo->bank_name ?? '-' }}</p>
                </div>
                <div class="pt-2" style="border-top:1px dashed #e5e7eb;">
                    <p class="text-[10px] uppercase font-semibold" style="color:#9ca3af;">Nama Pemilik</p>
                    <p class="text-xs font-medium" style="color:#374151;">{{ $eo->account_name ?? '-' }}</p>
                </div>
                <div class="pt-2" style="border-top:1px dashed #e5e7eb;">
                    <p class="text-[10px] uppercase font-semibold" style="color:#9ca3af;">No. Rekening</p>
                    <p class="text-xs font-medium" style="color:#374151;">{{ $eo->account_number ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- TRACK RECORD --}}
        <div class="card rounded-xl p-4 flex flex-col">
            <h2 class="text-xs font-bold mb-3 flex items-center gap-1.5" style="color:#111827;">
                <svg class="w-3.5 h-3.5" style="color:#f97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6m-9 0h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Status Event
            </h2>
            <div class="grid grid-cols-2 gap-2 flex-1">
                <div class="rounded-lg text-center py-2.5 flex flex-col justify-center" style="background:#f0fdf4;">
                    <p class="text-base font-bold" style="color:#16a34a;">{{ $statusCount['selesai'] }}</p>
                    <p class="text-[9px] font-medium" style="color:#16a34a;">Selesai</p>
                </div>
                <div class="rounded-lg text-center py-2.5 flex flex-col justify-center" style="background:#eff6ff;">
                    <p class="text-base font-bold" style="color:#2563eb;">{{ $statusCount['aktif'] }}</p>
                    <p class="text-[9px] font-medium" style="color:#2563eb;">Aktif</p>
                </div>
                <div class="rounded-lg text-center py-2.5 flex flex-col justify-center" style="background:#f0f9ff;">
                    <p class="text-base font-bold" style="color:#0284c7;">{{ $statusCount['resched'] }}</p>
                    <p class="text-[9px] font-medium" style="color:#0284c7;">Resched</p>
                </div>
                <div class="rounded-lg text-center py-2.5 flex flex-col justify-center" style="background:#fef2f2;">
                    <p class="text-base font-bold" style="color:#dc2626;">{{ $statusCount['batal'] }}</p>
                    <p class="text-[9px] font-medium" style="color:#dc2626;">Batal</p>
                </div>
            </div>
        </div>

    </div>

    {{-- ===================== TREN PENDAPATAN (full width, tinggi tetap ringkas) ===================== --}}
    <div class="card rounded-xl p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-bold flex items-center gap-1.5" style="color:#111827;">
                <svg class="w-3.5 h-3.5" style="color:#f97316;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Tren Pendapatan 6 Bulan Terakhir
            </h2>
            <span class="text-[10px]" style="color:#9ca3af;">Tiket + Merch (paid)</span>
        </div>

        <div class="flex items-end justify-between gap-2" style="height: 90px;">
            @foreach ($revenueTrend as $m)
                @php $heightPct = max(6, round(($m['total'] / $revenueTrendMax) * 100)); @endphp
                <div class="flex-1 flex flex-col items-center justify-end h-full group">
                    <p class="text-[9px] font-semibold mb-1 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap" style="color:#f97316;">
                        Rp {{ number_format($m['total'], 0, ',', '.') }}
                    </p>
                    <div class="w-full rounded-t-md transition-all duration-300 group-hover:opacity-80"
                         style="height: {{ $heightPct }}%; background: linear-gradient(to top, #f97316, #facc15); min-height:4px;"></div>
                    <p class="text-[9px] font-medium mt-1.5" style="color:#9ca3af;">{{ $m['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===================== PORTOFOLIO EVENT ===================== --}}
    <div class="card rounded-xl p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
            <h2 class="text-xs font-bold" style="color:#111827;">Portofolio Semua Event ({{ $events->count() }})</h2>

            <div class="flex items-center gap-2">
                <input type="text" id="event-search" placeholder="Cari event..."
                       class="form-control text-xs py-1.5 px-3" style="border-radius: 9999px; width: 160px;">
                <select id="event-sort" class="form-control text-xs py-1.5 px-3" style="border-radius: 9999px;">
                    <option value="date">Terbaru</option>
                    <option value="gmv">GMV Tertinggi</option>
                    <option value="tickets">Tiket Terlaris</option>
                </select>
            </div>
        </div>

        {{-- TABS FILTER STATUS --}}
        <div class="flex items-center gap-1.5 mb-3 flex-wrap" id="event-tabs">
            <button type="button" data-status="all" class="event-tab-btn text-[10px] font-semibold px-2.5 py-1 rounded-full transition-colors" style="background:#f97316; color:#fff;">
                Semua ({{ $events->count() }})
            </button>
            <button type="button" data-status="approved" class="event-tab-btn text-[10px] font-semibold px-2.5 py-1 rounded-full transition-colors" style="background:#f3f4f6; color:#374151;">
                Approved ({{ $events->where('status', 'approved')->count() }})
            </button>
            <button type="button" data-status="cancelled" class="event-tab-btn text-[10px] font-semibold px-2.5 py-1 rounded-full transition-colors" style="background:#f3f4f6; color:#374151;">
                Cancelled ({{ $events->where('status', 'cancelled')->count() }})
            </button>
            <button type="button" data-status="pending_cancel,pending_reschedule" class="event-tab-btn text-[10px] font-semibold px-2.5 py-1 rounded-full transition-colors" style="background:#f3f4f6; color:#374151;">
                Proses ({{ $events->whereIn('status', ['pending_cancel', 'pending_reschedule'])->count() }})
            </button>
        </div>

        <div class="space-y-2" id="event-list">
            @forelse ($events as $event)
                @php
                    $badgeStyle = match ($event->status) {
                        'approved' => ['#f0fdf4', '#16a34a'],
                        'cancelled' => ['#fef2f2', '#dc2626'],
                        'pending_cancel', 'pending_reschedule' => ['#fffbeb', '#d97706'],
                        default => ['#f3f4f6', '#6b7280'],
                    };
                    $eventGmv = $event->ticket_gmv + $event->merch_gmv;
                @endphp

                <div class="rounded-xl overflow-hidden event-card" style="border:1px solid #e5e7eb;"
                     data-event-id="{{ $event->id }}"
                     data-status="{{ $event->status }}"
                     data-title="{{ strtolower($event->title) }}"
                     data-gmv="{{ $eventGmv }}"
                     data-tickets="{{ $event->tickets_sold }}"
                     data-date="{{ $event->date }}">

                    <button type="button" class="w-full text-left px-3.5 py-3 flex items-center justify-between event-toggle">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                                <p class="font-semibold text-sm truncate" style="color:#111827;">{{ $event->title }}</p>
                                <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full uppercase"
                                      style="background:{{ $badgeStyle[0] }}; color:{{ $badgeStyle[1] }};">
                                    {{ $event->status }}
                                </span>
                                @if ($event->is_rescheduled > 0)
                                    <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full" style="background:#f0f9ff; color:#0284c7;">
                                        Resched {{ $event->is_rescheduled }}x
                                    </span>
                                @endif
                                @if ($event->withdraw_locked)
                                    <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full" style="background:#fef2f2; color:#dc2626;">🔒</span>
                                @endif
                            </div>
                            <p class="text-[11px]" style="color:#9ca3af;">
                                {{ $event->tickets_sold }} Pax &middot; {{ \Carbon\Carbon::parse($event->date)->translatedFormat('d M Y') }}
                                @if ($event->location) &middot; {{ $event->location }} @endif
                            </p>
                        </div>
                        <div class="text-right shrink-0 ml-3">
                            <p class="font-bold text-sm" style="color:#111827;">Rp {{ number_format($eventGmv, 0, ',', '.') }}</p>
                            <svg class="w-3.5 h-3.5 ml-auto mt-0.5 transition-transform chevron" style="color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>

                    <div class="event-detail hidden px-3.5 py-3" style="background:#fafafa; border-top:1px solid #e5e7eb;">
                        <div class="event-loading text-center text-xs py-3" style="color:#9ca3af;">Memuat ringkasan...</div>
                        <div class="event-content hidden"></div>
                    </div>
                </div>
            @empty
                <p class="text-center py-8 text-sm" style="color:#9ca3af;">EO ini belum memiliki event.</p>
            @endforelse
        </div>

        <p id="event-empty-filtered" class="hidden text-center py-8 text-sm" style="color:#9ca3af;">
            Tidak ada event yang cocok dengan filter/pencarian.
        </p>
    </div>
</div>

{{-- Template ringkasan dropdown, di-render via JS --}}
<template id="event-detail-template">
    <div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
            <div class="rounded-lg p-2.5" style="background:#fff; border:1px solid #e5e7eb;">
                <p class="text-[10px]" style="color:#9ca3af;">GMV Tiket</p>
                <p class="font-bold text-xs" style="color:#111827;" data-field="ticket_gmv"></p>
                <p class="text-[10px] mt-0.5" style="color:#9ca3af;" data-field="ticket_trx_label"></p>
            </div>
            <div class="rounded-lg p-2.5" style="background:#fff; border:1px solid #e5e7eb;">
                <p class="text-[10px]" style="color:#9ca3af;">GMV Merch</p>
                <p class="font-bold text-xs" style="color:#111827;" data-field="merch_gmv"></p>
                <p class="text-[10px] mt-0.5" style="color:#9ca3af;" data-field="merch_trx_label"></p>
            </div>
            <div class="rounded-lg p-2.5" style="background:#fff; border:1px solid #e5e7eb;">
                <p class="text-[10px]" style="color:#9ca3af;">Tiket Terjual</p>
                <p class="font-bold text-xs" style="color:#f97316;" data-field="tickets_sold"></p>
            </div>
            <div class="rounded-lg p-2.5" style="background:#fff; border:1px solid #e5e7eb;">
                <p class="text-[10px]" style="color:#9ca3af;">Merch Terjual</p>
                <p class="font-bold text-xs" style="color:#f97316;" data-field="merch_qty_sold"></p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 mb-2.5">
            <label class="text-[11px]" style="color:#6b7280;">Dari</label>
            <input type="date" class="form-control text-[11px] px-2 py-1 filter-start-date" style="width:auto;">
            <label class="text-[11px]" style="color:#6b7280;">Sampai</label>
            <input type="date" class="form-control text-[11px] px-2 py-1 filter-end-date" style="width:auto;">
            <select class="form-control text-[11px] px-2 py-1 filter-group-by" style="width:auto;">
                <option value="day">Harian</option>
                <option value="week">Mingguan</option>
                <option value="month">Bulanan</option>
            </select>
            <button type="button" class="btn-orange-pill filter-apply" style="padding: 5px 12px; font-size: 10px;">
                Terapkan
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead>
                    <tr class="text-left border-b" style="color:#9ca3af;">
                        <th class="py-1.5 pr-2">Periode</th>
                        <th class="py-1.5 pr-2 text-right">Trx Tiket</th>
                        <th class="py-1.5 pr-2 text-right">GMV Tiket</th>
                        <th class="py-1.5 pr-2 text-right">Trx Merch</th>
                        <th class="py-1.5 pr-2 text-right">GMV Merch</th>
                        <th class="py-1.5 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="trend-body"></tbody>
            </table>
            <p class="trend-empty hidden text-center py-3" style="color:#9ca3af;">Tidak ada penjualan pada rentang ini.</p>
        </div>
    </div>
</template>

@push('scripts')
<script>
(function () {
    const summaryUrlBase = "{{ url('/admin/monitoring/event') }}";
    const formatRupiah = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID');

    document.querySelectorAll('.event-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const card = btn.closest('.event-card');
            const detail = card.querySelector('.event-detail');
            const chevron = btn.querySelector('.chevron');
            const isHidden = detail.classList.contains('hidden');

            detail.classList.toggle('hidden');
            chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';

            if (isHidden && !card.dataset.loaded) {
                loadEventSummary(card);
            }
        });
    });

    function loadEventSummary(card, params = {}) {
        const eventId = card.dataset.eventId;
        const loading = card.querySelector('.event-loading');
        const content = card.querySelector('.event-content');

        loading.classList.remove('hidden');
        content.classList.add('hidden');

        const query = new URLSearchParams(params).toString();
        fetch(`${summaryUrlBase}/${eventId}/summary${query ? '?' + query : ''}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then((res) => res.json())
            .then((data) => {
                if (!content.dataset.rendered) {
                    renderTemplate(card, content, data);
                    bindFilter(card, content);
                    content.dataset.rendered = '1';
                } else {
                    fillData(content, data);
                }
                card.dataset.loaded = '1';
                loading.classList.add('hidden');
                content.classList.remove('hidden');
            })
            .catch(() => {
                loading.textContent = 'Gagal memuat ringkasan. Coba lagi.';
            });
    }

    function renderTemplate(card, content, data) {
        const tpl = document.getElementById('event-detail-template');
        content.appendChild(tpl.content.cloneNode(true));
        content.querySelector('.filter-start-date').value = data.filter.start_date;
        content.querySelector('.filter-end-date').value = data.filter.end_date;
        content.querySelector('.filter-group-by').value = data.filter.group_by;
        fillData(content, data);
    }

    function fillData(content, data) {
        const s = data.summary;
        content.querySelector('[data-field="ticket_gmv"]').textContent = formatRupiah(s.ticket_gmv);
        content.querySelector('[data-field="ticket_trx_label"]').textContent = `${s.ticket_trx} transaksi`;
        content.querySelector('[data-field="merch_gmv"]').textContent = formatRupiah(s.merch_gmv);
        content.querySelector('[data-field="merch_trx_label"]').textContent = `${s.merch_trx} transaksi`;
        content.querySelector('[data-field="tickets_sold"]').textContent = `${s.tickets_sold} Pax`;
        content.querySelector('[data-field="merch_qty_sold"]').textContent = `${s.merch_qty_sold} pcs`;

        const tbody = content.querySelector('.trend-body');
        const emptyEl = content.querySelector('.trend-empty');
        tbody.innerHTML = '';

        if (!data.trend.length) {
            emptyEl.classList.remove('hidden');
        } else {
            emptyEl.classList.add('hidden');
            data.trend.forEach((row) => {
                const tr = document.createElement('tr');
                tr.className = 'border-b last:border-0';
                tr.innerHTML = `
                    <td class="py-1.5 pr-2">${row.period}</td>
                    <td class="py-1.5 pr-2 text-right">${row.ticket_trx}</td>
                    <td class="py-1.5 pr-2 text-right">${formatRupiah(row.ticket_gmv)}</td>
                    <td class="py-1.5 pr-2 text-right">${row.merch_trx}</td>
                    <td class="py-1.5 pr-2 text-right">${formatRupiah(row.merch_gmv)}</td>
                    <td class="py-1.5 text-right font-semibold">${formatRupiah(row.total_gmv)}</td>
                `;
                tbody.appendChild(tr);
            });
        }
    }

    function bindFilter(card, content) {
        content.querySelector('.filter-apply').addEventListener('click', function () {
            loadEventSummary(card, {
                start_date: content.querySelector('.filter-start-date').value,
                end_date: content.querySelector('.filter-end-date').value,
                group_by: content.querySelector('.filter-group-by').value,
            });
        });
    }

    // ===== Filter tab status =====
    const tabButtons = document.querySelectorAll('.event-tab-btn');
    const eventList = document.getElementById('event-list');
    const emptyFiltered = document.getElementById('event-empty-filtered');
    let activeStatus = 'all';
    let activeSearch = '';

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', function () {
            tabButtons.forEach((b) => {
                b.style.background = '#f3f4f6';
                b.style.color = '#374151';
            });
            btn.style.background = '#f97316';
            btn.style.color = '#fff';
            activeStatus = btn.dataset.status;
            applyFilter();
        });
    });

    document.getElementById('event-search').addEventListener('input', function (e) {
        activeSearch = e.target.value.toLowerCase().trim();
        applyFilter();
    });

    function applyFilter() {
        const cards = Array.from(eventList.querySelectorAll('.event-card'));
        let visibleCount = 0;

        cards.forEach((card) => {
            const statusMatch = activeStatus === 'all' || activeStatus.split(',').includes(card.dataset.status);
            const searchMatch = !activeSearch || card.dataset.title.includes(activeSearch);
            const show = statusMatch && searchMatch;
            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        emptyFiltered.classList.toggle('hidden', visibleCount > 0);
    }

    document.getElementById('event-sort').addEventListener('change', function (e) {
        const cards = Array.from(eventList.querySelectorAll('.event-card'));
        const sortBy = e.target.value;

        cards.sort((a, b) => {
            if (sortBy === 'gmv') return parseFloat(b.dataset.gmv) - parseFloat(a.dataset.gmv);
            if (sortBy === 'tickets') return parseInt(b.dataset.tickets) - parseInt(a.dataset.tickets);
            return new Date(b.dataset.date) - new Date(a.dataset.date);
        });

        cards.forEach((card) => eventList.appendChild(card));
    });
})();
</script>
@endpush
@endsection