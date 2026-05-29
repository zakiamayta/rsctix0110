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
        $user = auth('user')->user();

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
}