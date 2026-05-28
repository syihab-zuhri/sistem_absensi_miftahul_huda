<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    public function index()
    {
        // 1. Ambil data untuk dropdown
        $subjects = Subject::all();
        $teachers = User::role(['guru', 'admin'])->get();

        // 2. Ambil semua jadwal dan urutkan secara logis (Senin -> Minggu, lalu berdasarkan jam)
        $dayOrder = [
            'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 
            'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7
        ];

        $schedules = Schedule::with(['subject', 'teacher'])->get()
            ->sortBy(function($schedule) use ($dayOrder) {
                return $dayOrder[$schedule->day] . '-' . $schedule->start_time;
            })
            ->groupBy('day'); // Dikelompokkan berdasarkan hari

        return view('admin.schedules.index', compact('schedules', 'subjects', 'teachers'));
    }

    public function store(Request $request)
    {
        // REVISI 1C: Paksa input class_id menjadi UPPERCASE sebelum diproses dan divalidasi
        $request->merge([
            'class_id' => strtoupper($request->input('class_id'))
        ]);

        // Validasi Anti Bentrok: Kelas yang sama tidak boleh memiliki jadwal di hari dan jam yang sama persis
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'day' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'class_id' => [
                'required',
                'string',
                Rule::unique('schedules')->where(function ($query) use ($request) {
                    return $query->where('day', $request->day)
                                ->where('start_time', $request->start_time)
                                ->where('end_time', $request->end_time);
                })
            ]
        ], [
            'class_id.unique' => 'Jadwal Bentrok! Kelas ini sudah memiliki pelajaran lain pada hari dan jam tersebut.'
        ]);

        Schedule::create($request->all());

        return redirect()->back()->with('success', 'Jadwal baru berhasil ditambahkan!');
    }

    public function update(Request $request, Schedule $schedule)
    {
        // REVISI 1C: Paksa input class_id menjadi UPPERCASE sebelum diproses dan divalidasi
        $request->merge([
            'class_id' => strtoupper($request->input('class_id'))
        ]);

        // Validasi Anti Bentrok (Mengabaikan ID jadwal yang sedang diedit)
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'day' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'class_id' => [
                'required',
                'string',
                Rule::unique('schedules')->where(function ($query) use ($request) {
                    return $query->where('day', $request->day)
                                ->where('start_time', $request->start_time)
                                ->where('end_time', $request->end_time);
                })->ignore($schedule->id)
            ]
        ], [
            'class_id.unique' => 'Jadwal Bentrok! Kelas ini sudah memiliki pelajaran lain pada hari dan jam tersebut.'
        ]);

        $schedule->update($request->all());

        return redirect()->back()->with('success', 'Jadwal berhasil diperbarui!');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return redirect()->back()->with('success', 'Jadwal berhasil dihapus!');
    }

    // FUNGSI BARU: Hapus Massal
    public function destroyBulk(Request $request)
    {
        $ids = $request->input('schedule_ids');
        if (!$ids || count($ids) == 0) {
            return redirect()->back()->with('error', 'Tidak ada jadwal yang dipilih.');
        }

        Schedule::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', count($ids) . ' sesi jadwal berhasil dihapus.');
    }

    // FUNGSI BARU: Kosongkan Tabel (Truncate)
    public function truncate()
    {
        Schedule::truncate();
        return redirect()->back()->with('success', 'Seluruh jadwal telah dikosongkan. Siap untuk semester baru!');
    }
}