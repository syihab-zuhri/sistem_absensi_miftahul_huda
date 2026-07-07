<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StudentSettingsController extends Controller
{
    public function index()
    {
        // Ambil data user yang sedang login beserta data siswanya
        $user = Auth::user();
        $student = $user->student;

        // Jika user ini ternyata belum punya data di tabel students
        if (!$student) {
            return redirect()->route('dashboard')->with('error', 'Data siswa Anda belum lengkap. Silakan hubungi Admin.');
        }

        return view('student.settings', compact('student'));
    }

    // Fungsi untuk update password (dipindahkan dari StudentPortalController)
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed', // Pastikan input new_password_confirmation ada di HTML
        ]);

        $user = Auth::user();

        // Cek apakah password lama sesuai
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Sandi saat ini yang Anda masukkan salah.');
        }

        // Simpan password baru
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return back()->with('success', 'Kata sandi berhasil diperbarui! Silakan gunakan sandi baru untuk login selanjutnya.');
    }

    public function updateEmail(Request $request)
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return back()->with('error', 'Data siswa tidak ditemukan.');
        }

        $request->validate([
            'personal_email' => 'required|email|max:255|unique:users,email|unique:students,personal_email,' . $student->id,
        ]);

        $student->update([
            'personal_email' => $request->personal_email
        ]);

        return back()->with('success', 'Email pribadi berhasil disimpan! Sekarang Anda bisa login menggunakan email ini.');
    }
}