<?php

namespace App\Http\Controllers;

use App\Rules\AvailableLoginEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StudentSettingsController extends Controller
{
    public function index()
    {
        // Ambil data user yang sedang login beserta data siswanya
        $user = Auth::user();
        $student = $user->student;

        // Jika user ini ternyata belum punya data di tabel students
        if (! $student) {
            abort(403, 'Data siswa belum tersedia. Hubungi administrator sekolah.');
        }

        return view('student.settings', compact('student'));
    }

    // Fungsi untuk update password (dipindahkan dari StudentPortalController)
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'string', 'max:255', Password::defaults(), 'confirmed'], // Pastikan input new_password_confirmation ada di HTML
        ]);

        $user = Auth::user();

        // Cek apakah password lama sesuai
        if (! Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Sandi saat ini yang Anda masukkan salah.');
        }

        // Simpan password baru
        $user->update([
            'password' => Hash::make($request->new_password),
            'must_change_password' => false,
        ]);

        $request->session()->put('password_hash_web', $user->password);

        return back()->with('success', 'Kata sandi berhasil diperbarui! Silakan gunakan sandi baru untuk login selanjutnya.');
    }

    public function updateEmail(Request $request)
    {
        $user = Auth::user();
        $student = $user->student;

        if (! $student) {
            return back()->with('error', 'Data siswa tidak ditemukan.');
        }

        $request->merge(['personal_email' => mb_strtolower(trim((string) $request->personal_email))]);
        $request->validate([
            'personal_email' => ['required', 'email', 'max:255', new AvailableLoginEmail($user->id, $student->id)],
        ]);

        $student->update([
            'personal_email' => $request->personal_email,
        ]);

        return back()->with('success', 'Email pribadi berhasil disimpan! Sekarang Anda bisa login menggunakan email ini.');
    }
}
