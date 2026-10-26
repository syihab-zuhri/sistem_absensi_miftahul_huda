<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAttendanceRequest;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Schedule;
use App\Models\Student;
use App\Services\AttendanceRecorder;
use App\Services\AttendanceSessions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    public function index()
    {
        $year = AcademicYear::active()->first();
        $activeSemester = $year?->active_semester ?? 'Ganjil';
        $time = now()->format('H:i:s');
        // Only lessons inside their recording window are offered here; late recording starts from the session page.
        $sessions = AttendanceSession::with('schedule.subject')->visibleTo(auth()->user())->whereDate('lesson_date', today())
            ->where('status', 'open')->get()->filter(fn ($session) => ! $session->isLate() && Gate::allows('record', $session));
        $schedules = $year ? Schedule::with(['subject', 'academicYear'])
            ->where('academic_year_id', $year->id)->where('teacher_id', auth()->id())
            ->where('day', now()->locale('id')->dayName)->where('start_time', '<=', $time)->where('semester', $activeSemester)
            ->get()->filter(fn ($schedule) => now()->lt($schedule->lateAfter(today())) && Gate::allows('record', $schedule)) : collect();
        // Prefer the lesson that is still running over one that only remains within its grace period.
        $activeSession = $sessions->first(fn ($session) => $session->end_time > $time);
        $activeSchedule = $activeSession?->schedule ?? $schedules->first(fn ($schedule) => $schedule->end_time > $time);
        if (! $activeSchedule) {
            $activeSession = $sessions->first();
            $activeSchedule = $activeSession?->schedule ?? $schedules->first();
        }
        $lateAfter = $activeSession?->lateAfter() ?? $activeSchedule?->lateAfter(today());

        return view('scanner', compact('activeSchedule', 'activeSemester', 'activeSession', 'lateAfter'));
    }

    public function getSessionData($schedule_id)
    {
        $schedule = Schedule::findOrFail($schedule_id);
        $session = AttendanceSession::where('schedule_id', $schedule->id)->whereDate('lesson_date', today())->first();
        if ($session) {
            return app(AttendanceSessionController::class)->roster($session);
        }
        Gate::authorize('view', $schedule);
        $attendances = Attendance::where('schedule_id', $schedule->id)->where('attendance_date', today()->toDateString())->get()->keyBy('student_id');
        $students = Student::with('user')->where('class_id', $schedule->class_id)->where('status', 'aktif')->get()->sortBy('user.name')->values();

        return response()->json($students->map(fn ($student) => [
            'id' => $student->id, 'nisn' => $student->nisn, 'name' => $student->user?->name ?? 'User Terhapus',
            'status' => $attendances->get($student->id)?->status ?? '',
        ]));
    }

    public function saveSession(SaveAttendanceRequest $request, $schedule_id, AttendanceSessions $sessions)
    {
        $schedule = Schedule::with('academicYear')->findOrFail($schedule_id);
        Gate::authorize('record', $schedule);

        return DB::transaction(function () use ($request, $schedule, $sessions) {
            AcademicYear::whereKey($schedule->academic_year_id)->lockForUpdate()->firstOrFail();
            $locked = Schedule::with('academicYear')->lockForUpdate()->findOrFail($schedule->id);
            Gate::authorize('record', $locked);
            $session = AttendanceSession::where('schedule_id', $locked->id)->whereDate('lesson_date', today())->first()
                ?? $sessions->create($locked, ['lesson_date' => today()->toDateString()], $request->user());
            if ($session->status === 'planned') {
                $session = $sessions->open($session, $request->user());
            }

            return app(AttendanceSessionController::class)->save($request, $session, app(AttendanceRecorder::class));
        }, 3);
    }
}
