<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SchoolFixtures;
use Tests\TestCase;

class LeaveRequestWorkflowTest extends TestCase
{
    use RefreshDatabase, SchoolFixtures;

    private function submit($session, array $extra = [])
    {
        return $this->actingAs($this->student->user)->post('/leave-requests', array_merge([
            'attendance_session_id' => $session->id, 'type' => 'sakit', 'reason' => 'Sedang berobat dan perlu istirahat.',
        ], $extra));
    }

    private function approve($request)
    {
        return $this->actingAs($this->teacher)->post('/leave-requests/'.$request->id.'/review', ['decision' => 'approved', 'review_note' => 'Surat dan keterangan sudah diperiksa.']);
    }

    public function test_student_submits_private_attachment_and_only_owner_or_authorized_reviewer_can_download(): void
    {
        $session = $this->plannedSession();
        $this->submit($session, ['attachment' => UploadedFile::fake()->create('surat.pdf', 20, 'application/pdf')])->assertRedirect()->assertSessionHasNoErrors();
        $leave = LeaveRequest::firstOrFail();
        $this->actingAs($this->student->user)->get('/leave-requests')->assertOk()->assertViewHas('sessions', fn ($sessions) => $sessions->isEmpty());
        Storage::disk('local')->assertExists($leave->attachment_path);
        $this->actingAs($this->student->user)->get('/leave-requests/'.$leave->id.'/attachment')->assertOk();
        $this->actingAs($this->teacher)->get('/leave-requests/'.$leave->id.'/attachment')->assertOk();
        $this->actingAs($this->otherTeacher)->get('/leave-requests/'.$leave->id.'/attachment')->assertForbidden();
        $other = $this->schoolStudent();
        $this->actingAs($other->user)->get('/leave-requests/'.$leave->id.'/attachment')->assertForbidden();
        $this->actingAs($other->user)->get('/leave-requests')->assertOk()->assertDontSee('Sedang berobat dan perlu istirahat.');
    }

    public function test_student_cannot_submit_for_another_class_or_spoof_student_and_review_fields(): void
    {
        $session = $this->plannedSession();
        $outside = $this->schoolStudent('11-A');
        $this->actingAs($outside->user)->post('/leave-requests', ['attendance_session_id' => $session->id, 'type' => 'izin', 'reason' => 'Mencoba mengakses kelas lain.'])->assertForbidden();
        $this->submit($session, ['student_id' => $outside->id, 'status' => 'approved', 'reviewed_by' => $this->admin->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('leave_requests', ['student_id' => $this->student->id, 'status' => 'pending', 'reviewed_by' => null]);
    }

    public function test_submission_validates_reason_type_upload_and_duplicate_active_request(): void
    {
        $session = $this->plannedSession();
        $this->submit($session, ['reason' => 'x', 'type' => 'hadir', 'attachment' => UploadedFile::fake()->create('script.php', 1, 'application/x-php')])->assertSessionHasErrors(['reason', 'type', 'attachment']);
        $this->submit($session)->assertSessionHasNoErrors();
        $this->submit($session)->assertSessionHasErrors('attendance_session_id');
        $this->assertDatabaseCount('leave_requests', 1);
    }

    public function test_other_teachers_and_students_cannot_review_and_decision_cannot_be_repeated(): void
    {
        $session = $this->openSession();
        $this->submit($session)->assertSessionHasNoErrors();
        $leave = LeaveRequest::firstOrFail();
        foreach ([$this->otherTeacher, $this->student->user] as $user) {
            $this->actingAs($user)->post('/leave-requests/'.$leave->id.'/review', ['decision' => 'approved', 'review_note' => 'Keterangan telah diperiksa.'])->assertForbidden();
        }
        $this->approve($leave)->assertSessionHasNoErrors();
        $this->approve($leave)->assertSessionHasErrors('decision');
        $this->assertDatabaseHas('attendances', ['attendance_session_id' => $session->id, 'student_id' => $this->student->id, 'status' => 'sakit']);
        $this->assertDatabaseHas('attendance_audits', ['actor_id' => $this->teacher->id, 'new_status' => 'sakit']);
    }

    public function test_approved_future_leave_is_applied_when_session_opens(): void
    {
        $session = $this->plannedSession(['lesson_date' => '2026-11-02']);
        $this->submit($session)->assertSessionHasNoErrors();
        $this->approve(LeaveRequest::firstOrFail())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('attendances', 0);
        Carbon::setTestNow('2026-11-02 08:30:00');
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/open')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', ['attendance_session_id' => $session->id, 'attendance_date' => '2026-11-02', 'status' => 'sakit']);
    }

    public function test_leave_approval_corrects_closed_alpa_and_keeps_original_attendance_time(): void
    {
        $session = $this->openSession();
        $this->recordAttendance($session, 'alpa')->assertOk();
        $time = (string) Attendance::first()->timestamp;
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/close')->assertSessionHasNoErrors();
        Carbon::setTestNow('2026-10-26 12:00:00');
        $this->submit($session)->assertSessionHasNoErrors();
        $this->approve(LeaveRequest::firstOrFail())->assertSessionHasNoErrors();
        $this->assertSame('sakit', Attendance::first()->status);
        $this->assertSame($time, (string) Attendance::first()->timestamp);
        $this->assertDatabaseHas('attendance_audits', ['old_status' => 'alpa', 'new_status' => 'sakit']);
    }

    public function test_approval_cannot_overwrite_recorded_presence_and_rolls_back_decision(): void
    {
        $session = $this->openSession();
        $this->recordAttendance($session)->assertOk();
        $this->submit($session)->assertSessionHasNoErrors();
        $leave = LeaveRequest::firstOrFail();
        $this->approve($leave)->assertSessionHasErrors('decision');
        $this->assertSame('pending', $leave->fresh()->status);
        $this->assertSame('hadir', Attendance::first()->status);
    }

    public function test_rejected_or_cancelled_request_can_be_resubmitted_and_all_decisions_remain_in_history(): void
    {
        $session = $this->plannedSession();
        $this->submit($session)->assertSessionHasNoErrors();
        $first = LeaveRequest::firstOrFail();
        $this->actingAs($this->teacher)->post('/leave-requests/'.$first->id.'/review', ['decision' => 'rejected', 'review_note' => 'Keterangan belum lengkap.'])->assertSessionHasNoErrors();
        $this->submit($session)->assertSessionHasNoErrors();
        $second = LeaveRequest::latest('id')->firstOrFail();
        $this->actingAs($this->student->user)->post('/leave-requests/'.$second->id.'/cancel')->assertSessionHasNoErrors();
        $this->submit($session)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('leave_requests', 3);
        $this->assertDatabaseHas('leave_requests', ['id' => $first->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('leave_requests', ['id' => $second->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('attendance_audits', ['action' => 'leave_rejected']);
    }

    public function test_pending_request_blocks_automatic_alpa_and_cancelled_session_blocks_submission(): void
    {
        $session = $this->openSession();
        $this->submit($session)->assertSessionHasNoErrors();
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/close', ['mark_unrecorded_absent' => true])->assertSessionHasErrors('attendances');
        $this->assertDatabaseCount('attendances', 0);
        $future = $this->plannedSession(['lesson_date' => '2026-11-02']);
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$future->id.'/cancel', ['reason' => 'Sekolah libur kegiatan nasional.'])->assertSessionHasNoErrors();
        $this->submit($future)->assertSessionHasErrors('attendance_session_id');
    }

    public function test_student_cannot_cancel_approved_request_and_reviewer_must_supply_note(): void
    {
        $session = $this->plannedSession();
        $this->submit($session)->assertSessionHasNoErrors();
        $leave = LeaveRequest::firstOrFail();
        $this->actingAs($this->teacher)->post('/leave-requests/'.$leave->id.'/review', ['decision' => 'approved'])->assertSessionHasErrors('review_note');
        $this->approve($leave)->assertSessionHasNoErrors();
        $this->actingAs($this->student->user)->post('/leave-requests/'.$leave->id.'/cancel')->assertSessionHasErrors('leave_request');
    }

    public function test_failed_duplicate_upload_is_removed_and_pending_student_history_is_protected(): void
    {
        $session = $this->plannedSession();
        $this->submit($session, ['attachment' => UploadedFile::fake()->create('first.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->submit($session, ['attachment' => UploadedFile::fake()->create('second.pdf', 10, 'application/pdf')])->assertSessionHasErrors('attendance_session_id');
        $this->assertCount(1, Storage::disk('local')->files('leave-attachments'));
        $this->actingAs($this->admin)->delete('/admin/students/'.$this->student->id)->assertSessionHasErrors('student');
        $this->assertDatabaseCount('students', 1);
    }

    public function test_pending_request_blocks_rescheduling_and_cancelling_session_retains_request_history(): void
    {
        $session = $this->plannedSession();
        $this->submit($session)->assertSessionHasNoErrors();
        $leave = LeaveRequest::firstOrFail();
        $this->actingAs($this->teacher)->patch('/attendance-sessions/'.$session->id, ['lesson_date' => '2026-11-02', 'reason' => 'Dipindahkan karena acara sekolah.'])->assertSessionHasErrors('session');
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/cancel', ['reason' => 'Pertemuan diganti kegiatan sekolah.'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $leave->fresh()->status);
        $this->assertDatabaseHas('attendance_audits', ['action' => 'leave_cancelled', 'leave_request_id' => $leave->id]);
        $this->approve($leave)->assertSessionHasErrors('decision');
    }

    public function test_other_student_cannot_cancel_request_and_new_classmate_cannot_apply_to_frozen_roster(): void
    {
        $session = $this->openSession();
        $this->submit($session)->assertSessionHasNoErrors();
        $leave = LeaveRequest::firstOrFail();
        $other = $this->schoolStudent();
        $this->actingAs($other->user)->post('/leave-requests/'.$leave->id.'/cancel')->assertForbidden();
        $this->actingAs($other->user)->post('/leave-requests', ['attendance_session_id' => $session->id, 'type' => 'izin', 'reason' => 'Belum menjadi peserta saat sesi dibuka.'])->assertForbidden();
    }

    public function test_approved_future_request_is_cancelled_with_history_if_student_leaves_class_before_opening(): void
    {
        $session = $this->plannedSession(['lesson_date' => '2026-11-02']);
        $this->submit($session)->assertSessionHasNoErrors();
        $leave = LeaveRequest::firstOrFail();
        $this->approve($leave)->assertSessionHasNoErrors();
        $this->student->update(['class_id' => '11-A']);
        Carbon::setTestNow('2026-11-02 08:30:00');
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/open')->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $leave->fresh()->status);
        $this->assertDatabaseCount('attendances', 0);
        $this->assertDatabaseHas('attendance_audits', ['action' => 'leave_cancelled', 'leave_request_id' => $leave->id, 'old_status' => 'approved', 'new_status' => 'cancelled']);
    }
}
