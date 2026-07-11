@extends('layouts.eo')

@section('title', 'Detail Withdrawal')

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
    color: var(--rsc-text); letter-spacing: -.5px; margin: 0 0 4px;
  }
  .page-header p { color: var(--rsc-muted); font-size: .82rem; margin: 0; }
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
    white-space: nowrap;
  }
  .btn-ghost:hover { border-color: #B5AEA8; color: var(--rsc-text); }

  /* ── Card ── */
  .rsc-card {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
    overflow: hidden;
  }
  .rsc-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--rsc-border);
    background: var(--rsc-surface2);
    display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;
  }
  .rsc-card-header h3 {
    font-family: 'Sora', sans-serif;
    font-size: 1.05rem; font-weight: 800; color: var(--rsc-text); margin: 0;
  }
  .rsc-card-body { padding: 24px; }

  /* ── Status badges ── */
  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
    padding: 5px 12px; border-radius: 20px;
  }
  .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
  .badge-approved { background: #E8F5EE; color: #1A7A44; }
  .badge-rejected { background: #FEF2F2; color: #B91C1C; }
  .badge-pending  { background: #FEF3E2; color: #B45309; }

  /* ── Info panels ── */
  .info-panels {
    display: grid; grid-template-columns: repeat(2, 1fr);
    gap: 18px; margin-bottom: 4px;
  }
  .info-panel-title {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: var(--rsc-accent); margin-bottom: 10px;
  }
  .info-box {
    background: var(--rsc-surface2);
    border: 1px solid var(--rsc-border);
    border-radius: 10px;
    padding: 16px 18px;
  }
  .info-row { margin-bottom: 12px; }
  .info-row:last-child { margin-bottom: 0; }
  .info-row .label { display:block; font-size: .74rem; color: var(--rsc-muted); margin-bottom: 3px; }
  .info-row .value { font-size: .9rem; font-weight: 700; color: var(--rsc-text); }
  .info-row .value.big { font-family: 'Sora', sans-serif; font-size: 1.25rem; font-weight: 800; color: #1A7A44; }
  .info-row .value.note { font-size: .82rem; font-weight: 500; color: var(--rsc-muted); }

  .rsc-divider { border: none; border-top: 1px solid var(--rsc-border); margin: 22px 0; }

  /* ── File section ── */
  .file-section { margin-bottom: 22px; }
  .file-section:last-child { margin-bottom: 0; }
  .file-section h4 {
    font-family: 'Sora', sans-serif;
    font-size: .85rem; font-weight: 800; color: var(--rsc-text);
    margin: 0 0 12px;
  }
  .btn-file {
    border: none; border-radius: 9px;
    padding: 10px 20px; font-size: .82rem; font-weight: 700;
    font-family: 'Sora', sans-serif;
    text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
    cursor: pointer; transition: opacity .15s;
  }
  .btn-file:hover { opacity: .85; }
  .btn-file-invoice { background: #FEF3E2; color: #B45309; }
  .btn-file-transfer { background: #E8F5EE; color: #1A7A44; }
  .file-missing {
    color: #B91C1C; font-size: .78rem; font-weight: 700;
    background: #FEF2F2; padding: 5px 12px; border-radius: 8px;
    display: inline-block;
  }

  /* ── Custom RSC Modal Styles ── */
  .rsc-modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(26, 18, 8, 0.5); backdrop-filter: blur(4px);
    display: flex; justify-content: center; align-items: center;
    z-index: 9999; opacity: 0; pointer-events: none;
    transition: opacity 0.2s ease;
  }
  .rsc-modal-overlay.show { opacity: 1; pointer-events: auto; }
  
  .rsc-modal-container {
    background: var(--rsc-surface);
    border: 1px solid var(--rsc-border);
    border-radius: var(--radius);
    width: 90%; max-width: 800px; max-height: 85vh;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    display: flex; flex-direction: column; overflow: hidden;
    transform: translateY(20px); transition: transform 0.2s ease;
  }
  .rsc-modal-overlay.show .rsc-modal-container { transform: translateY(0); }

  .rsc-modal-header {
    padding: 16px 24px; background: var(--rsc-surface2);
    border-bottom: 1px solid var(--rsc-border);
    display: flex; justify-content: space-between; align-items: center;
  }
  .rsc-modal-header h4 {
    font-family: 'Sora', sans-serif; font-size: 1rem; font-weight: 800;
    color: var(--rsc-text); margin: 0;
  }
  .rsc-modal-close {
    background: none; border: none; font-size: 1.5rem; color: var(--rsc-muted);
    cursor: pointer; font-weight: bold; line-height: 1; padding: 0;
  }
  .rsc-modal-close:hover { color: var(--rsc-text); }
  
  .rsc-modal-body {
    padding: 20px; overflow-y: auto; display: flex; justify-content: center; align-items: center;
    background: #FAF8F6; min-height: 300px;
  }
  .rsc-modal-preview { max-width: 100%; max-height: 70vh; border-radius: 6px; object-fit: contain; }
  .rsc-modal-iframe { width: 100%; height: 70vh; border: none; border-radius: 6px; }

  @media (max-width: 640px) {
    .info-panels { grid-template-columns: 1fr; }
    .page-header { flex-direction: column; align-items: flex-start; }
  }
</style>

<div class="rsc-wrap">

    <div class="page-header">
        <div>
            <h2><span class="accent-dot"></span>Detail Withdrawal</h2>
            <p>Informasi lengkap pengajuan pencairan dana tiket</p>
        </div>

        <a href="{{ route('eo.ticket-history.index') }}" class="btn-ghost">
            Kembali
        </a>
    </div>

    <div class="rsc-card">

        <div class="rsc-card-header">
            <h3>{{ $withdrawal->event_name ?? ($withdrawal->event->title ?? '-') }}</h3>

            @if($withdrawal->status == 'pending')
                <span class="badge badge-pending"><span class="badge-dot"></span>PENDING</span>
            @elseif($withdrawal->status == 'approved')
                <span class="badge badge-approved"><span class="badge-dot"></span>APPROVED</span>
            @else
                <span class="badge badge-rejected"><span class="badge-dot"></span>REJECTED</span>
            @endif
        </div>

        <div class="rsc-card-body">

            <div class="info-panels">

                <div>
                    <div class="info-panel-title">Informasi Penarikan</div>
                    <div class="info-box">
                        <div class="info-row">
                            <span class="label">Nominal Pengajuan</span>
                            <span class="value big">Rp {{ number_format($withdrawal->amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Tanggal Pengajuan</span>
                            <span class="value">{{ \Carbon\Carbon::parse($withdrawal->created_at)->format('d F Y H:i') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Catatan/Log Sistem</span>
                            <span class="value note">{{ $withdrawal->note ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="info-panel-title">Tujuan Transfer Bank</div>
                    <div class="info-box">
                        <div class="info-row">
                            <span class="label">Nama Bank</span>
                            <span class="value">{{ $withdrawal->bank_name ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Nomor Rekening</span>
                            <span class="value">{{ $withdrawal->account_number ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Nama Pemilik Rekening</span>
                            <span class="value">{{ $withdrawal->account_name ?? '-' }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <hr class="rsc-divider">

            {{-- INVOICE --}}
            <div class="file-section">
                <h4>Invoice Pengajuan</h4>
                @if($withdrawal->invoice_file)
                    {{-- Diubah menjadi button dengan attributes data-file --}}
                    <button 
                        type="button"
                        class="btn-file btn-file-invoice"
                        onclick="openRscModal('{{ asset($withdrawal->invoice_file) }}', 'Invoice Pengajuan')"
                    >
                        Lihat Berkas Invoice
                    </button>
                @else
                    <span class="file-missing">
                        Invoice tidak tersedia
                    </span>
                @endif
            </div>

            {{-- BUKTI TRANSFER --}}
            @if($withdrawal->transfer_proof)
            <div class="file-section">
                <h4>Bukti Transfer Owner</h4>
                <button 
                    type="button"
                    class="btn-file btn-file-transfer"
                    onclick="openRscModal('{{ asset($withdrawal->transfer_proof) }}', 'Bukti Transfer Owner')"
                >
                    Lihat Bukti Transfer
                </button>
            </div>
            @endif

        </div>

    </div>

</div>

<div id="rscModal" class="rsc-modal-overlay" onclick="closeRscModal(event)">
    <div class="rsc-modal-container" onclick="event.stopPropagation()">
        <div class="rsc-modal-header">
            <h4 id="rscModalTitle">Pratinjau Berkas</h4>
            <button class="rsc-modal-close" onclick="closeRscModal(true)">&times;</button>
        </div>
        <div class="rsc-modal-body" id="rscModalBody">
            </div>
    </div>
</div>

<script>
    function openRscModal(fileUrl, title) {
        const modal = document.getElementById('rscModal');
        const modalTitle = document.getElementById('rscModalTitle');
        const modalBody = document.getElementById('rscModalBody');
        
        modalTitle.innerText = title;
        modalBody.innerHTML = ''; // bersihkan data lama
        
        // Ambil ekstensi berkas secara lowercase
        const extension = fileUrl.split('.').pop().toLowerCase();
        
        if (extension === 'pdf') {
            // Render menggunakan iframe jika berkas adalah PDF
            modalBody.innerHTML = `<iframe src="${fileUrl}" class="rsc-modal-iframe"></iframe>`;
        } else if (['jpg', 'jpeg', 'png'].includes(extension)) {
            // Render menggunakan gambar biasa
            modalBody.innerHTML = `<img src="${fileUrl}" class="rsc-modal-preview" alt="${title}">`;
        } else {
            // Pengaman jika format file tidak didukung
            modalBody.innerHTML = `<div style="text-align:center; padding: 20px; color: var(--rsc-muted);">
                <p>Format tidak dapat dipratinjau langsung.</p>
                <a href="${fileUrl}" class="btn-ghost" target="_blank" style="display:inline-flex;">Unduh / Buka Berkas Langsung</a>
            </div>`;
        }
        
        // Tampilkan modal
        modal.classList.add('show');
        document.body.style.overflow = 'hidden'; // Kunci scroll halaman belakang
    }

    function closeRscModal(force = false) {
        // Jika parameter force bernilai true atau area klik merupakan overlay luar modal
        if (force === true || event.target.id === 'rscModal') {
            const modal = document.getElementById('rscModal');
            modal.classList.remove('show');
            document.body.style.overflow = ''; // Aktifkan kembali scroll halaman
            
            // Hapus isi body setelah transisi selesai biar iframe/audio mati
            setTimeout(() => {
                document.getElementById('rscModalBody').innerHTML = '';
            }, 200);
        }
    }

    // Dukungan menutup modal dengan menekan tombol tombol 'ESC' di keyboard
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRscModal(true);
        }
    });
</script>

@endsection