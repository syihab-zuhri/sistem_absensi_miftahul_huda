<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['academic_year_id' => 'nullable|integer|exists:academic_years,id']);
        $subjects = Subject::orderBy('name')->get();
        $teachers = User::role(['guru', 'admin'])->orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $activeYear = AcademicYear::active()->first();
        $selectedYearId = $request->input('academic_year_id', $activeYear?->id);
        $activeSemesterGlobal = $activeYear?->active_semester ?? 'Ganjil';
        $classrooms = Classroom::orderBy('name')->get();
        $dayOrder = array_flip(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']);
        $rows = Schedule::with(['subject', 'teacher'])->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))->get();
        $schedules = [];
        foreach (['Ganjil', 'Genap'] as $semester) {
            $schedules[$semester] = $rows->where('semester', $semester)->sortBy(fn ($s) => ($dayOrder[$s->day] ?? 9).$s->start_time)->groupBy('day');
        }

        return view('admin.schedules.index', compact('schedules', 'subjects', 'teachers', 'activeSemesterGlobal', 'classrooms', 'academicYears', 'selectedYearId'));
    }

    public function store(Request $request)
    {
        $year = AcademicYear::find($request->input('academic_year_id')) ?? AcademicYear::active()->first();
        if (! $year) {
            throw ValidationException::withMessages(['academic_year_id' => 'Aktifkan tahun ajaran terlebih dahulu.']);
        }
        $data = $this->validatedSchedule($request, $year->id);
        DB::transaction(function () use ($data, $year) {
            AcademicYear::whereKey($year->id)->lockForUpdate()->firstOrFail();
            $this->ensureNoConflict($data);
            Schedule::create($data);
        });

        return back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function update(Request $request, Schedule $schedule)
    {
        $data = $this->validatedSchedule($request, $schedule->academic_year_id);
        DB::transaction(function () use ($data, $schedule) {
            AcademicYear::whereKey($schedule->academic_year_id)->lockForUpdate()->firstOrFail();
            $locked = Schedule::lockForUpdate()->findOrFail($schedule->id);
            if (($locked->attendances()->exists() || $locked->archivedAttendances()->exists() || $locked->sessions()->exists()) && collect($data)->contains(fn ($value, $key) => (string) $locked->$key !== (string) $value)) {
                throw ValidationException::withMessages(['schedule' => 'Jadwal yang memiliki absensi disimpan sebagai arsip. Buat jadwal baru untuk perubahan.']);
            }
            $this->ensureNoConflict($data, $schedule->id);
            $locked->update($data);
        });

        return back()->with('success', 'Jadwal berhasil diperbarui.');
    }

    private function validatedSchedule(Request $request, ?int $yearId): array
    {
        $data = $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'teacher_id' => 'required|integer|exists:users,id',
            'class_id' => 'required|string|max:255|exists:classrooms,name',
            'day' => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])],
            'start_time' => ['required', 'date_format:H:i,H:i:s'],
            'end_time' => ['required', 'date_format:H:i,H:i:s', 'after:start_time'],
            'semester' => ['required', Rule::in(['Ganjil', 'Genap'])],
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
        ]);
        if (! User::findOrFail($data['teacher_id'])->hasAnyRole(['admin', 'guru'])) {
            throw ValidationException::withMessages(['teacher_id' => 'Pengajar harus memiliki peran guru atau admin.']);
        }
        foreach (['start_time', 'end_time'] as $key) {
            $data[$key] = strlen($data[$key]) === 5 ? $data[$key].':00' : $data[$key];
        }
        $data['academic_year_id'] = $yearId;

        return $data;
    }

    private function ensureNoConflict(array $data, ?int $ignore = null): void
    {
        $query = Schedule::where('academic_year_id', $data['academic_year_id'])->where('semester', $data['semester'])
            ->where('day', $data['day'])->where('start_time', '<', $data['end_time'])->where('end_time', '>', $data['start_time'])
            ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore));
        $errors = [];
        if ((clone $query)->where('class_id', $data['class_id'])->exists()) {
            $errors['class_id'] = 'Rentang waktu kelas bertabrakan dengan jadwal lain.';
        }
        if ((clone $query)->where('teacher_id', $data['teacher_id'])->exists()) {
            $errors['teacher_id'] = 'Guru sudah mengajar pada rentang waktu tersebut.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function deleteSchedules($query): void
    {
        DB::transaction(function () use ($query) {
            $rows = $query->lockForUpdate()->get();
            if (Schedule::whereKey($rows->modelKeys())->where(fn ($q) => $q->whereHas('attendances')->orWhereHas('archivedAttendances')->orWhereHas('sessions'))->exists()) {
                throw ValidationException::withMessages(['schedules' => 'Jadwal yang memiliki riwayat absensi tidak dapat dihapus.']);
            }
            Schedule::whereKey($rows->modelKeys())->delete();
        });
    }

    public function destroy(Schedule $schedule)
    {
        $this->deleteSchedules(Schedule::whereKey($schedule->id));

        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    public function destroyBulk(Request $request)
    {
        $data = $request->validate(['schedule_ids' => 'required|array|min:1|max:1000', 'schedule_ids.*' => 'required|integer|distinct|exists:schedules,id']);
        $this->deleteSchedules(Schedule::whereKey($data['schedule_ids']));

        return back()->with('success', 'Jadwal terpilih berhasil dihapus.');
    }

    public function truncateByYear(Request $request)
    {
        $data = $request->validate(['academic_year_id' => 'required|integer|exists:academic_years,id']);
        $this->deleteSchedules(Schedule::where('academic_year_id', $data['academic_year_id']));

        return back()->with('success', 'Jadwal tanpa absensi pada tahun terpilih berhasil dihapus.');
    }

    public function truncate(Request $request)
    {
        $data = $request->validate(['semester' => 'required|in:Ganjil,Genap', 'academic_year_id' => 'required|integer|exists:academic_years,id']);
        $this->deleteSchedules(Schedule::where('semester', $data['semester'])->where('academic_year_id', $data['academic_year_id']));

        return back()->with('success', 'Jadwal semester pada tahun terpilih berhasil dihapus.');
    }

    public function toggleSemester(Request $request)
    {
        $data = $request->validate(['semester' => 'required|in:Ganjil,Genap']);
        $year = AcademicYear::active()->first();
        if (! $year) {
            throw ValidationException::withMessages(['semester' => 'Aktifkan tahun ajaran terlebih dahulu.']);
        }
        $year->update(['active_semester' => $data['semester']]);

        return back()->with('success', 'Semester aktif berhasil diperbarui.');
    }
}
