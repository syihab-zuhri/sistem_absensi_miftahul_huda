<?php

namespace App\Http\Controllers;

use App\Models\AttendanceAudit;
use App\Models\AttendanceSession;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\User;
use App\Rules\AvailableLoginEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

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
        $request->merge(['email' => mb_strtolower(trim((string) $request->email))]);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', new AvailableLoginEmail],
            'password' => 'required|string|min:12|max:255',
            'role' => 'required|in:admin,guru',
        ]);

        // Buat Akun
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'must_change_password' => true,
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

        if ($user->student || Schedule::where('teacher_id', $user->id)->exists()
            || AttendanceSession::where('teacher_id', $user->id)->exists()
            || AttendanceAudit::where('actor_id', $user->id)->exists()
            || LeaveRequest::where('reviewed_by', $user->id)->exists()
            || ($user->hasRole('admin') && User::role('admin')->count() <= 1)) {
            throw ValidationException::withMessages(['user' => 'Akun yang terhubung dengan data akademik atau admin terakhir tidak dapat dihapus.']);
        }
        $user->delete();

        return redirect()->back()->with('success', 'Pengguna berhasil dihapus!');
    }
}
