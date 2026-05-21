<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // Tambahan untuk keamanan password

class StudentPortalController extends Controller
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

        // Batas waktu 30 hari terakhir
        $thirtyDaysAgo = \Carbon\Carbon::now()->subDays(30);

        // Ambil riwayat kehadiran siswa ini (Batas 30 hari terakhir sesuai Item 10)
        $attendances = Attendance::with('schedule.subject')
                        ->where('student_id', $student->id)
                        ->where('timestamp', '>=', $thirtyDaysAgo)
                        ->orderBy('timestamp', 'desc')
                        ->get();

        // Hitung persentase kehadiran (Berdasarkan 30 hari terakhir)
        $totalSessions = Attendance::where('student_id', $student->id)
                                  ->where('timestamp', '>=', $thirtyDaysAgo)
                                  ->count();
        $presentCount = Attendance::where('student_id', $student->id)
                                 ->where('status', 'hadir')
                                 ->where('timestamp', '>=', $thirtyDaysAgo)
                                 ->count();
        
        $attendancePercentage = 0;
        if ($totalSessions > 0) {
            $attendancePercentage = round(($presentCount / $totalSessions) * 100);
        }

        // Kirim data ke tampilan
        return view('student.portal', compact('student', 'attendances', 'attendancePercentage'));
    }

    // FUNGSI BARU: Ganti Password Siswa
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

        // Simpan password baru (NISN tetap aman di tabel students, hanya password login yang berubah di tabel users)
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return back()->with('success', 'Kata sandi berhasil diperbarui! Silakan gunakan sandi baru untuk login selanjutnya.');
    }
}
