<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class LeaveRequests
{
    public function __construct(private AttendanceRecorder $recorder) {}

    public function submit(AttendanceSession $session, Student $student, User $actor, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($session, $student, $actor, $data) {
            $session = AttendanceSession::with('schedule.academicYear')->lockForUpdate()->findOrFail($session->id);
            abort_unless($student->status === 'aktif' && $session->accepts($student), 403);
            if ($session->status === 'cancelled' || ! $session->isCurrentTerm()) {
                throw ValidationException::withMessages(['attendance_session_id' => 'Sesi dibatalkan atau berada di luar semester aktif.']);
            }
            if ($session->leaveRequests()->where('student_id', $student->id)->whereIn('status', ['pending', 'approved'])->exists()) {
                throw ValidationException::withMessages(['attendance_session_id' => 'Sudah ada pengajuan yang menunggu atau disetujui untuk sesi ini.']);
            }
            $leave = $session->leaveRequests()->create(['student_id' => $student->id, 'type' => $data['type'], 'reason' => $data['reason'], 'attachment_path' => $data['attachment_path'] ?? null, 'status' => 'pending']);
            $this->recorder->audit($session, $actor, 'leave_submitted', ['student_id' => $student->id, 'leave_request_id' => $leave->id, 'reason' => $leave->reason, 'new_status' => 'pending']);

            return $leave;
        }, 3);
    }

    public function review(LeaveRequest $leave, User $actor, string $decision, string $note): void
    {
        DB::transaction(function () use ($leave, $actor, $decision, $note) {
            $session = AttendanceSession::lockForUpdate()->findOrFail($leave->attendance_session_id);
            $leave = LeaveRequest::with('student')->lockForUpdate()->findOrFail($leave->id);
            Gate::forUser($actor)->authorize('review', $leave);
            if ($leave->status !== 'pending' || $session->status === 'cancelled') {
                throw ValidationException::withMessages(['decision' => 'Pengajuan sudah diputuskan atau sesi dibatalkan.']);
            }
            if ($decision === 'approved') {
                if (! $session->accepts($leave->student)) {
                    throw ValidationException::withMessages(['decision' => 'Siswa tidak lagi termasuk peserta sesi ini.']);
                }
                if ($session->attendances()->where('student_id', $leave->student_id)->where('status', 'hadir')->exists()) {
                    throw ValidationException::withMessages(['decision' => 'Siswa sudah tercatat hadir. Periksa dan koreksi presensi sebelum menyetujui izin/sakit.']);
                }
                if (in_array($session->status, ['open', 'closed'], true)) {
                    $this->recorder->record($session, $leave->student, $leave->type, $actor, 'Persetujuan izin/sakit: '.$note);
                }
            }
            $leave->update(['status' => $decision, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_note' => $note]);
            $this->recorder->audit($session, $actor, 'leave_'.$decision, ['student_id' => $leave->student_id, 'leave_request_id' => $leave->id, 'old_status' => 'pending', 'new_status' => $decision, 'reason' => $note]);
        }, 3);
    }

    public function cancel(LeaveRequest $leave, User $actor): void
    {
        DB::transaction(function () use ($leave, $actor) {
            $session = AttendanceSession::lockForUpdate()->findOrFail($leave->attendance_session_id);
            $leave = LeaveRequest::lockForUpdate()->findOrFail($leave->id);
            Gate::forUser($actor)->authorize('cancel', $leave);
            if ($leave->status !== 'pending') {
                throw ValidationException::withMessages(['leave_request' => 'Hanya pengajuan yang masih menunggu yang dapat dibatalkan.']);
            }
            $leave->update(['status' => 'cancelled']);
            $this->recorder->audit($session, $actor, 'leave_cancelled', ['student_id' => $leave->student_id, 'leave_request_id' => $leave->id, 'old_status' => 'pending', 'new_status' => 'cancelled', 'reason' => 'Dibatalkan oleh siswa.']);
        }, 3);
    }
}
