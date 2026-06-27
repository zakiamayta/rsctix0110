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

        return view('eo.merch.index', compact('products', 'events'));
    }

    public function store(Request $request)
{
    try {

        $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',

            'varians' => 'required|array|min:1',
            'varians.*.varian' => 'required|string',

            'varians.*.image' => 'nullable|image|max:4096',

            'varians.*.ukurans' => 'required|array|min:1',
            'varians.*.ukurans.*.ukuran' => 'required|string',
            'varians.*.ukurans.*.harga' => 'required|numeric',
            'varians.*.ukurans.*.stok' => 'required|numeric',
        ]);

        DB::beginTransaction();

        // 1. PRODUCT
        $product = Product::create([
            'event_id' => $request->event_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // 2. VARIANS
        foreach ($request->varians as $varian) {

            $productVarian = ProductVarian::create([
                'product_id' => $product->id,
                'varian' => $varian['varian'],
            ]);

            // 3. IMAGE FIX (PAKAI PUBLIC MOVE)
            if (!empty($varian['image'])) {

                $file = $varian['image'];
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();

                $file->move(public_path('images/merch'), $filename);

                Image::create([
                    'product_varian_id' => $productVarian->id,
                    'url' => 'images/merch/' . $filename,
                ]);
            }

            // 4. UKURAN (WAJIB include varian_id)
            foreach ($varian['ukurans'] as $ukuran) {

                ProductUkuran::create([
                    'varian_id' => $productVarian->id, // 🔥 FIX UTAMA
                    'ukuran' => $ukuran['ukuran'],
                    'harga' => $ukuran['harga'],
                    'stok' => $ukuran['stok'],
                ]);
            }
        }

        DB::commit();

        Log::info('MERCH_INSERT_SUCCESS', [
            'product_id' => $product->id
        ]);

        return redirect()->route('eo.merch.index')
            ->with('success', 'Merch berhasil ditambahkan');

    } catch (\Throwable $e) {

        DB::rollBack();

        Log::error('MERCH_INSERT_FAILED', [
            'message' => $e->getMessage()
        ]);

        return back()->with('error', 'Gagal tambah merch');
    }
}

    public function destroy($id)
    {
        try {
            Product::findOrFail($id)->delete();

            Log::info('MERCH_DELETE_SUCCESS', ['product_id' => $id]);

            return back()->with('success', 'Berhasil dihapus');

        } catch (\Throwable $e) {

            Log::error('MERCH_DELETE_FAILED', [
                'message' => $e->getMessage()
            ]);

            return back()->with('error', 'Gagal hapus');
        }
    }
    public function edit($id)
{
    $user = auth()->user();

    $eo = DB::table('eo')
        ->where('user_id', $user->id)
        ->first();

    $eventIds = Event::where('eo_id', $eo->id)
        ->pluck('id');

    $product = Product::with([
        'event',
        'varians.images',
        'varians.ukurans'
    ])
    ->whereIn('event_id', $eventIds)
    ->findOrFail($id);

    $events = Event::whereIn('id', $eventIds)->get();

    return view(
        'eo.merch.edit',
        compact('product', 'events')
    );
}

public function update(Request $request, $id)
{
    try {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',

            'varians' => 'required|array|min:1',
            'varians.*.varian' => 'required|string',
            'varians.*.image' => 'nullable|image|max:4096',

            'varians.*.ukurans' => 'required|array|min:1',
            'varians.*.ukurans.*.ukuran' => 'required|string',
            'varians.*.ukurans.*.harga' => 'required|numeric',
            'varians.*.ukurans.*.stok' => 'required|numeric',
        ]);

        DB::beginTransaction();

        $product = Product::findOrFail($id);
        $product->update([
            'event_id' => $request->event_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        $keptVarianIds = [];

        foreach ($request->varians as $vKey => $varianData) {
            // 1. Cek apakah varian ini adalah varian lama atau baru
            $vId = $varianData['id'] ?? null;
            $productVarian = $vId ? ProductVarian::find($vId) : null;

            if ($productVarian) {
                $productVarian->update([
                    'varian' => $varianData['varian']
                ]);
            } else {
                $productVarian = ProductVarian::create([
                    'product_id' => $product->id,
                    'varian' => $varianData['varian']
                ]);
            }

            $keptVarianIds[] = $productVarian->id;

            // 2. Handle Image (Hanya diproses jika user upload gambar baru)
            if (!empty($varianData['image'])) {
                // Hapus gambar lama dari storage & DB jika ada
                foreach ($productVarian->images as $oldImage) {
                    $oldPath = public_path($oldImage->url);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                    $oldImage->delete();
                }

                // Upload gambar baru
                $file = $varianData['image'];
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('images/merch'), $filename);

                Image::create([
                    'product_varian_id' => $productVarian->id,
                    'url' => 'images/merch/' . $filename,
                ]);
            }

            // 3. Handle Ukuran & Stok
            $keptUkuranIds = [];
            if (isset($varianData['ukurans']) && is_array($varianData['ukurans'])) {
                foreach ($varianData['ukurans'] as $uKey => $ukuranData) {
                    $uId = $ukuranData['id'] ?? null;
                    $productUkuran = $uId ? ProductUkuran::find($uId) : null;

                    if ($productUkuran) {
                        $productUkuran->update([
                            'ukuran' => $ukuranData['ukuran'],
                            'harga' => $ukuranData['harga'],
                            'stok' => $ukuranData['stok'],
                        ]);
                    } else {
                        $productUkuran = ProductUkuran::create([
                            'varian_id' => $productVarian->id,
                            'ukuran' => $ukuranData['ukuran'],
                            'harga' => $ukuranData['harga'],
                            'stok' => $ukuranData['stok'],
                        ]);
                    }
                    $keptUkuranIds[] = $productUkuran->id;
                }
            }

            // Hapus ukuran yang di-delete oleh user di UI untuk varian ini
            ProductUkuran::where('varian_id', $productVarian->id)
                ->whereNotIn('id', $keptUkuranIds)
                ->delete();
        }

        // 4. Hapus varian lama dari DB & Storage yang sudah tidak ada di form request
        $variantsToDelete = ProductVarian::where('product_id', $product->id)
            ->whereNotIn('id', $keptVarianIds)
            ->get();

        foreach ($variantsToDelete as $oldVarian) {
            // Hapus file gambarnya
            foreach ($oldVarian->images as $img) {
                $p = public_path($img->url);
                if (file_exists($p)) {
                    unlink($p);
                }
                $img->delete();
            }
            // Hapus ukuran & variannya
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

        return back()->withInput()->with('error', 'Gagal update merchandise');
    }
}
}