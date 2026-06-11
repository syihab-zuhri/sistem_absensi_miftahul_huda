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
    public function index()
    {
        $subjects = Subject::all();
        $teachers = User::role(['guru', 'admin'])->get();

        $dayOrder = [
            'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 
            'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7
        ];

        // Status aktif global dari Cache (Untuk integrasi modul scanner nanti)
        $activeSemesterGlobal = Cache::get('active_semester', 'Ganjil');

        // Pemisahan data eksplisit untuk mencegah error [start_time] di View
        $schedules = [
            'Ganjil' => Schedule::with(['subject', 'teacher'])->where('semester', 'Ganjil')->get()
                ->sortBy(function($schedule) use ($dayOrder) {
                    return ($dayOrder[$schedule->day] ?? 99) . '-' . $schedule->start_time;
                })->groupBy('day'),
                
            'Genap'  => Schedule::with(['subject', 'teacher'])->where('semester', 'Genap')->get()
                ->sortBy(function($schedule) use ($dayOrder) {
                    return ($dayOrder[$schedule->day] ?? 99) . '-' . $schedule->start_time;
                })->groupBy('day'),
        ];

        $classrooms = \App\Models\Classroom::orderBy('name')->get();

        return view('admin.schedules.index', compact('schedules', 'subjects', 'teachers', 'activeSemesterGlobal', 'classrooms'));
    }

    public function store(Request $request)
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
                })
            ]
        ], [
            'class_id.unique' => 'Jadwal Bentrok! Kelas ini sudah memiliki pelajaran lain pada hari, jam, dan semester tersebut.'
        ]);

        Schedule::create($request->all());
        return redirect()->back()->with('success', 'Jadwal baru berhasil ditambahkan!');
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

        $schedule->update($request->all());
        return redirect()->back()->with('success', 'Jadwal berhasil diperbarui!');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return redirect()->back()->with('success', 'Jadwal berhasil dihapus!');
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