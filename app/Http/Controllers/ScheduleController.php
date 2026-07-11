<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $subjects = Subject::all();
        $teachers = User::role(['guru', 'admin'])->get();
        $academicYears = \App\Models\AcademicYear::all();
        $activeYear = \App\Models\AcademicYear::active()->first();

        // Gunakan filter tahun ajaran dari request, jika tidak ada, gunakan tahun ajaran aktif
        $selectedYearId = $request->input('academic_year_id', $activeYear ? $activeYear->id : null);

        $dayOrder = [
            'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3,
            'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7
        ];

        // Status aktif global dari Cache (Untuk integrasi modul scanner nanti)
        $activeSemesterGlobal = Cache::get('active_semester', 'Ganjil');

        // Pemisahan data eksplisit untuk mencegah error [start_time] di View
        $queryGanjil = Schedule::with(['subject', 'teacher'])->where('semester', 'Ganjil');
        $queryGenap = Schedule::with(['subject', 'teacher'])->where('semester', 'Genap');

        if ($selectedYearId) {
            $queryGanjil->where('academic_year_id', $selectedYearId);
            $queryGenap->where('academic_year_id', $selectedYearId);
        }

        $schedules = [
            'Ganjil' => $queryGanjil->get()
                ->sortBy(function($schedule) use ($dayOrder) {
                    return ($dayOrder[$schedule->day] ?? 99) . '-' . $schedule->start_time;
                })->groupBy('day'),

            'Genap'  => $queryGenap->get()
                ->sortBy(function($schedule) use ($dayOrder) {
                    return ($dayOrder[$schedule->day] ?? 99) . '-' . $schedule->start_time;
                })->groupBy('day'),
        ];

        $classrooms = \App\Models\Classroom::orderBy('name')->get();

        return view('admin.schedules.index', compact('schedules', 'subjects', 'teachers', 'activeSemesterGlobal', 'classrooms', 'academicYears', 'selectedYearId'));
    }

    public function store(Request $request)
    {
        // Cek tahun ajaran aktif
        $activeYear = \App\Models\AcademicYear::active()->first();
        if (!$activeYear) {
            return redirect()->back()
                ->with('error', 'Tidak ada tahun ajaran aktif. Silakan hubungi admin untuk mengaktifkan tahun ajaran terlebih dahulu.');
        }

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'day' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'semester' => 'required|in:Ganjil,Genap',
            'class_id' => [
                'required',
                'string',
                Rule::unique('schedules')->where(function ($query) use ($request) {
                    return $query->where('day', $request->day)
                                 ->where('start_time', $request->start_time)
                                 ->where('end_time', $request->end_time)
                                 ->where('semester', $request->semester);
                })
            ]
        ], [
            'class_id.unique' => 'Jadwal Bentrok! Kelas ini sudah memiliki pelajaran lain pada hari, jam, dan semester tersebut.'
        ]);

        $data = $request->all();
        $data['academic_year_id'] = $activeYear->id;

        Schedule::create($data);
        return redirect()->back()->with('success', 'Jadwal baru berhasil ditambahkan untuk tahun ajaran ' . $activeYear->name . '!');
    }

    public function update(Request $request, Schedule $schedule)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'day' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'semester' => 'required|in:Ganjil,Genap',
            'class_id' => [
                'required',
                'string',
                Rule::unique('schedules')->where(function ($query) use ($request) {
                    return $query->where('day', $request->day)
                                 ->where('start_time', $request->start_time)
                                 ->where('end_time', $request->end_time)
                                 ->where('semester', $request->semester);
                })->ignore($schedule->id)
            ]
        ], [
            'class_id.unique' => 'Jadwal Bentrok! Kelas ini sudah memiliki pelajaran lain pada hari, jam, dan semester tersebut.'
        ]);

        // Cek tahun ajaran aktif
        $activeYear = \App\Models\AcademicYear::active()->first();
        if (!$activeYear) {
            return redirect()->back()
                ->with('error', 'Tidak ada tahun ajaran aktif. Silakan hubungi admin untuk mengaktifkan tahun ajaran terlebih dahulu.');
        }

        $data = $request->all();
        $data['academic_year_id'] = $activeYear->id;

        $schedule->update($data);
        return redirect()->back()->with('success', 'Jadwal berhasil diperbarui untuk tahun ajaran ' . $activeYear->name . '!');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return redirect()->back()->with('success', 'Jadwal berhasil dihapus!');
    }

    // LANGKAH 6: Hapus Seluruh Jadwal Berdasarkan Tahun Ajaran
    public function truncateByYear(Request $request)
    {
        $request->validate(['academic_year_id' => 'required|exists:academic_years,id']);

        Schedule::where('academic_year_id', $request->academic_year_id)->delete();

        return redirect()->back()->with('success', 'Seluruh data jadwal untuk tahun ajaran ini berhasil dikosongkan!');
    }

    // Toggle Aktif Semester
    public function toggleSemester(Request $request)
    {
        $request->validate(['semester' => 'required|in:Ganjil,Genap']);
        Cache::forever('active_semester', $request->semester);
        return redirect()->back()->with('success', 'Sistem Absensi kini menggunakan ' . $request->semester . '!');
    }

    // LANGKAH 5: Hapus Seluruh Jadwal Berdasarkan Semester
    public function truncate(Request $request)
    {
        $request->validate(['semester' => 'required|in:Ganjil,Genap']);
        
        Schedule::where('semester', $request->semester)->delete();
        
        return redirect()->back()->with('success', 'Seluruh data jadwal untuk Semester ' . $request->semester . ' berhasil dikosongkan!');
    }
}