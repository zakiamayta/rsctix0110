<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * Menampilkan daftar user dengan fitur filter & pencarian
     */
    public function index(Request $request)
    {
        $query = User::query();

        // 🔍 Filter Pencarian Nama atau Email
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // 🎭 Filter Berdasarkan Role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Tampilkan data melebar ke bawah, urutkan dari pendaftaran terbaru (ID terbesar)
        $users = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Memperbarui role milik user tertentu
     */
    public function updateRole(Request $request, $id)
    {
        // Validasi agar inputan sesuai dengan struktur ENUM di database
        $request->validate([
            'role' => 'required|in:admin,owner,eo,user',
        ]);

        $user = User::findOrFail($id);
        $user->role = $request->role;
        $user->save();

        return redirect()->back()->with('success', "Role untuk user {$user->email} berhasil diperbarui menjadi {$user->role}.");
    }
}