<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StudentPortalController extends Controller
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

        // Batas waktu 30 hari terakhir
        $thirtyDaysAgo = Carbon::now()->subDays(30);

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
}
