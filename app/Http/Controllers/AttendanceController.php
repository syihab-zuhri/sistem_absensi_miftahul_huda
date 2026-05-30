<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Mengambil Sesi Jadwal yang Aktif Saat Ini
     * Hanya mengembalikan jadwal jika jadwal tersebut MILIK GURU YANG SEDANG LOGIN
     */
    public function getActiveSession()
    {
        $now = \Carbon\Carbon::now();
        $day = $now->locale('id')->dayName; 

        // Admin bisa melihat semua sesi yang aktif
        // Guru HANYA bisa melihat sesi miliknya sendiri
        $query = Schedule::where('day', $day)
            ->where('start_time', '<=', $now->format('H:i'))
            ->where('end_time', '>=', $now->format('H:i'))
            ->with(['subject', 'teacher']);

        if (!auth()->user()->hasRole('admin')) {
            $query->where('teacher_id', auth()->id());
        }

        return $query->first();
    }

    /**
     * Ambil Semua Siswa (Pre-load) KHUSUS HARI INI
     */
    public function getSessionData($schedule_id)
    {
        $schedule = Schedule::findOrFail($schedule_id);
        
        // Ambil semua siswa di kelas
        $students = Student::with('user')->where('class_id', $schedule->class_id)->orderBy('nisn', 'asc')->get();
        
        // Hanya ambil absensi HARI INI agar data minggu lalu tidak bocor
        $today = \Carbon\Carbon::today();
        $attendances = Attendance::where('schedule_id', $schedule_id)
                        ->whereDate('timestamp', $today)
                        ->get()
                        ->keyBy('student_id');

        $data = $students->map(function($student) use ($attendances) {
            return [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'name' => $student->user->name ?? 'User Terhapus',
                'status' => $attendances->has($student->id) ? $attendances[$student->id]->status : '' 
            ];
        });

        return response()->json($data);
    }

    /**
     * Simpan Sesi (Batch Save) Mencegah Timpa Data Minggu Lalu
     */
    public function saveSession(Request $request, $schedule_id)
    {
        $request->validate([
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:hadir,sakit,izin,alpa',
        ]);

        $today = \Carbon\Carbon::today();
        $now = \Carbon\Carbon::now();

        foreach($request->attendances as $att) {
            // Cek apakah siswa sudah diabsen khusus hari ini
            $attendance = Attendance::where('schedule_id', $schedule_id)
                ->where('student_id', $att['student_id'])
                ->whereDate('timestamp', $today)
                ->first();

            if ($attendance) {
                // Jika sudah ada (hari ini), update statusnya
                $attendance->update(['status' => $att['status']]);
            } else {
                // Jika belum ada (hari ini), buat data absensi baru
                Attendance::create([
                    'schedule_id' => $schedule_id,
                    'student_id' => $att['student_id'],
                    'status' => $att['status'],
                    'timestamp' => $now
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Sesi Absensi Berhasil Disimpan!']);
    }
}