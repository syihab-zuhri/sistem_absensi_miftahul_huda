<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AttendanceRecorder
{
    // Callers hold the session lock and wrap this operation in their transaction.
    public function record(AttendanceSession $session, Student $student, string $status, User $actor, ?string $reason = null, ?string $expectedStatus = null, bool $checkExpected = false): Attendance
    {
        if (! $session->students()->whereKey($student->id)->exists()) {
            throw ValidationException::withMessages(['attendances' => 'Siswa tidak termasuk peserta sesi ini.']);
        }
        $attendance = Attendance::where('schedule_id', $session->schedule_id)->where('student_id', $student->id)
            ->where('attendance_date', $session->lesson_date->toDateString())->lockForUpdate()->first();
        if ($checkExpected && $attendance?->status !== $expectedStatus) {
            throw ValidationException::withMessages(['expected_status' => 'Presensi telah berubah. Muat ulang halaman sebelum menyimpan.']);
        }
        if ($attendance && $attendance->status === $status) {
            return $attendance;
        }
        if ($attendance && mb_strlen(trim($reason ?? '')) < 10) {
            throw ValidationException::withMessages(['reason' => 'Alasan koreksi wajib diisi minimal 10 karakter.']);
        }
        $oldStatus = $attendance?->status;
        if (! $attendance) {
            $attendance = new Attendance;
            $attendance->schedule_id = $session->schedule_id;
            $attendance->student_id = $student->id;
            // Meeting date remains authoritative even for approved post-session leave.
            $attendance->timestamp = $session->lesson_date->copy()->setTimeFromTimeString(now()->format('H:i:s'));
            $attendance->attendance_date = $session->lesson_date->toDateString();
        }
        $attendance->attendance_session_id = $session->id;
        $attendance->status = $status;
        $attendance->save();
        $this->audit($session, $actor, $oldStatus === null ? 'attendance_recorded' : 'attendance_corrected', [
            'student_id' => $student->id, 'old_status' => $oldStatus, 'new_status' => $status, 'reason' => $reason,
        ]);

        return $attendance;
    }

    public function audit(AttendanceSession $session, User $actor, string $action, array $values = []): AttendanceAudit
    {
        return AttendanceAudit::create(array_merge($values, ['attendance_session_id' => $session->id, 'actor_id' => $actor->id, 'action' => $action, 'created_at' => now()]));
    }
}
