@extends('layouts.eo')

@section('title', 'Edit Merchandise')

@section('content')

{{-- Style tetap sama seperti sebelumnya --}}
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
  .page-header h2 { font-family: 'Sora', sans-serif; font-size: 1.5rem; font-weight: 800; margin: 0; }
  .accent-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--rsc-accent); margin-right: 7px; }
  .section-title { font-family: 'Sora', sans-serif; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: var(--rsc-accent); margin: 20px 0 16px; display: flex; align-items: center; gap: 8px; }
  .section-title::after { content: ''; flex: 1; height: 1px; background: linear-gradient(to right, var(--rsc-border), transparent); }
  .btn-primary { display: inline-flex; align-items: center; gap: 7px; background: var(--rsc-accent); color: #fff; border: none; border-radius: 10px; padding: 10px 20px; font-size: .82rem; font-weight: 700; cursor: pointer; text-decoration: none; }
  .btn-ghost { display: inline-flex; align-items: center; gap: 7px; background: var(--rsc-surface2); color: var(--rsc-muted); border: 1px solid var(--rsc-border); border-radius: 10px; padding: 10px 18px; font-size: .82rem; font-weight: 700; text-decoration: none; }
  .btn-sm { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 700; padding: 6px 13px; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; }
  .btn-sm-danger { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
  .btn-sm-accent { background: var(--rsc-accent-dim); color: var(--rsc-accent); border: 1px solid rgba(232,71,10,.2); }
  .rsc-card { background: var(--rsc-surface); border: 1px solid var(--rsc-border); border-radius: var(--radius); padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); }
  .field-group { display: flex; flex-direction: column; gap: 5px; }
  .field-group label { font-size: .72rem; font-weight: 700; color: var(--rsc-muted); text-transform: uppercase; }
  .rsc-input { background: var(--rsc-surface2); border: 1px solid var(--rsc-border); border-radius: 9px; color: var(--rsc-text); padding: 9px 12px; font-size: .85rem; width: 100%; outline: none; }
  .rsc-input:focus { border-color: var(--rsc-accent); background: #fff; }
  .variant-card { background: var(--rsc-surface2); border: 1px solid var(--rsc-border); border-radius: 12px; padding: 16px; margin-bottom: 16px; }
  .variant-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
  .variant-label { font-family: 'Sora', sans-serif; font-size: .7rem; font-weight: 700; color: var(--rsc-accent); text-transform: uppercase; }
  .size-box { background: var(--rsc-surface); border: 1px solid var(--rsc-border); border-radius: 10px; padding: 14px; margin-top: 12px; }
  .size-box-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
  .size-box-label { font-size: .68rem; font-weight: 700; color: var(--rsc-muted); text-transform: uppercase; }
  .size-row { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 8px; margin-bottom: 8px; align-items: end; }
  .file-zone { border: 1.5px dashed var(--rsc-border); border-radius: 9px; padding: 14px 12px; background: var(--rsc-surface); text-align: center; position: relative; }
  .file-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
  .file-zone-text { font-size: .75rem; color: var(--rsc-muted); }
  .current-img-preview { display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 0.75rem; color: var(--rsc-muted); }
  .current-img-preview img { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid var(--rsc-border); }
  .field-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .span2 { grid-column: span 2; }
</style>

<div class="rsc-wrap">

  <div class="page-header">
    <div>
      <h2><span class="accent-dot"></span>Edit Merchandise</h2>
      <p>Ubah informasi produk, varian, harga, beserta stok untuk event kamu</p>
    </div>
    <a href="{{ route('eo.merch.index') }}" class="btn-ghost">Kembali</a>
  </div>

  <div class="rsc-card">
    <form action="{{ route('eo.merch.update', $product->id) }}" method="POST" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      <div class="section-title">Info Produk</div>
      <div class="field-grid-2" style="margin-bottom:24px;">
        <div class="field-group">
          <label>Event</label>
          <select name="event_id" class="rsc-input" required>
            <option value="">Pilih Event</option>
            @foreach($events as $event)
              <option value="{{ $event->id }}" {{ $product->event_id == $event->id ? 'selected' : '' }}>
                {{ $event->title }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="field-group">
          <label>Nama Produk</label>
          <input type="text" name="name" class="rsc-input" value="{{ old('name', $product->name) }}" required>
        </div>

        <div class="field-group span2">
          <label>Deskripsi</label>
          <textarea name="description" class="rsc-input">{{ old('description', $product->description) }}</textarea>
        </div>
      </div>

      <div class="section-title">Varian Produk</div>
      <div id="variant-wrapper">
        @foreach($product->varians as $vIdx => $varian)
          {{-- Gunakan ID asli database sebagai key array agar konsisten --}}
          <div class="variant-card" data-id="{{ $varian->id }}">
            <input type="hidden" name="varians[{{ $varian->id }}][id]" value="{{ $varian->id }}">
            
            <div class="variant-card-header">
              <span class="variant-label">Varian #{{ $vIdx + 1 }}</span>
              <button type="button" class="btn-sm btn-sm-danger" onclick="this.closest('.variant-card').remove()">✕ Hapus Varian</button>
            </div>

            <div class="field-grid-2" style="margin-bottom:12px;">
              <div class="field-group">
                <label>Nama Varian</label>
                <input type="text" name="varians[{{ $varian->id }}][varian]" class="rsc-input" value="{{ $varian->varian}}" required>
              </div>

              <div class="field-group">
                <label>Gambar Varian (Abaikan jika tidak ingin diganti)</label>
                <div class="file-zone">
                  <input type="file" name="varians[{{ $varian->id }}][image]" accept="image/*">
                  <div class="file-zone-text"><span>Pilih file baru jika ingin mengganti</span></div>
                </div>
                @if($varian->images->first())
                  <div class="current-img-preview">
                    <img src="{{ asset($varian->images->first()->url) }}">
                    <span>Gambar aktif tersimpan</span>
                  </div>
                @endif
              </div>
            </div>

            <div class="size-box">
              <div class="size-box-header">
                <span class="size-box-label">Ukuran & Harga</span>
                <button type="button" class="btn-sm btn-sm-accent" onclick="addUkuran('{{ $varian->id }}')">+ Ukuran</button>
              </div>
              <div id="ukuran-wrapper-{{ $varian->id }}">
                @foreach($varian->ukurans as $ukuran)
                  <div class="size-row">
                    <input type="hidden" name="varians[{{ $varian->id }}][ukurans][{{ $ukuran->id }}][id]" value="{{ $ukuran->id }}">
                    <div class="field-group">
                      <label>Ukuran</label>
                      <input type="text" name="varians[{{ $varian->id }}][ukurans][{{ $ukuran->id }}][ukuran]" class="rsc-input" value="{{ $ukuran->ukuran }}" required>
                    </div>
                    <div class="field-group">
                      <label>Harga</label>
                      <input type="number" name="varians[{{ $varian->id }}][ukurans][{{ $ukuran->id }}][harga]" class="rsc-input" value="{{ $ukuran->harga }}" required>
                    </div>
                    <div class="field-group">
                      <label>Stok</label>
                      <input type="number" name="varians[{{ $varian->id }}][ukurans][{{ $ukuran->id }}][stok]" class="rsc-input" value="{{ $ukuran->stok }}" required>
                    </div>
                    <button type="button" class="btn-sm btn-sm-danger" style="margin-top:22px; height:38px;" onclick="this.closest('.size-row').remove()">✕</button>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; border-top: 1px solid var(--rsc-border); padding-top: 20px;">
        <button type="button" class="btn-sm btn-sm-accent" onclick="addVariant()">+ Tambah Varian Baru</button>
        <button type="submit" class="btn-primary">Update Merchandise</button>
      </div>
    </form>
  </div>
</div>

<script>
  function addVariant() {
    const wrapper = document.getElementById('variant-wrapper');
    // Menggunakan timestamp unik agar tidak bentrok dengan ID database asli
    const vIdx = 'new_' + Date.now();

    const html = `
    <div class="variant-card">
      <div class="variant-card-header">
        <span class="variant-label">Varian Baru</span>
        <button type="button" class="btn-sm btn-sm-danger" onclick="this.closest('.variant-card').remove()">✕ Hapus Varian</button>
      </div>
      <div class="field-grid-2" style="margin-bottom:12px;">
        <div class="field-group">
          <label>Nama Varian</label>
          <input type="text" name="varians[${vIdx}][varian]" class="rsc-input" placeholder="Misal: Hitam" required>
        </div>
        <div class="field-group">
          <label>Gambar Varian</label>
          <div class="file-zone">
            <input type="file" name="varians[${vIdx}][image]" accept="image/*" required>
            <div class="file-zone-text"><span>Pilih gambar</span></div>
          </div>
        </div>
      </div>
      <div class="size-box">
        <div class="size-box-header">
          <span class="size-box-label">Ukuran & Harga</span>
          <button type="button" class="btn-sm btn-sm-accent" onclick="addUkuran('${vIdx}')">+ Ukuran</button>
        </div>
        <div id="ukuran-wrapper-${vIdx}"></div>
      </div>
    </div>`;

    wrapper.insertAdjacentHTML('beforeend', html);
    addUkuran(vIdx);
  }

  function addUkuran(vIdx) {
    const wrapper = document.getElementById(`ukuran-wrapper-${vIdx}`);
    const uIdx = 'new_' + Date.now() + Math.floor(Math.random() * 100);

    const html = `
    <div class="size-row">
      <div class="field-group">
        <label>Ukuran</label>
        <input type="text" name="varians[${vIdx}][ukurans][${uIdx}][ukuran]" class="rsc-input" placeholder="XL / L / All Size" required>
      </div>
      <div class="field-group">
        <label>Harga</label>
        <input type="number" name="varians[${vIdx}][ukurans][${uIdx}][harga]" class="rsc-input" placeholder="0" required>
      </div>
      <div class="field-group">
        <label>Stok</label>
        <input type="number" name="varians[${vIdx}][ukurans][${uIdx}][stok]" class="rsc-input" placeholder="0" required>
      </div>
      <button type="button" class="btn-sm btn-sm-danger" style="margin-top:22px; height:38px;" onclick="this.closest('.size-row').remove()">✕</button>
    </div>`;

    wrapper.insertAdjacentHTML('beforeend', html);
  }
</script>

@endsection