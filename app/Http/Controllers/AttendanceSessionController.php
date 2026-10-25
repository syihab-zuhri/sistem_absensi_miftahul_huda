<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceSessionRequest;
use App\Http\Requests\SaveAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceRecorder;
use App\Services\AttendanceSessions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceSessionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['start_date' => 'nullable|date_format:Y-m-d', 'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date']);
        $startDate = $filters['start_date'] ?? today()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? today()->addMonth()->endOfMonth()->toDateString();
        $sessions = AttendanceSession::visibleTo($request->user())->with(['schedule.subject', 'teacher'])->withCount('attendances')
            ->whereBetween('lesson_date', [$startDate, $endDate])->orderByDesc('lesson_date')->orderBy('start_time')->paginate(25)->withQueryString();
        $schedules = Schedule::with(['subject', 'academicYear'])->whereHas('academicYear', fn ($q) => $q->where('is_active', true))
            ->when(! $request->user()->hasRole('admin'), fn ($q) => $q->where('teacher_id', $request->user()->id))
            ->get()->filter(fn ($schedule) => $schedule->semester === $schedule->academicYear->active_semester);
        $teachers = $request->user()->hasRole('admin') ? User::role(['guru', 'admin'])->orderBy('name')->get() : collect();

        return view('attendance-sessions.index', compact('sessions', 'schedules', 'teachers', 'startDate', 'endDate'));
    }

    public function store(AttendanceSessionRequest $request, AttendanceSessions $sessions)
    {
        $schedule = Schedule::findOrFail($request->validated('schedule_id'));
        $session = $sessions->create($schedule, $request->validated(), $request->user());

        return redirect()->route('attendance-sessions.show', $session)->with('success', 'Sesi pertemuan berhasil dibuat.');
    }

    public function show(AttendanceSession $session)
    {
        Gate::authorize('view', $session);
        $session->load(['schedule.subject', 'schedule.academicYear', 'teacher']);
        $students = $this->students($session)->load('user')->sortBy('user.name');
        $attendances = $session->attendances()->get()->keyBy('student_id');
        $audits = $session->audits()->with(['actor', 'student.user'])->orderByDesc('id')->paginate(25);
        $pendingCount = $session->leaveRequests()->where('status', 'pending')->count();
        $teachers = auth()->user()->hasRole('admin') ? User::role(['guru', 'admin'])->orderBy('name')->get() : collect();

        return view('attendance-sessions.show', compact('session', 'students', 'attendances', 'audits', 'pendingCount', 'teachers'));
    }

    private function students(AttendanceSession $session)
    {
        return $session->opened_at ? $session->students()->get() : Student::active()->where('class_id', $session->class_id)->get();
    }

    public function open(Request $request, AttendanceSession $session, AttendanceSessions $sessions)
    {
        $sessions->open($session, $request->user());

        return back()->with('success', 'Sesi dibuka dan daftar peserta disimpan.');
    }

    public function close(Request $request, AttendanceSession $session, AttendanceSessions $sessions)
    {
        $request->validate(['mark_unrecorded_absent' => 'sometimes|boolean']);
        $sessions->close($session, $request->user(), $request->boolean('mark_unrecorded_absent'));

        return back()->with('success', 'Sesi ditutup. Perubahan berikutnya dilakukan melalui koreksi dengan alasan.');
    }

    public function cancel(Request $request, AttendanceSession $session, AttendanceSessions $sessions)
    {
        Gate::authorize('manage', $session);
        $data = $request->validate(['reason' => 'required|string|min:10|max:1000']);
        $sessions->cancel($session, $request->user(), $data['reason']);

        return back()->with('success', 'Sesi dibatalkan dan alasannya dicatat.');
    }

    public function update(AttendanceSessionRequest $request, AttendanceSession $session, AttendanceSessions $sessions)
    {
        $sessions->reschedule($session, $request->validated(), $request->user());

        return back()->with('success', 'Waktu pertemuan diperbarui dan perubahan dicatat.');
    }

    public function scanner(AttendanceSession $session)
    {
        Gate::authorize('record', $session);
        $activeSchedule = $session->schedule()->with('subject')->firstOrFail();

        return view('scanner', ['activeSchedule' => $activeSchedule, 'activeSession' => $session, 'activeSemester' => $activeSchedule->semester]);
    }

    public function roster(AttendanceSession $session)
    {
        Gate::authorize('view', $session);
        $attendances = $session->attendances()->get()->keyBy('student_id');

        return response()->json($this->students($session)->load('user')->sortBy('user.name')->values()->map(fn ($student) => [
            'id' => $student->id, 'nisn' => $student->nisn, 'name' => $student->user->name,
            'status' => $attendances->get($student->id)?->status ?? '',
        ]));
    }

    public function save(SaveAttendanceRequest $request, AttendanceSession $session, AttendanceRecorder $recorder)
    {
        Gate::authorize('record', $session);
        $data = $request->validated();
        DB::transaction(function () use ($session, $data, $request, $recorder) {
            $session = AttendanceSession::with('schedule.academicYear')->lockForUpdate()->findOrFail($session->id);
            Gate::authorize('record', $session);
            foreach ($data['attendances'] as $row) {
                $recorder->record($session, Student::findOrFail($row['student_id']), $row['status'], $request->user(), $data['reason'] ?? null, $row['expected_status'] ?? null, array_key_exists('expected_status', $row));
            }
        }, 3);

        return response()->json(['success' => true, 'message' => 'Presensi sesi berhasil disimpan.']);
    }

    public function correct(Request $request, AttendanceSession $session, AttendanceRecorder $recorder)
    {
        Gate::authorize('correct', $session);
        $data = $request->validate(['student_id' => 'required|integer|exists:students,id', 'status' => ['required', Rule::in(Attendance::STATUSES)],
            'expected_status' => ['required', Rule::in(Attendance::STATUSES)], 'reason' => 'required|string|min:10|max:1000']);
        DB::transaction(function () use ($session, $data, $request, $recorder) {
            $session = AttendanceSession::lockForUpdate()->findOrFail($session->id);
            Gate::authorize('correct', $session);
            if (! $session->attendances()->where('student_id', $data['student_id'])->exists()) {
                throw ValidationException::withMessages(['student_id' => 'Koreksi hanya untuk presensi yang sudah tercatat.']);
            }
            $recorder->record($session, Student::findOrFail($data['student_id']), $data['status'], $request->user(), $data['reason'], $data['expected_status'], true);
        }, 3);

        return $request->expectsJson() ? response()->json(['success' => true, 'message' => 'Koreksi presensi dicatat.']) : back()->with('success', 'Koreksi presensi dicatat.');
    }
}
