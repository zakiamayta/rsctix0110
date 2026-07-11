@extends('layouts.admin') {{-- Sesuaikan dengan nama master layout admin Anda --}}

@section('content')
<div class="w-full p-6 bg-gray-50 min-h-screen">
    
    {{-- 🏷️ JUDUL HALAMAN --}}
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900 tracking-tight">Kelola Data User</h1>
        <p class="text-xs text-gray-500 mt-1">Manajemen hak akses, pencarian data user, dan konfigurasi tingkatan role pengguna platform.</p>
    </div>

    {{-- ⚠️ NOTIFIKASI SUKSES --}}
    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs p-3 rounded flex items-center gap-2 shadow-sm font-medium">
            ✅ {{ session('success') }}
        </div>
    @endif

    {{-- 🔍 PANEL FILTER & PENCARIAN --}}
    <div class="bg-white border rounded shadow-sm p-4 mb-6">
        <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-col md:flex-row gap-3 items-end">
            
            {{-- Input Pencarian Nama/Email --}}
            <div class="w-full md:w-1/3">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cari Nama / Email:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama atau email user..." 
                       class="w-full rounded border border-gray-300 p-2 text-xs bg-white focus:outline-none focus:border-indigo-500 font-medium text-gray-700">
            </div>

            {{-- Dropdown Filter Role --}}
            <div class="w-full md:w-1/4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Filter Berdasarkan Role:</label>
                <select name="role" class="w-full rounded border border-gray-300 p-2 text-xs bg-white focus:outline-none focus:border-indigo-500 font-medium text-gray-700">
                    <option value="">-- Semua Role --</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="owner" {{ request('role') == 'owner' ? 'selected' : '' }}>Owner</option>
                    <option value="eo" {{ request('role') == 'eo' ? 'selected' : '' }}>Event Organizer (EO)</option>
                    <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>Regular User</option>
                </select>
            </div>

            {{-- Tombol Aksi --}}
            <div class="flex gap-2 w-full md:w-auto">
                <button type="submit" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded text-xs font-bold transition shadow-sm w-full md:w-auto">
                    Terapkan Filter
                </button>
                @if(request()->has('search') || request()->has('role'))
                    <a href="{{ route('admin.users.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded text-xs font-bold transition text-center w-full md:w-auto">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- 📊 TABEL DATA USER (LEBAR KE BAWAH) --}}
    <div class="bg-white border rounded shadow-sm overflow-hidden w-full">
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-gray-900 text-white text-[11px] uppercase tracking-wider font-bold">
                        <th class="p-3 text-center border-r border-gray-800 w-12">ID</th>
                        <th class="p-3">Nama Lengkap</th>
                        <th class="p-3">Alamat Email</th>
                        <th class="p-3">No. Telepon</th>
                        <th class="p-3 text-center">Tgl Lahir / Gender</th>
                        <th class="p-3 text-center">Status Profil</th>
                        <th class="p-3 text-center w-48">Role Pengguna</th>
                        <th class="p-3 text-center w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-xs font-medium text-gray-700 divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50/70 transition">
                            {{-- ID User --}}
                            <td class="p-3 text-center font-mono font-bold text-gray-400 bg-gray-50 border-r border-gray-200">{{ $user->id }}</td>
                            
                            {{-- Nama User & Avatar --}}
                            <td class="p-3 font-semibold text-gray-900">
                                <div class="flex items-center gap-2">
                                    <img src="{{ $user->avatar ? asset($user->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=EBF4FF&color=7F9CF5' }}" 
                                         alt="Avatar" class="w-6 h-6 rounded-full border shadow-sm">
                                    <span>{{ $user->name ?? '-' }}</span>
                                </div>
                            </td>

                            {{-- Email --}}
                            <td class="p-3 font-mono text-gray-600">
                                {{ $user->email }}
                                @if($user->google_id)
                                    <span class="ml-1 bg-blue-50 text-blue-600 border border-blue-200 text-[10px] px-1 rounded font-sans">Google Auth</span>
                                @endif
                            </td>

                            {{-- No HP --}}
                            <td class="p-3 font-mono text-gray-600">{{ $user->phone ?? '-' }}</td>

                            {{-- Tanggal Lahir & Gender --}}
                            <td class="p-3 text-center leading-relaxed">
                                <div>{{ $user->birth_date ?? '-' }}</div>
                                <div class="text-[10px] text-gray-400 capitalize">{{ $user->gender ?? '-' }}</div>
                            </td>

                            {{-- Lengkap Profil --}}
                            <td class="p-3 text-center">
                                @if($user->profile_complete)
                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded-full text-[10px] font-bold">Lengkap</span>
                                @else
                                    <span class="bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded-full text-[10px] font-bold">Belum Lengkap</span>
                                @endif
                            </td>

                            {{-- Form Update Role Terintegrasi Per Baris --}}
                            <td class="p-3 text-center bg-gray-50/50">
                                <form id="form-role-{{ $user->id }}" action="{{ route('admin.users.updateRole', $user->id) }}" method="POST" class="flex items-center gap-1.5 justify-center">
                                    @csrf
                                    @method('PATCH')
                                    
                                    <select name="role" class="rounded border border-gray-300 p-1 text-xs bg-white font-bold text-gray-800 focus:outline-none focus:border-indigo-600 shadow-sm w-full">
                                        <option value="user" class="font-normal" {{ $user->role == 'user' ? 'selected' : '' }}>Regular User</option>
                                        <option value="eo" class="font-normal" {{ $user->role == 'eo' ? 'selected' : '' }}>Event Organizer</option>
                                        <option value="owner" class="font-normal" {{ $user->role == 'owner' ? 'selected' : '' }}>Owner</option>
                                        <option value="admin" class="font-normal" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                </form>
                            </td>

                            {{-- Tombol Submit Form --}}
                            <td class="p-3 text-center">
                                <button type="submit" form="form-role-{{ $user->id }}" 
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3 py-1 rounded text-[11px] transition shadow-sm">
                                    Simpan
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-400 font-medium">
                                📭 Tidak ada data user yang ditemukan sesuai kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 📄 LINK PAGINATION LARAVEL --}}
        @if($users->hasPages())
            <div class="p-3 bg-gray-50 border-t text-xs">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection