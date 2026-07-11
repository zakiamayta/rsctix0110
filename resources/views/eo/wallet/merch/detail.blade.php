@extends('layouts.eo')

@section('title', 'Detail Withdrawal Merchandise')

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

  /* ── Info grid ── */
  .info-grid {
    display: grid; grid-template-columns: repeat(2, 1fr);
    gap: 20px; margin-bottom: 8px;
  }
  .info-label {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: var(--rsc-muted); margin-bottom: 6px;
  }
  .info-value {
    font-family: 'Sora', sans-serif;
    font-weight: 800; font-size: 1.3rem; color: var(--rsc-text);
  }
  .info-value.amount { color: #1A7A44; }
  .info-value.plain { font-size: .95rem; font-weight: 700; }

  .rsc-divider { border: none; border-top: 1px solid var(--rsc-border); margin: 22px 0; }

  /* ── Note box ── */
  .note-box {
    background: var(--rsc-surface2);
    border: 1px solid var(--rsc-border);
    border-radius: 10px;
    padding: 14px 16px;
    font-size: .85rem;
    color: var(--rsc-text);
    white-space: pre-line;
  }

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
    transition: opacity .15s; cursor: pointer;
  }
  .btn-file:hover { opacity: .85; }
  .btn-file-invoice { background: #FEF3E2; color: #B45309; }
  .btn-file-transfer { background: #E8F5EE; color: #1A7A44; }
  .file-missing { color: #B91C1C; font-size: .82rem; font-weight: 600; }

  /* ── Custom Modal Styles ── */
  .rsc-modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(26, 18, 8, 0.45); backdrop-filter: blur(4px);
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
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    display: flex; flex-direction: column; overflow: hidden;
    transform: translateY(15px); transition: transform 0.2s ease;
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
    background: #FAF8F6; min-height: 320px;
  }
  .rsc-modal-preview { max-width: 100%; max-height: 70vh; border-radius: 6px; object-fit: contain; }
  .rsc-modal-iframe { width: 100%; height: 70vh; border: none; border-radius: 6px; }

  @media (max-width: 640px) {
    .info-grid { grid-template-columns: 1fr; }
    .page-header { flex-direction: column; align-items: flex-start; }
  }
</style>

<div class="rsc-wrap">

    <div class="page-header">
        <div>
            <h2><span class="accent-dot"></span>Detail Withdrawal Merch</h2>
            <p>Informasi lengkap pengajuan pencairan komisi merchandise</p>
        </div>

        <a href="{{ route('eo.merch-wallet.dashboard') }}" class="btn-ghost">
            Kembali
        </a>
    </div>

    <div class="rsc-card">

        <div class="rsc-card-header">
            <h3>{{ $withdrawal->event_title ?? 'Event Tidak Diketahui' }}</h3>

            @if($withdrawal->status == 'pending')
                <span class="badge badge-pending"><span class="badge-dot"></span>PENDING</span>
            @elseif($withdrawal->status == 'approved')
                <span class="badge badge-approved"><span class="badge-dot"></span>APPROVED</span>
            @else
                <span class="badge badge-rejected"><span class="badge-dot"></span>REJECTED</span>
            @endif
        </div>

        <div class="rsc-card-body">

            <div class="info-grid">

                <div>
                    <div class="info-label">Nominal Dana Diperoleh</div>
                    <div class="info-value amount">
                        Rp {{ number_format($withdrawal->amount, 0, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="info-label">Tanggal Pengajuan</div>
                    <div class="info-value plain">
                        {{ \Carbon\Carbon::parse($withdrawal->created_at)->format('d M Y, H:i') }} WIB
                    </div>
                </div>

            </div>

            <hr class="rsc-divider">

            <div class="file-section">
                <h4>Catatan Riwayat Transaksi</h4>
                <div class="note-box">{{ $withdrawal->note ?? 'Tidak ada catatan tambahan.' }}</div>
            </div>

            <hr class="rsc-divider">

            {{-- INVOICE --}}
            <div class="file-section">
                <h4>Invoice Pengajuan</h4>
                @if($withdrawal->invoice_file)
                    <button
                        type="button"
                        class="btn-file btn-file-invoice"
                        onclick="openRscModal('{{ asset($withdrawal->invoice_file) }}', 'Invoice Pengajuan')"
                    >
                        Lihat Invoice
                    </button>
                @else
                    <span class="file-missing">
                        Invoice berkas tidak tersedia atau hilang
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
                    onclick="openRscModal('{{ asset('storage/' . $withdrawal->transfer_proof) }}', 'Bukti Transfer Bank')"
                >
                    Lihat Bukti Transfer Bank
                </button>
            </div>
            @endif

        </div>

    </div>

</div>

<!-- AREA CONTAINER POPUP MODAL -->
<div id="rscModal" class="rsc-modal-overlay" onclick="closeRscModal(event)">
    <div class="rsc-modal-container" onclick="event.stopPropagation()">
        <div class="rsc-modal-header">
            <h4 id="rscModalTitle">Pratinjau Berkas</h4>
            <button class="rsc-modal-close" onclick="closeRscModal(true)">&times;</button>
        </div>
        <div class="rsc-modal-body" id="rscModalBody">
            <!-- Isi pratinjau dirender oleh script js -->
        </div>
    </div>
</div>

<script>
    function openRscModal(fileUrl, title) {
        const modal = document.getElementById('rscModal');
        const modalTitle = document.getElementById('rscModalTitle');
        const modalBody = document.getElementById('rscModalBody');
        
        modalTitle.innerText = title;
        modalBody.innerHTML = ''; 
        
        // Memisahkan ekstensi file secara dinamis
        const extension = fileUrl.split('.').pop().toLowerCase();
        
        if (extension === 'pdf') {
            // Embed dokumen jika tipe PDF
            modalBody.innerHTML = `<iframe src="${fileUrl}" class="rsc-modal-iframe"></iframe>`;
        } else if (['jpg', 'jpeg', 'png'].includes(extension)) {
            // Render gambar jika jenis berkas gambar umum
            modalBody.innerHTML = `<img src="${fileUrl}" class="rsc-modal-preview" alt="${title}">`;
        } else {
            // Opsi fallback jika format di luar prediksi sistem pratinjau
            modalBody.innerHTML = `<div style="text-align:center; padding: 20px; color: var(--rsc-muted);">
                <p>Ekstensi dokumen tidak didukung pratinjau instan.</p>
                <a href="${fileUrl}" class="btn-ghost" target="_blank" style="display:inline-flex;">Unduh Berkas Langsung</a>
            </div>`;
        }
        
        // Jalankan animasi kemunculan modal
        modal.classList.add('show');
        document.body.style.overflow = 'hidden'; 
    }

    function closeRscModal(force = false) {
        if (force === true || event.target.id === 'rscModal') {
            const modal = document.getElementById('rscModal');
            modal.classList.remove('show');
            document.body.style.overflow = ''; 
            
            // Mengosongkan data frame setelah animasi menghilang (200ms)
            setTimeout(() => {
                document.getElementById('rscModalBody').innerHTML = '';
            }, 200);
        }
    }

    // Menutup modal otomatis via tombol escape keyboard
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRscModal(true);
        }
    });
</script>

@endsection