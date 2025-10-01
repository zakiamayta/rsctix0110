<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Event;
use App\Models\ProductVarian;
use App\Models\ProductUkuran;
use Illuminate\Support\Facades\DB;

class AdminMerchController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['event','varians.ukurans'])
            ->where('type','merch');

        if ($request->filled('event_id')) {
            $query->where('event_id',$request->event_id);
        }

        if ($request->filled('search')) {
            $query->where('name','like','%'.$request->search.'%');
        }

        $products = $query->latest()->get();
        $events   = Event::all();

        return view('admin.admin-merch', compact('products','events'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'varians' => 'required|array|min:1',
            'varians.*.varian' => 'required|string',
            'varians.*.image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'varians.*.ukurans' => 'required|array|min:1',
            'varians.*.ukurans.*.ukuran' => 'required|string',
            'varians.*.ukurans.*.harga' => 'required|numeric',
            'varians.*.ukurans.*.stok' => 'required|integer',
        ]);

        DB::transaction(function() use ($request) {
            $product = Product::create([
                'event_id' => $request->event_id,
                'type' => 'merch',
                'name' => $request->name,
                'description' => $request->description,
            ]);

            foreach ($request->varians as $varianInput) {
                $varianData = [
                    'event_id' => $request->event_id,
                    'varian' => $varianInput['varian'],
                    'product_id' => $product->id,
                ];

                if (isset($varianInput['image'])) {
                    $path = $varianInput['image']->store('merch','public');
                    $varianData['image'] = '/storage/'.$path;
                }

                $varian = ProductVarian::create($varianData);

                foreach ($varianInput['ukurans'] as $ukuranInput) {
                    ProductUkuran::create([
                        'event_id' => $request->event_id,
                        'varian_id' => $varian->id,
                        'ukuran' => $ukuranInput['ukuran'],
                        'harga' => $ukuranInput['harga'],
                        'stok' => $ukuranInput['stok'],
                    ]);
                }
            }
        });

        return redirect()->route('admin.merch.index')->with('success','Produk berhasil ditambahkan');
    }

public function edit($id)
{
    $product = Product::with(['event', 'varians.ukurans'])->findOrFail($id);
    return response()->json($product); // JSON untuk AJAX
}

    public function update(Request $request, $id)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'varians' => 'required|array|min:1',
            'varians.*.varian' => 'required|string',
            'varians.*.image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'varians.*.ukurans' => 'required|array|min:1',
            'varians.*.ukurans.*.ukuran' => 'required|string',
            'varians.*.ukurans.*.harga' => 'required|numeric',
            'varians.*.ukurans.*.stok' => 'required|integer',
        ]);

        DB::transaction(function() use ($request,$id) {
            $product = Product::findOrFail($id);
            $product->update([
                'event_id' => $request->event_id,
                'name' => $request->name,
                'description' => $request->description,
            ]);

            // hapus data lama
            foreach ($product->varians as $v) {
                $v->ukurans()->delete();
            }
            $product->varians()->delete();

            foreach ($request->varians as $varianInput) {
                $varianData = [
                    'event_id' => $request->event_id,
                    'varian' => $varianInput['varian'],
                    'product_id' => $product->id,
                ];

                if (isset($varianInput['image'])) {
                    $path = $varianInput['image']->store('merch','public');
                    $varianData['image'] = '/storage/'.$path;
                }

                $varian = ProductVarian::create($varianData);

                foreach ($varianInput['ukurans'] as $ukuranInput) {
                    ProductUkuran::create([
                        'event_id' => $request->event_id,
                        'varian_id' => $varian->id,
                        'ukuran' => $ukuranInput['ukuran'],
                        'harga' => $ukuranInput['harga'],
                        'stok' => $ukuranInput['stok'],
                    ]);
                }
            }
        });

        return redirect()->route('admin.merch.index')->with('success','Produk berhasil diperbarui');
    }

    public function destroy($id)
    {
        $product = Product::with('varians.ukurans')->findOrFail($id);
        foreach ($product->varians as $v) {
            $v->ukurans()->delete();
        }
        $product->varians()->delete();
        $product->delete();

        return redirect()->route('admin.merch.index')->with('success','Produk berhasil dihapus');
    }

public function show($id)
{
    $product = Product::with(['event', 'varians.ukurans'])->findOrFail($id);
    return response()->json($product);
}
}
