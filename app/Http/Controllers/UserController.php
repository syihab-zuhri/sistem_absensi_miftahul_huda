<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        // Ambil semua user kecuali siswa (Hanya admin & guru)
        $users = User::role(['admin', 'guru'])->get();
        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,guru'
        ]);

        // Buat Akun
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Berikan Hak Akses (Role)
        $user->assignRole($request->role);

        return redirect()->back()->with('success', 'Pengguna baru berhasil ditambahkan!');
    }

    public function destroy(User $user)
    {
        // Mencegah admin menghapus akunnya sendiri yang sedang dipakai
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Peringatan: Anda tidak dapat menghapus akun Anda sendiri!');
        }
        
        $user->delete();
        return redirect()->back()->with('success', 'Pengguna berhasil dihapus!');
    }
}