@extends('layouts.admin')

@section('title', 'Kelola Merchandise')

@section('content')
<main class="container mx-auto px-4 py-8 md:px-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Kelola Merchandise</h2>
        <button onclick="openAddModal()" 
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-semibold text-sm transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Produk
        </button>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-md mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
            <div>
                <label for="event_filter" class="block text-sm font-semibold text-gray-700 mb-1">Filter Event</label>
                <select id="event_filter" name="event_id" 
                        class="form-select w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 transition">
                    <option value="">-- Semua Event --</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                            {{ $event->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-semibold text-gray-700 mb-1">Pencarian</label>
                <input type="text" id="search" name="search" placeholder="Cari nama produk..."
                       value="{{ request('search') }}"
                       class="form-input w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 transition"/>
            </div>

            <div class="flex gap-4">
                <button type="submit" 
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-semibold text-sm transition-colors shadow-sm">
                    Filter
                </button>
                <a href="{{ route('admin.merch.index') }}" 
                   class="flex-1 px-4 py-3 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold text-sm transition-colors text-center shadow-sm">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($products as $product)
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1 group">
            <div class="relative w-full h-48 bg-gray-100 overflow-hidden">
                <img src="{{ optional($product->varians->first())->image ?? '/api/placeholder/300/200' }}" 
                     alt="{{ $product->name }}" 
                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
            </div>
            
            <div class="p-6 flex flex-col justify-between h-[220px]">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2 truncate">{{ $product->name }}</h3>
                    <p class="text-sm text-gray-600 line-clamp-3">{{ $product->description }}</p>
                </div>

                <div class="flex gap-2 mt-4">
                    <button onclick="openDetailModal({{ $product->id }})" 
                            class="flex-1 bg-blue-100 text-blue-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-blue-200 transition-colors">
                        Detail
                    </button>

                    <button onclick="openEditModal({{ $product->id }})" 
                            class="flex-1 bg-green-100 text-green-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-green-200 transition-colors">
                        Edit
                    </button>

                    <form method="POST" action="{{ route('admin.merch.destroy', $product->id) }}" onsubmit="return confirm('Yakin ingin menghapus produk ini? Tindakan ini tidak dapat dikembalikan.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="flex-1 bg-red-100 text-red-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-red-200 transition-colors">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-16 text-gray-500 text-lg font-medium">
            Belum ada produk merchandise yang terdaftar.
        </div>
        @endforelse
    </div>
</main>

<div id="productModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4 transition-all duration-300">
    <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl p-8 overflow-y-auto max-h-[90vh] transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
        <div class="flex justify-between items-center mb-6">
            <h3 id="modalTitle" class="text-2xl font-bold text-gray-800">Tambah Produk</h3>
            <button type="button" onclick="closeModal()" class="text-gray-500 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="productForm" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="id" id="productId">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Produk</label>
                    <input type="text" name="name" id="productName" class="form-input w-full rounded-xl border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Event</label>
                    <select name="event_id" id="productEvent" class="form-select w-full rounded-xl border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition" required>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" id="productDescription" rows="4" class="form-textarea w-full rounded-xl border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"></textarea>
            </div>

            <div id="variansContainer" class="space-y-6"></div>
            
            <button type="button" onclick="tambahVarian()" class="w-full bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold px-4 py-3 rounded-xl transition-colors flex items-center justify-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                <span>Tambah Varian</span>
            </button>

            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-medium transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium transition-colors">
                    Simpan Produk
                </button>
            </div>
        </form>
    </div>
</div>

<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4 transition-all duration-300">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl p-8 overflow-y-auto max-h-[90vh] transform transition-all duration-300 scale-95 opacity-0" id="detailModalContent">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-800">Detail Produk</h3>
            <button type="button" onclick="closeDetailModal()" class="text-gray-500 hover:text-gray-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div id="detailContent" class="text-sm text-gray-700 space-y-4">
            </div>
        <div class="flex justify-end mt-6">
            <button type="button" onclick="closeDetailModal()" 
                    class="px-6 py-3 bg-gray-200 hover:bg-gray-300 rounded-xl font-medium transition-colors">
                Tutup
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    let varianIndex = 0;

    function openAddModal() {
        document.getElementById('modalTitle').innerText = "Tambah Produk";
        document.getElementById('productForm').action = "{{ route('admin.merch.store') }}";
        document.getElementById('formMethod').value = "POST";
        document.getElementById('productId').value = "";
        document.getElementById('productName').value = "";
        document.getElementById('productDescription').value = "";
        document.getElementById('productEvent').value = "";
        document.getElementById('variansContainer').innerHTML = '';
        varianIndex = 0;
        tambahVarian();
        document.getElementById('productModal').classList.remove('hidden');
        document.getElementById('modalContent').classList.remove('scale-95', 'opacity-0');
        document.getElementById('modalContent').classList.add('scale-100', 'opacity-100');
    }

    function openEditModal(id) {
        fetch(`/admin/merch/${id}/edit`)
        .then(res => res.json())
        .then(product => {
            document.getElementById('modalTitle').innerText = "Edit Produk";
            document.getElementById('productForm').action = `/admin/merch/${id}`;
            document.getElementById('formMethod').value = "PUT";
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('productDescription').value = product.description ?? '';
            document.getElementById('productEvent').value = product.event_id;

            document.getElementById('variansContainer').innerHTML = '';
            varianIndex = 0;

            product.varians.forEach((v) => {
                tambahVarian(v, v.ukurans);
            });

            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('modalContent').classList.remove('scale-95', 'opacity-0');
            document.getElementById('modalContent').classList.add('scale-100', 'opacity-100');
        });
    }

    function closeModal() {
        document.getElementById('modalContent').classList.remove('scale-100', 'opacity-100');
        document.getElementById('modalContent').classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            document.getElementById('productModal').classList.add('hidden');
        }, 300);
    }

    function tambahVarian(varianData = null, ukuranList = []) {
        let html = `<div class="varianBlock border border-gray-200 rounded-xl p-6 mb-6 bg-gray-50 shadow-sm relative">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Varian</label>
            <input type="text" name="varians[${varianIndex}][varian]" class="form-input w-full rounded-lg mb-4 p-2 border-gray-300 focus:ring-blue-500" value="${varianData ? varianData.varian : ''}" required>
            
            <label class="block text-sm font-semibold text-gray-700 mb-1">Gambar Varian</label>
            <input type="file" name="varians[${varianIndex}][image]" class="form-input w-full rounded-lg mb-4">
            
            <div class="ukuransContainer space-y-4">
                <p class="text-sm font-bold text-gray-800">Ukuran & Stok:</p>`;

        if (ukuranList.length > 0) {
            ukuranList.forEach((u, uIndex) => {
                html += `<div class="ukuranBlock grid grid-cols-3 gap-3">
                    <input type="text" name="varians[${varianIndex}][ukurans][${uIndex}][ukuran]" class="form-input rounded-lg border-gray-300" value="${u.ukuran}" placeholder="Ukuran" required>
                    <input type="number" name="varians[${varianIndex}][ukurans][${uIndex}][harga]" class="form-input rounded-lg border-gray-300" value="${u.harga}" placeholder="Harga" required>
                    <input type="number" name="varians[${varianIndex}][ukurans][${uIndex}][stok]" class="form-input rounded-lg border-gray-300" value="${u.stok}" placeholder="Stok" required>
                </div>`;
            });
        } else {
            html += `<div class="ukuranBlock grid grid-cols-3 gap-3">
                <input type="text" name="varians[${varianIndex}][ukurans][0][ukuran]" class="form-input rounded-lg border-gray-300" placeholder="Ukuran" required>
                <input type="number" name="varians[${varianIndex}][ukurans][0][harga]" class="form-input rounded-lg border-gray-300" placeholder="Harga" required>
                <input type="number" name="varians[${varianIndex}][ukurans][0][stok]" class="form-input rounded-lg border-gray-300" placeholder="Stok" required>
            </div>`;
        }

        html += `</div>
            <button type="button" onclick="tambahUkuran(this, ${varianIndex})" class="mt-4 px-3 py-1 bg-gray-200 hover:bg-gray-300 text-sm font-medium rounded-lg transition-colors">
                + Tambah Ukuran
            </button>
        </div>`;

        document.getElementById('variansContainer').insertAdjacentHTML('beforeend', html);
        varianIndex++;
    }

    function tambahUkuran(button, vIndex) {
        let ukuransContainer = button.parentElement.querySelector('.ukuransContainer');
        let index = ukuransContainer.querySelectorAll('.ukuranBlock').length;
        let html = `<div class="ukuranBlock grid grid-cols-3 gap-3">
            <input type="text" name="varians[${vIndex}][ukurans][${index}][ukuran]" class="form-input rounded-lg border-gray-300" placeholder="Ukuran" required>
            <input type="number" name="varians[${vIndex}][ukurans][${index}][harga]" class="form-input rounded-lg border-gray-300" placeholder="Harga" required>
            <input type="number" name="varians[${vIndex}][ukurans][${index}][stok]" class="form-input rounded-lg border-gray-300" placeholder="Stok" required>
        </div>`;
        ukuransContainer.insertAdjacentHTML('beforeend', html);
    }

    function openDetailModal(id) {
        fetch(`/admin/merch/${id}`)
        .then(res => res.json())
        .then(product => {
            let html = `<div class="space-y-4">
                <p><strong class="font-semibold text-gray-800">Nama:</strong> ${product.name}</p>
                <p><strong class="font-semibold text-gray-800">Deskripsi:</strong> ${product.description ?? '-'}</p>
                <p><strong class="font-semibold text-gray-800">Event:</strong> ${product.event?.title ?? '-'}</p>
                <h4 class="font-bold mt-6 mb-2 text-gray-800 border-b pb-2">Varians Produk</h4>
            </div>`;
            product.varians.forEach(v => {
                html += `<div class="mt-4 border border-gray-200 p-4 rounded-xl bg-gray-50">
                    <div class="flex items-center space-x-4 mb-2">
                        ${v.image ? `<img src="${v.image}" class="h-16 w-16 object-cover rounded-lg shadow-md">` : ''}
                        <div>
                            <p class="font-bold text-gray-800 text-lg">${v.varian}</p>
                        </div>
                    </div>
                    <p class="font-semibold text-gray-700 mb-2">Daftar Ukuran:</p>
                    <ul class="space-y-2">`;
                v.ukurans.forEach(u => {
                    html += `<li class="flex justify-between items-center py-1 px-3 bg-white rounded-md shadow-sm">
                        <span>${u.ukuran}</span>
                        <span class="font-mono text-gray-600">Rp${u.harga}</span>
                        <span class="text-sm text-gray-500">(stok: ${u.stok})</span>
                    </li>`;
                });
                html += `</ul></div>`;
            });
            document.getElementById('detailContent').innerHTML = html;
            document.getElementById('detailModal').classList.remove('hidden');
            document.getElementById('detailModalContent').classList.remove('scale-95', 'opacity-0');
            document.getElementById('detailModalContent').classList.add('scale-100', 'opacity-100');
        });
    }

    function closeDetailModal() {
        document.getElementById('detailModalContent').classList.remove('scale-100', 'opacity-100');
        document.getElementById('detailModalContent').classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            document.getElementById('detailModal').classList.add('hidden');
        }, 300);
    }
</script>
@endsection