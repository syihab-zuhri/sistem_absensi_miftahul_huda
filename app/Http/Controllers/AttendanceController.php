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
     * REVISI LOGIKA: Ambil Semua Siswa di Kelas Tersebut (Pre-load)
     */
    public function getSessionData($schedule_id)
    {
        $schedule = Schedule::findOrFail($schedule_id);
        
        // Ambil semua siswa yang berada di kelas jadwal tersebut
        $students = Student::with('user')->where('class_id', $schedule->class_id)->orderBy('nisn', 'asc')->get();
        
        // Ambil absensi yang sudah tersimpan (jika ada)
        $attendances = Attendance::where('schedule_id', $schedule_id)->get()->keyBy('student_id');

        // Gabungkan datanya
        $data = $students->map(function($student) use ($attendances) {
            return [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'name' => $student->user->name ?? 'User Terhapus',
                'status' => $attendances->has($student->id) ? $attendances[$student->id]->status : '' // Kosong jika belum absen
            ];
        });

        return response()->json($data);
    }

    /**
     * FUNGSI BARU: Simpan Seluruh Sesi Sekaligus (Batch Save)
     */
    public function saveSession(Request $request, $schedule_id)
    {
        $request->validate([
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:hadir,sakit,izin,alpa',
        ]);

        $now = \Carbon\Carbon::now();

        // Simpan massal menggunakan updateOrCreate
        foreach($request->attendances as $att) {
            Attendance::updateOrCreate(
                ['schedule_id' => $schedule_id, 'student_id' => $att['student_id']],
                ['status' => $att['status'], 'timestamp' => $now]
            );
        }

        return response()->json(['success' => true, 'message' => 'Sesi Absensi Berhasil Disimpan!']);
    }
}