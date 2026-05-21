<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Mengambil Sesi Jadwal yang Aktif Saat Ini
     */
    public function getActiveSession()
    {
        $now = \Carbon\Carbon::now();
        $day = $now->locale('id')->dayName; 

        // Admin bisa melihat semua sesi yang aktif (opsional, jika admin butuh akses scanner)
        // Tapi Guru HANYA bisa melihat sesi miliknya sendiri
        $query = Schedule::where('day', $day)
            ->where('start_time', '<=', $now->format('H:i'))
            ->where('end_time', '>=', $now->format('H:i'))
            ->with(['subject', 'teacher']);

        // Jika yang login BUKAN Admin, filter berdasarkan ID Guru
        if (!auth()->user()->hasRole('admin')) {
            $query->where('teacher_id', auth()->id());
        }

        return $query->first();
    }

    /**
     * FASE 6: Core Logic - Deteksi Sesi & Pencatatan (Scanner)
     */
    public function scan(Request $request)
    {
        $request->validate([
            'nisn' => 'required|string',
        ]);

        $nisn = $request->nisn;
        $student = Student::with('user')->where('nisn', $nisn)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal! NISN ' . $nisn . ' tidak terdaftar.'
            ], 404);
        }

        $now = \Carbon\Carbon::now();
        $day = $now->locale('id')->dayName;
        $time = $now->format('H:i:s');

        // Cari jadwal aktif yang SESUAI DENGAN GURU INI
        $query = Schedule::where('day', $day)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time);
            
        if (!auth()->user()->hasRole('admin')) {
            $query->where('teacher_id', auth()->id());
        }

        $activeSchedule = $query->first();

        if (!$activeSchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Ditolak: Anda tidak memiliki jadwal mengajar di kelas manapun pada jam ini.'
            ], 403);
        }

        $alreadyAttended = Attendance::where('schedule_id', $activeSchedule->id)
            ->where('student_id', $student->id)
            ->exists();

        if ($alreadyAttended) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa sudah melakukan presensi di sesi ini.'
            ], 400);
        }

        Attendance::create([
            'schedule_id' => $activeSchedule->id,
            'student_id' => $student->id,
            'status' => 'hadir',
            'timestamp' => $now
        ]);

        $studentName = $student->user->name ?? 'Siswa (NISN: ' . $nisn . ')';

        return response()->json([
            'success' => true,
            'message' => 'Kehadiran Tercatat!',
            'student_name' => $studentName
        ]);
    }

    /**
     * FUNGSI BARU (Item 8): Mengambil Data Log Sesi Secara Real-time untuk Tabel
     */
    public function getSessionLog($schedule_id)
    {
        $logs = Attendance::with(['student.user'])
                ->where('schedule_id', $schedule_id)
                ->orderBy('timestamp', 'desc')
                ->get();
                
        return response()->json($logs);
    }

    /**
     * FUNGSI BARU (Item 8): Memperbarui Status Absensi (Hadir/Izin/Sakit/Alpa) dari Dropdown
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:hadir,sakit,izin,alpa']);
        
        $attendance = Attendance::findOrFail($id);
        $attendance->update(['status' => $request->status]);
        
        return response()->json(['success' => true, 'message' => 'Status diperbarui']);
    }
}