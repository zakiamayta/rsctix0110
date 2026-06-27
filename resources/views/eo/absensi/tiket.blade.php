@extends('layouts.eo')

@section('title', 'Pantauan Absensi Pengunjung')

@section('content')
<div class="container-fluid py-4">
    <h2 class="h4 fw-bold text-dark mb-4">Pantauan Absensi Pengunjung</h2>

    {{-- 🔹 Ringkasan Absen --}}
    @php
        $totalSudah = $attendees->filter(fn($a) => $a->transaction?->is_registered)->count();
        $totalBelum = $attendees->filter(fn($a) => !$a->transaction?->is_registered)->count();
    @endphp
    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card border-0 bg-success bg-opacity-10 p-3 rounded-4 shadow-sm h-100 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <p class="small text-success fw-bold text-uppercase mb-1">Sudah Absen</p>
                    <h3 class="fw-bold text-success mb-0">{{ $totalSudah }}</h3>
                </div>
                <div class="bg-success text-white p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card border-0 bg-danger bg-opacity-10 p-3 rounded-4 shadow-sm h-100 d-flex flex-row align-items-center justify-content-between">
                <div>
                    <p class="small text-danger fw-bold text-uppercase mb-1">Belum Absen</p>
                    <h3 class="fw-bold text-danger mb-0">{{ $totalBelum }}</h3>
                </div>
                <div class="bg-danger text-white p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- 🔹 Form Filter & Sort --}}
    <div class="card border-0 shadow-sm p-4 rounded-4 mb-4">
        <form method="GET" action="{{ route('eo.absensi.tiket') }}" class="row g-3 align-items-end">
            {{-- Pilih Event --}}
            <div class="col-12 col-md-3">
                <label for="event_id" class="form-label small fw-medium text-secondary mb-1">Pilih Event</label>
                <select id="event_id" name="event_id" class="form-select form-select-sm">
                    <option value="">-- Semua Event --</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                            {{ $event->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Pencarian --}}
            <div class="col-12 col-md-3">
                <label for="search" class="form-label small fw-medium text-secondary mb-1">Pencarian</label>
                <input type="text" id="search" name="search" value="{{ $search ?? '' }}"
                    placeholder="Cari nama, email, atau no. telp" class="form-control form-control-sm">
            </div>

            {{-- Status --}}
            <div class="col-12 col-md-3">
                <label for="status" class="form-label small fw-medium text-secondary mb-1">Status Absen</label>
                <select id="status" name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="sudah" {{ ($status ?? '') === 'sudah' ? 'selected' : '' }}>Sudah Absen</option>
                    <option value="belum" {{ ($status ?? '') === 'belum' ? 'selected' : '' }}>Belum Absen</option>
                </select>
            </div>

            {{-- Tombol Aksi --}}
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold w-100">
                    Filter
                </button>
                <a href="{{ route('eo.absensi.tiket') }}" class="btn btn-light btn-sm px-4 fw-semibold text-secondary border w-100">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- 🔹 Tabel Absensi --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0 text-sm">
                <thead class="table-light text-secondary fw-semibold">
                    <tr>
                        <th class="px-4 py-3 text-center" style="width: 70px;">No</th>
                        <th class="px-4 py-3">Nama Event</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Nama Pengunjung</th>
                        <th class="px-4 py-3">Status Absen</th>
                        <th class="px-4 py-3 text-center">QR Code</th>
                        <th class="px-4 py-3 text-center" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendees as $index => $attendee)
                        <tr class="border-bottom">
                            <td class="px-4 py-3 text-center fw-medium text-secondary">
                                {{ ($attendees->currentPage() - 1) * $attendees->perPage() + $index + 1 }}
                            </td>
                            <td class="px-4 py-3 text-dark">{{ $attendee->transaction?->event?->title ?? '-' }}</td>
                            <td class="px-4 py-3 text-secondary">{{ $attendee->transaction?->email ?? '-' }}</td>
                            <td class="px-4 py-3 fw-medium text-dark">{{ $attendee->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if ($attendee->transaction?->is_registered)
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-semibold">Sudah Absen</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill fw-semibold">Belum Absen</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($attendee->transaction?->qr_code)
                                    <a href="{{ route('absen.form', $attendee->transaction->kode_unik) }}" target="_blank"
                                        class="btn btn-outline-primary btn-sm px-3 py-1 rounded-3 fw-medium" style="font-size: 0.75rem;">
                                        QR
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    @if ($attendee->transaction)
                                        {{-- Detail Pembeli --}}
                                        <button onclick="showDetail({{ $attendee->id }})"
                                            class="btn btn-sm btn-info bg-info bg-opacity-10 text-info border-0 rounded-circle p-2 d-flex align-items-center justify-content-center"
                                            style="width: 35px; height: 35px;" title="Detail Pembeli">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Tombol Absen/Batalkan --}}
                                    @if (!$attendee->transaction?->is_registered)
                                        <form method="POST" action="{{ route('eo.absensi.manual', ['id' => $attendee->id]) }}" class="m-0">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-sm btn-success bg-success bg-opacity-10 text-success border-0 rounded-circle p-2 d-flex align-items-center justify-content-center"
                                                style="width: 35px; height: 35px;" title="Tandai Sudah Absen">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('eo.absensi.batal', ['id' => $attendee->id]) }}" class="m-0">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-sm btn-danger bg-danger bg-opacity-10 text-danger border-0 rounded-circle p-2 d-flex align-items-center justify-content-center"
                                                style="width: 35px; height: 35px;" title="Batalkan Absen">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                Tidak ada data peserta.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($attendees->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4">
                {{ $attendees->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

{{-- 🔹 Modal Detail (Bootstrap 5 native format) --}}
<div class="modal fade" id="detailModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="detailModalLabel">Detail Pembeli Tiket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div id="modalContent" class="text-sm"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary w-100 fw-semibold py-2 rounded-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    const attendeeData = @json($attendees->items());

    function escapeHtml(str) {
        if (!str) return '-';
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }

    function showDetail(attendeeId) {
        const attendee = attendeeData.find(a => a.id === attendeeId);
        const modalContent = document.getElementById('modalContent');

        if (!attendee) {
            modalContent.innerHTML = '<p class="text-muted text-center my-3">Data peserta tidak ditemukan.</p>';
        } else {
            const t = attendee.transaction ?? {};
            modalContent.innerHTML = `
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="fw-semibold text-secondary py-1" style="width: 35%;">Email:</td><td class="text-dark py-1">${escapeHtml(t.email)}</td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">Waktu Checkout:</td><td class="text-dark py-1">${t.checkout_time ? new Date(t.checkout_time).toLocaleString('id-ID') : '-'}</td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">Status Bayar:</td><td class="text-dark py-1"><span class="badge bg-secondary bg-opacity-10 text-secondary">${escapeHtml(t.payment_status)}</span></td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">Nama Peserta:</td><td class="text-dark py-1">${escapeHtml(attendee.name)}</td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">No HP:</td><td class="text-dark py-1">${escapeHtml(attendee.phone_number)}</td></tr>
                        <tr><td class="fw-semibold text-secondary py-1">ID Tiket:</td><td class="text-dark py-1"><code class="text-primary">${escapeHtml(attendee.ticket_id)}</code></td></tr>
                    </table>
                </div>
            `;
        }

        // Memanggil modal bawaan Bootstrap 5
        const myModal = new bootstrap.Modal(document.getElementById('detailModal'));
        myModal.show();
    }
</script>
@endsection