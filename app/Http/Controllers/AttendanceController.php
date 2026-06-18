<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AttendanceController extends Controller
{
    /**
     * Menampilkan Halaman Antarmuka Scanner Utama (UI)
     */
    public function index()
    {
        $hariIni = Carbon::now()->locale('id')->dayName; // Senin, Selasa, dst...
        $waktuSekarang = Carbon::now()->format('H:i:s');
        
        // MENGAMBIL STATUS SEMESTER YANG SEDANG AKTIF (Diatur oleh Admin)
        $activeSemester = Cache::get('active_semester', 'Ganjil');

        // MENCARI JADWAL CERDAS:
        // Harus milik guru ini, pada hari ini, rentang jam saat ini, DAN di semester yang AKTIF
        $activeSchedule = Schedule::with('subject')
            ->where('teacher_id', Auth::id())
            ->where('day', $hariIni)
            ->where('start_time', '<=', $waktuSekarang)
            ->where('end_time', '>=', $waktuSekarang)
            ->where('semester', $activeSemester) // <--- KUNCI UTAMA FILTER SEMESTER
            ->first();

        // Mengirim data jadwal yang ditemukan ke halaman view scanner
        return view('scanner', compact('activeSchedule', 'activeSemester'));
    }

    /**
     * API: Mengambil daftar seluruh siswa di kelas yang sedang aktif (Pre-load)
     */
    public function getSessionData($schedule_id)
    {
        $schedule = Schedule::findOrFail($schedule_id);
        
        // Ambil semua siswa di kelas tersebut, urutkan berdasarkan nama
        $students = Student::with('user')
            ->where('class_id', $schedule->class_id)
            ->get()
            ->sortBy(function($student) {
                return $student->user->name;
            })->values();
        
        // Hanya ambil absensi HARI INI agar data tidak bocor
        $today = Carbon::today();
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
     * API: Menyimpan absensi massal (Batch Save) dari Scanner UI
     */
    public function saveSession(Request $request, $schedule_id)
    {
        $request->validate([
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:hadir,sakit,izin,alpa',
        ]);

        $today = Carbon::today();
        $now = Carbon::now();
        $schedule = Schedule::findOrFail($schedule_id);

        foreach($request->attendances as $att) {
            // Validasi Server-Side: Pastikan siswa ini berasal dari class_id yang sama dengan jadwal ini
            $student = \App\Models\Student::find($att['student_id']);
            if (!$student || $student->class_id !== $schedule->class_id) {
                continue; // Skip data manipulatif dari luar kelas
            }

            // Cek apakah siswa sudah diabsen khusus hari ini
            $attendance = Attendance::where('schedule_id', $schedule_id)
                ->where('student_id', $att['student_id'])
                ->whereDate('timestamp', $today)
                ->first();

            if ($attendance) {
                // Jika sudah ada (hari ini), update status & waktunya
                $attendance->update([
                    'status' => $att['status'],
                    'timestamp' => $now
                ]);
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