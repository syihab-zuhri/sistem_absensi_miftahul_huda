<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionMove;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AttendanceSessions
{
    public function __construct(private AttendanceRecorder $recorder) {}

    public function create(Schedule $schedule, array $data, User $actor): AttendanceSession
    {
        return DB::transaction(function () use ($schedule, $data, $actor) {
            AcademicYear::whereKey($schedule->academic_year_id)->lockForUpdate()->firstOrFail();
            $schedule = Schedule::with('academicYear')->lockForUpdate()->findOrFail($schedule->id);
            Gate::forUser($actor)->authorize('view', $schedule);
            $teacherId = (int) ($data['teacher_id'] ?? $schedule->teacher_id);
            abort_unless($actor->hasRole('admin') || $teacherId === (int) $actor->id, 403);
            $values = ['schedule_id' => $schedule->id, 'teacher_id' => $teacherId, 'class_id' => $schedule->class_id,
                'lesson_date' => $data['lesson_date'], 'start_time' => $this->time($data['start_time'] ?? $schedule->start_time), 'end_time' => $this->time($data['end_time'] ?? $schedule->end_time)];
            $this->validatePlan($schedule, $values);
            $session = AttendanceSession::create($values + ['status' => 'planned']);
            $this->recorder->audit($session, $actor, 'session_created', ['after_values' => $values]);

            return $session;
        }, 3);
    }

    private function time(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    private function validatePlan(Schedule $schedule, array $values, ?int $ignore = null): void
    {
        $year = $schedule->academicYear;
        $date = Carbon::parse($values['lesson_date'])->startOfDay();
        if (! $year || ! $year->is_active || $schedule->semester !== $year->active_semester
            || ($year->start_date && $date->lt($year->start_date)) || ($year->end_date && $date->gt($year->end_date))) {
            throw ValidationException::withMessages(['lesson_date' => 'Tanggal harus berada dalam tahun ajaran dan semester yang aktif.']);
        }
        if (! User::findOrFail($values['teacher_id'])->hasAnyRole(['guru', 'admin'])) {
            throw ValidationException::withMessages(['teacher_id' => 'Pengajar harus memiliki peran guru atau admin.']);
        }
        if ($values['end_time'] <= $values['start_time']) {
            throw ValidationException::withMessages(['end_time' => 'Waktu selesai harus sesudah waktu mulai.']);
        }
        if (AttendanceSession::where('schedule_id', $schedule->id)->whereDate('lesson_date', $values['lesson_date'])->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists()) {
            throw ValidationException::withMessages(['lesson_date' => 'Sesi untuk jadwal dan tanggal ini sudah ada.']);
        }
        if ($schedule->sessionMoves()->whereDate('original_date', $values['lesson_date'])
            ->when($ignore, fn ($q) => $q->where('attendance_session_id', '!=', $ignore))->exists()) {
            throw ValidationException::withMessages(['lesson_date' => 'Pertemuan pada tanggal ini sudah dipindahkan. Ubah sesi pertemuan yang sama bila ingin mengembalikannya.']);
        }
        if (AttendanceSession::whereDate('lesson_date', $values['lesson_date'])->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $values['end_time'])->where('end_time', '>', $values['start_time'])
            ->where(fn ($q) => $q->where('teacher_id', $values['teacher_id'])->orWhere('class_id', $values['class_id']))
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists()) {
            throw ValidationException::withMessages(['start_time' => 'Waktu sesi bertabrakan dengan sesi kelas atau pengajar yang lain.']);
        }
        if (Schedule::where('academic_year_id', $schedule->academic_year_id)->where('semester', $schedule->semester)
            ->whereKeyNot($schedule->id)->where('day', $date->locale('id')->dayName)
            ->where('start_time', '<', $values['end_time'])->where('end_time', '>', $values['start_time'])
            ->where(fn ($q) => $q->where('teacher_id', $values['teacher_id'])->orWhere('class_id', $values['class_id']))
            ->whereDoesntHave('sessions', fn ($q) => $q->whereDate('lesson_date', $values['lesson_date']))
            ->whereDoesntHave('sessionMoves', fn ($q) => $q->whereDate('original_date', $values['lesson_date']))->exists()) {
            throw ValidationException::withMessages(['start_time' => 'Waktu sesi bertabrakan dengan jadwal mingguan kelas atau pengajar.']);
        }
    }

    public function open(AttendanceSession $session, User $actor): AttendanceSession
    {
        return DB::transaction(function () use ($session, $actor) {
            $session = AttendanceSession::with('schedule.academicYear')->lockForUpdate()->findOrFail($session->id);
            Gate::forUser($actor)->authorize('manage', $session);
            // Past meetings may be opened late; recording them then requires a reason.
            if ($session->status !== 'planned' || ! $session->isCurrentTerm() || $session->lesson_date->isFuture()) {
                throw ValidationException::withMessages(['session' => 'Sesi hanya dapat dibuka mulai hari pertemuan dan pada semester aktif.']);
            }
            $ids = Student::where('class_id', $session->class_id)->active()->pluck('id')
                ->merge(Attendance::where('schedule_id', $session->schedule_id)->whereDate('attendance_date', $session->lesson_date)->pluck('student_id'))->unique();
            $session->students()->sync($ids);
            Attendance::where('schedule_id', $session->schedule_id)->whereDate('attendance_date', $session->lesson_date)->whereNull('attendance_session_id')->update(['attendance_session_id' => $session->id]);
            $session->update(['status' => 'open', 'opened_at' => now()]);
            $this->recorder->audit($session, $actor, 'session_opened', ['old_status' => 'planned', 'new_status' => 'open']);
            foreach ($session->leaveRequests()->with(['student', 'reviewer'])->where('status', 'approved')->get() as $leave) {
                if ($ids->contains($leave->student_id)) {
                    $this->recorder->record($session, $leave->student, $leave->type, $leave->reviewer, 'Persetujuan izin/sakit: '.$leave->review_note);
                } else {
                    $note = 'Pengajuan dibatalkan karena siswa tidak termasuk peserta saat sesi dibuka.';
                    $leave->update(['status' => 'cancelled', 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_note' => $note]);
                    $this->recorder->audit($session, $actor, 'leave_cancelled', ['student_id' => $leave->student_id, 'leave_request_id' => $leave->id, 'old_status' => 'approved', 'new_status' => 'cancelled', 'reason' => $note]);
                }
            }

            return $session;
        }, 3);
    }

    public function close(AttendanceSession $session, User $actor, bool $markAbsent): void
    {
        DB::transaction(function () use ($session, $actor, $markAbsent) {
            $session = AttendanceSession::lockForUpdate()->findOrFail($session->id);
            Gate::forUser($actor)->authorize('manage', $session);
            if ($session->status !== 'open' || $session->lesson_date->isFuture() || ($session->lesson_date->isToday() && $session->start_time > now()->format('H:i:s'))) {
                throw ValidationException::withMessages(['session' => 'Hanya sesi terbuka yang dapat ditutup.']);
            }
            $missing = $session->students()->whereDoesntHave('attendances', fn ($q) => $q->where('attendance_session_id', $session->id))->get();
            if ($missing->isNotEmpty()) {
                if (! $markAbsent || $session->leaveRequests()->where('status', 'pending')->whereIn('student_id', $missing->modelKeys())->exists()) {
                    throw ValidationException::withMessages(['attendances' => 'Masih ada peserta belum tercatat. Lengkapi presensi dan putuskan izin/sakit, atau pilih pengisian alpa secara eksplisit.']);
                }
                foreach ($missing as $student) {
                    $this->recorder->record($session, $student, 'alpa', $actor, 'Pengisian alpa dipilih saat menutup sesi.');
                }
            }
            $session->update(['status' => 'closed', 'closed_at' => now()]);
            $this->recorder->audit($session, $actor, 'session_closed', ['old_status' => 'open', 'new_status' => 'closed']);
        }, 3);
    }

    public function cancel(AttendanceSession $session, User $actor, string $reason): void
    {
        DB::transaction(function () use ($session, $actor, $reason) {
            $session = AttendanceSession::lockForUpdate()->findOrFail($session->id);
            Gate::forUser($actor)->authorize('manage', $session);
            if (! in_array($session->status, ['planned', 'open'], true) || $session->attendances()->exists()) {
                throw ValidationException::withMessages(['session' => 'Sesi yang sudah ditutup atau memiliki presensi tidak dapat dibatalkan.']);
            }
            $old = $session->status;
            $session->update(['status' => 'cancelled', 'cancellation_reason' => $reason]);
            foreach ($session->leaveRequests()->whereIn('status', ['pending', 'approved'])->get() as $leave) {
                $leave->update(['status' => 'cancelled', 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_note' => 'Sesi dibatalkan: '.$reason]);
                $this->recorder->audit($session, $actor, 'leave_cancelled', ['student_id' => $leave->student_id, 'leave_request_id' => $leave->id, 'reason' => 'Sesi dibatalkan: '.$reason]);
            }
            $this->recorder->audit($session, $actor, 'session_cancelled', ['old_status' => $old, 'new_status' => 'cancelled', 'reason' => $reason]);
        }, 3);
    }

    public function reschedule(AttendanceSession $session, array $data, User $actor): void
    {
        DB::transaction(function () use ($session, $data, $actor) {
            AcademicYear::whereKey($session->schedule->academic_year_id)->lockForUpdate()->firstOrFail();
            $schedule = Schedule::with('academicYear')->lockForUpdate()->findOrFail($session->schedule_id);
            $session = AttendanceSession::lockForUpdate()->findOrFail($session->id);
            Gate::forUser($actor)->authorize('manage', $session);
            if ($session->status !== 'planned' || $session->leaveRequests()->whereIn('status', ['pending', 'approved'])->exists()) {
                throw ValidationException::withMessages(['session' => 'Perubahan hanya untuk sesi terencana tanpa pengajuan aktif. Batalkan pengajuan/sesi terlebih dahulu bila diperlukan.']);
            }
            $before = $session->only(['lesson_date', 'start_time', 'end_time', 'teacher_id', 'class_id']);
            $before['lesson_date'] = $session->lesson_date->toDateString();
            $values = array_merge($before, array_filter(array_intersect_key($data, $before), fn ($value) => $value !== null));
            $values['start_time'] = $this->time($values['start_time']);
            $values['end_time'] = $this->time($values['end_time']);
            abort_unless($actor->hasRole('admin') || (int) $values['teacher_id'] === (int) $actor->id, 403);
            $this->validatePlan($schedule, $values, $session->id);
            $session->update($values);
            // Reserve the previous day so the weekly scanner cannot recreate the moved meeting.
            if ($before['lesson_date'] !== $values['lesson_date']) {
                AttendanceSessionMove::where('attendance_session_id', $session->id)->whereDate('original_date', $values['lesson_date'])->delete();
                AttendanceSessionMove::create(['schedule_id' => $schedule->id, 'attendance_session_id' => $session->id, 'original_date' => $before['lesson_date']]);
            }
            $this->recorder->audit($session, $actor, 'session_rescheduled', ['before_values' => $before, 'after_values' => $values, 'reason' => $data['reason']]);
        }, 3);
    }
}
