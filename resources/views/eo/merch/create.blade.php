@extends('layouts.eo')

@section('title', 'Tambah Merchandise Baru')

@section('content')

<style>
  @import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500&display=swap');
  :root {
    --rsc-bg: #F7F4F1; --rsc-surface: #FFFFFF; --rsc-surface2: #F2EEE9;
    --rsc-border: #E2DBD4; --rsc-accent: #f97316; --rsc-accent-dim: rgba(232,71,10,0.08);
    --rsc-text: #1A1208; --rsc-muted: #8A7E76; --radius: 14px;
  }
  .rsc-wrap * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
  .rsc-wrap { background: var(--rsc-bg); min-height: 100vh; padding: 28px 24px 60px; color: var(--rsc-text); }
  .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; }
  .page-header h2 { font-family: 'Sora', sans-serif; font-weight: 800; font-size: 1.8rem; margin: 0; color: var(--rsc-text); }
  
  .rsc-card { background: var(--rsc-surface); border: 1px solid var(--rsc-border); border-radius: var(--radius); padding: 24px; margin-bottom: 24px; }
  .card-title { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 1.1rem; margin-bottom: 20px; color: var(--rsc-text); display: flex; justify-content: space-between; align-items: center; }
  
  .field-group { margin-bottom: 18px; display: flex; flex-col: column; gap: 6px; width: 100%; }
  .field-group label { font-weight: 600; font-size: 0.88rem; color: var(--rsc-text); }
  .rsc-input, .rsc-select, .rsc-textarea { width: 100%; padding: 11px 14px; border: 1px solid var(--rsc-border); background: var(--rsc-surface); border-radius: 10px; font-size: 0.92rem; color: var(--rsc-text); transition: all 0.2s; }
  .rsc-input:focus, .rsc-select:focus, .rsc-textarea:focus { border-color: var(--rsc-accent); outline: none; box-shadow: 0 0 0 3px var(--rsc-accent-dim); }
  
  .btn-rsc { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 0.9rem; padding: 12px 24px; border-radius: 10px; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
  .btn-rsc-primary { background: var(--rsc-accent); color: #FFF; }
  .btn-rsc-primary:hover { background: #d03f08; }
  .btn-rsc-secondary { background: var(--rsc-surface2); color: var(--rsc-text); border: 1px solid var(--rsc-border); }
  .btn-rsc-secondary:hover { background: #e6e0d8; }
  .btn-sm { font-size: 0.78rem; padding: 6px 12px; border-radius: 6px; font-weight: 600; border: none; cursor: pointer; }
  .btn-sm-accent { background: var(--rsc-accent-dim); color: var(--rsc-accent); }
  .btn-sm-danger { background: #FEE2E2; color: #DC2626; }

  .variant-item { border: 1px solid var(--rsc-border); border-radius: 10px; padding: 18px; background: var(--rsc-bg); margin-bottom: 16px; }
  .size-box { background: var(--rsc-surface); border: 1px solid var(--rsc-border); border-radius: 8px; padding: 14px; margin-top: 14px; }
  .size-box-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-b: 1px dashed var(--rsc-border); padding-bottom: 6px; }
  .size-box-label { font-size: 0.82rem; font-weight: 600; color: var(--rsc-muted); }
  .size-row { display: grid; grid-template-columns: 2fr 2fr 1fr auto; gap: 12px; align-items: flex-end; margin-bottom: 8px; }
</style>

<div class="rsc-wrap">
  <div class="page-header">
    <div>
      <h2>Tambah Merchandise Baru</h2>
      <p style="color:var(--rsc-muted); margin:4px 0 0; font-size:.88rem;">Formulir pembuatan komoditas produk merchandise atau item event</p>
    </div>
    <a href="{{ route('eo.merch.index') }}" class="btn-rsc btn-rsc-secondary">Kembali</a>
  </div>

  @if(session('error'))
    <div style="background:#FEE2E2; border:1px solid #FCA5A5; color:#B91C1C; padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:.88rem;">
      ⚠️ {{ session('error') }}
    </div>
  @endif

  <form action="{{ route('eo.merch.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="rsc-card">
      <div class="card-title">Informasi Utama Produk</div>
      
      <div class="field-group">
        <label>Hubungkan ke Event Anda</label>
        <select name="event_id" class="rsc-select" required>
          <option value="" disabled selected>-- Pilih Event Terkait --</option>
          @foreach($events as $e)
            <option value="{{ $e->id }}" {{ old('event_id') == $e->id ? 'selected' : '' }}>{{ $e->title }}</option>
          @endforeach
        </select>
      </div>

      <div class="field-group">
        <label>Nama Merchandise</label>
        <input type="text" name="name" class="rsc-input" placeholder="Contoh: Kaos Official Concert, Tote Bag, Hoodie" value="{{ old('name') }}" required>
      </div>

      <div class="field-group">
        <label>Deskripsi Produk</label>
        <textarea name="description" rows="4" class="rsc-textarea" placeholder="Tuliskan spesifikasi bahan, detail cetak, atau ukuran produk secara umum...">{{ old('description') }}</textarea>
      </div>
    </div>

    <div class="rsc-card">
      <div class="card-title">
        <span>Pengaturan Varian, Ukuran, & Stok</span>
        <button type="button" class="btn-sm btn-sm-accent" onclick="addVarian()">+ Tambah Varian</button>
      </div>
      
      <div id="variants-container">
        </div>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px;">
      <a href="{{ route('eo.merch.index') }}" class="btn-rsc btn-rsc-secondary">Batalkan</a>
      <button type="submit" class="btn-rsc btn-rsc-primary">Simpan Produk Baru</button>
    </div>
  </form>
</div>

<script>
  let varianCounter = 0;

  // Jalankan otomatis 1 varian pertama saat halaman dimuat
  document.addEventListener('DOMContentLoaded', function() {
    addVarian();
  });

  function addVarian() {
    const container = document.getElementById('variants-container');
    const vIdx = 'new_' + varianCounter++;

    const html = `
    <div class="variant-item" id="variant-card-${vIdx}">
      <div style="display:flex; justify-content:between; align-items:center; margin-bottom:14px;">
        <span style="font-weight:700; font-size:.9rem; color:var(--rsc-accent);">Opsi Varian</span>
        <button type="button" class="btn-sm btn-sm-danger" onclick="removeVarian('${vIdx}')">Hapus Varian</button>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
        <div class="field-group">
          <label>Nama Varian (Warna / Model)</label>
          <input type="text" name="varians[${vIdx}][varian]" class="rsc-input" placeholder="Contoh: Hitam, Putih, Lengan Panjang" required>
        </div>
        <div class="field-group">
          <label>Unggah Foto Varian</label>
          <input type="file" name="varians[${vIdx}][image]" class="rsc-input" accept="image/*" required>
        </div>
      </div>

      <div class="size-box">
        <div class="size-box-header">
          <span class="size-box-label">Dimensi Ukuran, Harga & Ketersediaan Stok</span>
          <button type="button" class="btn-sm btn-sm-accent" onclick="addUkuran('${vIdx}')">+ Tambah Ukuran</button>
        </div>
        <div id="ukuran-wrapper-${vIdx}"></div>
      </div>
    </div>`;

    container.insertAdjacentHTML('beforeend', html);
    addUkuran(vIdx); // Setiap buat varian baru, otomatis kasih 1 baris ukuran default
  }

  function removeVarian(vIdx) {
    const card = document.getElementById(`variant-card-${vIdx}`);
    const container = document.getElementById('variants-container');
    
    if (container.children.length > 1) {
      card.remove();
    } else {
      alert('Minimal harus memiliki satu opsi varian produk!');
    }
  }

  function addUkuran(vIdx) {
    const wrapper = document.getElementById(`ukuran-wrapper-${vIdx}`);
    const uIdx = 'new_' + Date.now() + Math.floor(Math.random() * 100);

    const html = `
    <div class="size-row" id="size-row-${uIdx}">
      <div class="field-group">
        <label style="font-size:.78rem; color:var(--rsc-muted);">Ukuran</label>
        <input type="text" name="varians[${vIdx}][ukurans][${uIdx}][ukuran]" class="rsc-input" placeholder="XL / L / S / All Size" required>
      </div>
      <div class="field-group">
        <label style="font-size:.78rem; color:var(--rsc-muted);">Harga Jual (Rp)</label>
        <input type="number" name="varians[${vIdx}][ukurans][${uIdx}][harga]" class="rsc-input" placeholder="0" required>
      </div>
      <div class="field-group">
        <label style="font-size:.78rem; color:var(--rsc-muted);">Stok</label>
        <input type="number" name="varians[${vIdx}][ukurans][${uIdx}][stok]" class="rsc-input" placeholder="0" required>
      </div>
      <button type="button" class="btn-sm btn-sm-danger" style="margin-bottom:6px;" onclick="removeUkuran('${uIdx}', '${vIdx}')">×</button>
    </div>`;

    wrapper.insertAdjacentHTML('beforeend', html);
  }

  function removeUkuran(uIdx, vIdx) {
    const row = document.getElementById(`size-row-${uIdx}`);
    const wrapper = document.getElementById(`ukuran-wrapper-${vIdx}`);
    
    if (wrapper.children.length > 1) {
      row.remove();
    } else {
      alert('Setiap varian wajib memiliki minimal satu spesifikasi ukuran & stok!');
    }
  }
</script>

@endsection