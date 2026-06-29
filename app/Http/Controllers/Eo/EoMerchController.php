<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\Product;
use App\Models\ProductVarian;
use App\Models\ProductUkuran;
use App\Models\Image;
use App\Models\Event;

class EoMerchController extends Controller
{
    /**
     * Tampilkan List Utama Data Merchandise EO
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $eo = DB::table('eo')->where('user_id', $user->id)->first();

        $eventIds = Event::where('eo_id', $eo->id)->pluck('id');

        $events = Event::whereIn('id', $eventIds)->get();

        $products = Product::with([
                'event',
                'varians.images',
                'varians.ukurans'
            ])
            ->whereIn('event_id', $eventIds)
            ->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->latest()
            ->get();

        return view('eo.merch.index', compact('events', 'products'));
    }

    /**
     * 🆕 Tampilkan Form Halaman Tambah Merchandise Baru
     */
    public function create()
    {
        $user = auth()->user();
        $eo = DB::table('eo')->where('user_id', $user->id)->first();
        
        // Ambil daftar event aktif milik EO ini untuk dihubungkan ke merchandise
        $events = Event::where('eo_id', $eo->id)->get();

        return view('eo.merch.create', compact('events'));
    }

    /**
     * Eksekusi Simpan Data Merchandise Baru (Form Page)
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'varians' => 'required|array|min:1',
            'varians.*.varian' => 'required|string|max:100',
            'varians.*.image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'varians.*.ukurans' => 'required|array|min:1',
            'varians.*.ukurans.*.ukuran' => 'required|string|max:50',
            'varians.*.ukurans.*.harga' => 'required|numeric|min:0',
            'varians.*.ukurans.*.stok' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // 1. Simpan Master Product
            $product = new Product();
            $product->type = 'merch';
            $product->name = $request->name;
            $product->description = $request->description;
            $product->event_id = $request->event_id;
            $product->save();

            // 2. Loop entitas Varian, Foto, dan Ukuran detailnya
            foreach ($request->varians as $vData) {
                $productVarian = new ProductVarian();
                $productVarian->product_id = $product->id;
                $productVarian->varian = $vData['varian'];
                $productVarian->save();

                // Upload Image Varian Produk
                if (isset($vData['image']) && $vData['image']->isValid()) {
                    $file = $vData['image'];
                    $filename = 'merch_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/merch'), $filename);
                    $dbUrl = 'uploads/merch/' . $filename;

                    $imageModel = new Image();
                    $imageModel->product_varian_id = $productVarian->id;
                    $imageModel->url = $dbUrl;
                    $imageModel->save();
                }

                // Loop detail dimensi ukuran & stok barang
                if (isset($vData['ukurans']) && !empty($vData['ukurans'])) {
                    foreach ($vData['ukurans'] as $uData) {
                        $productUkuran = new ProductUkuran();
                        
                        // 🔥 FIX ERROR 1364: Masukkan event_id induk ke baris produk ukuran
                        $productUkuran->event_id = $product->event_id; 
                        
                        $productUkuran->varian_id = $productVarian->id;
                        $productUkuran->ukuran = $uData['ukuran'];
                        $productUkuran->harga = $uData['harga'];
                        $productUkuran->stok = $uData['stok'];
                        $productUkuran->save();
                    }
                }
            }

            DB::commit();

            // Sesuai instruksi, dialihkan kembali ke daftar index utama
            return redirect()->route('eo.merch.index')
                ->with('success', 'Merchandise baru berhasil ditambahkan dengan aman');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('MERCH_STORE_FAILED', [
                'message' => $e->getMessage()
            ]);
            return redirect()->back()
                ->with('error', 'Gagal menambahkan merchandise: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Tampilkan Form Halaman Edit Merchandise
     */
    public function edit($id)
    {
        $user = auth()->user();
        $eo = DB::table('eo')->where('user_id', $user->id)->first();
        
        $events = Event::where('eo_id', $eo->id)->get();

        $product = Product::with([
                'event',
                'varians.images',
                'varians.ukurans'
            ])
            ->whereIn('event_id', $events->pluck('id'))
            ->findOrFail($id);

        return view('eo.merch.edit', compact('events', 'product'));
    }

    /**
     * Eksekusi Perubahan Update Data Merchandise (Form Page)
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $eo = DB::table('eo')->where('user_id', $user->id)->first();
        $eventIds = Event::where('eo_id', $eo->id)->pluck('id');

        $product = Product::whereIn('event_id', $eventIds)->findOrFail($id);

        $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'varians' => 'required|array|min:1',
            'varians.*.varian' => 'required|string|max:100',
            'varians.*.image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'varians.*.ukurans' => 'required|array|min:1',
            'varians.*.ukurans.*.ukuran' => 'required|string|max:50',
            'varians.*.ukurans.*.harga' => 'required|numeric|min:0',
            'varians.*.ukurans.*.stok' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // 1. Update data dasar produk
            $product->name = $request->name;
            $product->description = $request->description;
            $product->event_id = $request->event_id;
            $product->save();

            $keptVarianIds = [];

            // 2. Loop varian dari form request
            foreach ($request->varians as $vKey => $vData) {
                // Deteksi apakah varian lama atau varian baru dibuat di UI
                if (str_starts_with($vKey, 'new_')) {
                    $productVarian = new ProductVarian();
                    $productVarian->product_id = $product->id;
                } else {
                    $productVarian = ProductVarian::where('product_id', $product->id)->findOrFail($vKey);
                }

                $productVarian->varian = $vData['varian'];
                $productVarian->save();
                $keptVarianIds[] = $productVarian->id;

                // Handle Upload File Gambar Varian jika ada
                if (isset($vData['image']) && $vData['image']->isValid()) {
                    // Hapus file foto lama di folder public jika tipe edit varian lama
                    if (!str_starts_with($vKey, 'new_')) {
                        foreach ($productVarian->images as $oldImg) {
                            $oldPath = public_path($oldImg->url);
                            if (file_exists($oldPath)) {
                                @unlink($oldPath);
                            }
                            $oldImg->delete();
                        }
                    }

                    $file = $vData['image'];
                    $filename = 'merch_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/merch'), $filename);
                    $dbUrl = 'uploads/merch/' . $filename;

                    $imageModel = new Image();
                    $imageModel->product_varian_id = $productVarian->id;
                    $imageModel->url = $dbUrl;
                    $imageModel->save();
                }

                // 3. Proses dimensi ukuran & stok dalam varian ini
                $keptUkuranIds = [];
                if (isset($vData['ukurans']) && is_array($vData['ukurans'])) {
                    foreach ($vData['ukurans'] as $uKey => $uData) {
                        if (str_starts_with($uKey, 'new_')) {
                            $productUkuran = new ProductUkuran();
                            $productUkuran->varian_id = $productVarian->id;
                        } else {
                            $productUkuran = ProductUkuran::where('varian_id', $productVarian->id)->findOrFail($uKey);
                        }

                        // 🔥 Sinkronkan event_id terbaru ke setiap item ukuran produk
                        $productUkuran->event_id = $product->event_id;
                        $productUkuran->ukuran = $uData['ukuran'];
                        $productUkuran->harga = $uData['harga'];
                        $productUkuran->stok = $uData['stok'];
                        $productUkuran->save();

                        $keptUkuranIds[] = $productUkuran->id;
                    }
                }

                // Hapus baris ukuran yang di-delete oleh user di antrean form UI
                ProductUkuran::where('varian_id', $productVarian->id)
                    ->whereNotIn('id', $keptUkuranIds)
                    ->delete();
            }

            // 4. Hapus total varian lama beserta fotonya yang dihilangkan oleh user di form UI
            $variantsToDelete = ProductVarian::where('product_id', $product->id)
                ->whereNotIn('id', $keptVarianIds)
                ->get();

            foreach ($variantsToDelete as $oldVarian) {
                foreach ($oldVarian->images as $img) {
                    $p = public_path($img->url);
                    if (file_exists($p)) {
                        @unlink($p);
                    }
                    $img->delete();
                }
                $oldVarian->ukurans()->delete();
                $oldVarian->delete();
            }

            DB::commit();

            return redirect()->route('eo.merch.index')
                ->with('success', 'Merchandise berhasil diperbarui dengan aman');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('MERCH_UPDATE_FAILED', [
                'message' => $e->getMessage()
            ]);
            return redirect()->back()
                ->with('error', 'Gagal memperbarui data merchandise: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Hapus Data Merchandise beserta Seluruh Varian, Ukuran, & Berkas Gambar
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $eo = DB::table('eo')->where('user_id', $user->id)->first();
        $eventIds = Event::where('eo_id', $eo->id)->pluck('id');

        $product = Product::whereIn('event_id', $eventIds)->findOrFail($id);

        DB::beginTransaction();
        try {
            foreach ($product->varians as $varian) {
                // Hapus fisik berkas foto dari penyimpanan lokal public
                foreach ($varian->images as $img) {
                    $filePath = public_path($img->url);
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                    $img->delete();
                }
                // Hapus data ukuran terkait varian ini
                $varian->ukurans()->delete();
                $varian->delete();
            }

            $product->delete();
            DB::commit();

            return redirect()->route('eo.merch.index')
                ->with('success', 'Produk merchandise berhasil dihapus permanen dari sistem');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('MERCH_DESTROY_FAILED', [
                'message' => $e->getMessage()
            ]);
            return redirect()->back()
                ->with('error', 'Gagal menghapus produk merchandise: ' . $e->getMessage());
        }
    }
}