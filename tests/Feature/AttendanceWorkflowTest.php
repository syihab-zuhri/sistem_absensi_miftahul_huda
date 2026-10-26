<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SchoolFixtures;
use Tests\TestCase;

class AttendanceWorkflowTest extends TestCase
{
    use RefreshDatabase, SchoolFixtures;

    public function test_teacher_creates_a_unique_dated_session_from_own_schedule(): void
    {
        $session = $this->plannedSession();
        $this->assertDatabaseHas('attendance_sessions', ['id' => $session->id, 'status' => 'planned', 'teacher_id' => $this->teacher->id, 'lesson_date' => '2026-10-26']);
        $this->actingAs($this->teacher)->post('/attendance-sessions', ['schedule_id' => $this->schedule->id, 'lesson_date' => '2026-10-26'])->assertSessionHasErrors('lesson_date');
        $this->assertDatabaseCount('attendance_sessions', 1);
    }

    public function test_other_teachers_and_students_cannot_create_or_manage_sessions(): void
    {
        $this->actingAs($this->otherTeacher)->post('/attendance-sessions', ['schedule_id' => $this->schedule->id, 'lesson_date' => '2026-10-26'])->assertForbidden();
        $this->actingAs($this->student->user)->post('/attendance-sessions', ['schedule_id' => $this->schedule->id, 'lesson_date' => '2026-10-26'])->assertForbidden();
        $session = $this->plannedSession();
        $this->actingAs($this->otherTeacher)->post('/attendance-sessions/'.$session->id.'/open')->assertForbidden();
        $this->actingAs($this->otherTeacher)->postJson('/attendance-sessions/'.$session->id.'/attendances', [])->assertForbidden();
        $this->actingAs($this->student->user)->get('/attendance-sessions/'.$session->id)->assertForbidden();
    }

    public function test_session_date_must_be_in_active_year_and_teachers_cannot_assign_substitutes(): void
    {
        $this->actingAs($this->teacher)->post('/attendance-sessions', ['schedule_id' => $this->schedule->id, 'lesson_date' => '2027-07-01'])->assertSessionHasErrors('lesson_date');
        $this->actingAs($this->teacher)->post('/attendance-sessions', ['schedule_id' => $this->schedule->id, 'lesson_date' => '2026-10-26', 'teacher_id' => $this->otherTeacher->id])->assertForbidden();
    }

    public function test_opening_freezes_roster_and_saves_linked_attendance(): void
    {
        $session = $this->openSession();
        $this->student->update(['class_id' => '11-A']);
        $newStudent = $this->schoolStudent();
        $this->recordAttendance($session)->assertOk();
        $this->assertDatabaseHas('attendances', ['attendance_session_id' => $session->id, 'student_id' => $this->student->id, 'status' => 'hadir']);
        $this->actingAs($this->teacher)->getJson('/attendance-sessions/'.$session->id.'/roster')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $this->student->id);
        $this->actingAs($this->teacher)->postJson('/attendance-sessions/'.$session->id.'/attendances', ['attendances' => [['student_id' => $newStudent->id, 'status' => 'hadir']]])->assertUnprocessable();
    }

    public function test_close_requires_complete_roster_or_explicit_alpa_and_blocks_further_scans(): void
    {
        $session = $this->openSession();
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/close')->assertSessionHasErrors('attendances');
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/close', ['mark_unrecorded_absent' => true])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', ['attendance_session_id' => $session->id, 'status' => 'alpa']);
        $this->assertDatabaseHas('attendance_sessions', ['id' => $session->id, 'status' => 'closed']);
        $this->recordAttendance($session)->assertForbidden();
        $this->actingAs($this->teacher)->postJson('/absensi/save-session/'.$this->schedule->id, ['attendances' => [['student_id' => $this->student->id, 'status' => 'hadir']]])->assertForbidden();
    }

    public function test_cancelled_session_cannot_be_opened_or_used_by_legacy_scanner(): void
    {
        $session = $this->plannedSession();
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/cancel', ['reason' => 'Kegiatan sekolah diliburkan.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendance_sessions', ['id' => $session->id, 'status' => 'cancelled']);
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/open')->assertSessionHasErrors('session');
        $this->actingAs($this->teacher)->postJson('/absensi/save-session/'.$this->schedule->id, ['attendances' => [['student_id' => $this->student->id, 'status' => 'hadir']]])->assertForbidden();
    }

    public function test_planned_session_can_be_rescheduled_but_open_session_cannot(): void
    {
        $session = $this->plannedSession();
        $this->actingAs($this->teacher)->patch('/attendance-sessions/'.$session->id, ['lesson_date' => '2026-10-27', 'start_time' => '10:00', 'end_time' => '11:00', 'reason' => 'Pertemuan dipindah karena rapat.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendance_sessions', ['id' => $session->id, 'lesson_date' => '2026-10-27', 'start_time' => '10:00:00']);
        $this->actingAs($this->teacher)->postJson('/absensi/save-session/'.$this->schedule->id, ['attendances' => [['student_id' => $this->student->id, 'status' => 'hadir']]])->assertForbidden();
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/open')->assertSessionHasErrors('session');
        Carbon::setTestNow('2026-10-27 10:30:00');
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/open')->assertSessionHasNoErrors();
        $this->actingAs($this->teacher)->patch('/attendance-sessions/'.$session->id, ['lesson_date' => '2026-10-28', 'start_time' => '10:00', 'end_time' => '11:00', 'reason' => 'Pemindahan setelah sesi dibuka.'])->assertSessionHasErrors('session');
    }

    public function test_recording_is_audited_once_and_status_changes_require_reason(): void
    {
        $session = $this->openSession();
        $this->recordAttendance($session)->assertOk();
        $this->recordAttendance($session)->assertOk();
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendance_audits', ['attendance_session_id' => $session->id, 'actor_id' => $this->teacher->id, 'old_status' => null, 'new_status' => 'hadir']);
        $this->assertSame(1, AttendanceAudit::whereNotNull('student_id')->count());
        $this->recordAttendance($session, 'izin')->assertUnprocessable();
        $this->assertDatabaseHas('attendances', ['status' => 'hadir']);
    }

    public function test_closed_session_correction_requires_reason_and_rejects_stale_status(): void
    {
        $session = $this->openSession();
        $this->recordAttendance($session, 'alpa')->assertOk();
        $firstTime = Attendance::first()->timestamp;
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/close')->assertSessionHasNoErrors();
        Carbon::setTestNow('2026-10-26 12:00:00');
        $url = '/attendance-sessions/'.$session->id.'/corrections';
        $payload = ['student_id' => $this->student->id, 'status' => 'izin', 'expected_status' => 'alpa'];
        $this->actingAs($this->teacher)->postJson($url, $payload)->assertUnprocessable();
        $this->actingAs($this->teacher)->postJson($url, $payload + ['reason' => 'Surat izin sudah diperiksa.'])->assertOk();
        $this->assertDatabaseHas('attendance_audits', ['actor_id' => $this->teacher->id, 'old_status' => 'alpa', 'new_status' => 'izin', 'reason' => 'Surat izin sudah diperiksa.']);
        $this->assertSame((string) $firstTime, (string) Attendance::first()->timestamp);
        $this->actingAs($this->teacher)->postJson($url, $payload + ['reason' => 'Halaman lama mencoba menyimpan.'])->assertUnprocessable();
    }

    public function test_admin_can_assign_substitute_and_reports_follow_actual_session_teacher(): void
    {
        $this->actingAs($this->admin)->post('/attendance-sessions', ['schedule_id' => $this->schedule->id, 'lesson_date' => '2026-10-26', 'teacher_id' => $this->otherTeacher->id])->assertSessionHasNoErrors();
        $session = AttendanceSession::firstOrFail();
        $this->actingAs($this->otherTeacher)->post('/attendance-sessions/'.$session->id.'/open')->assertSessionHasNoErrors();
        $this->actingAs($this->otherTeacher)->postJson('/attendance-sessions/'.$session->id.'/attendances', ['attendances' => [['student_id' => $this->student->id, 'status' => 'hadir']]])->assertOk();
        $this->actingAs($this->teacher)->get('/attendance-sessions/'.$session->id)->assertForbidden();
        $this->actingAs($this->teacher)->get('/reports')->assertOk()->assertViewHas('attendances', fn ($rows) => $rows->total() === 0);
        $this->actingAs($this->otherTeacher)->get('/reports')->assertOk()->assertViewHas('attendances', fn ($rows) => $rows->total() === 1);
        $this->actingAs($this->teacher)->get('/reports/session/'.$this->schedule->id)->assertForbidden();
        $this->actingAs($this->otherTeacher)->get('/reports/session/'.$this->schedule->id)->assertOk();
    }

    public function test_workflow_pages_and_audit_history_are_scoped_to_teacher(): void
    {
        $session = $this->openSession();
        $this->recordAttendance($session)->assertOk();
        $this->actingAs($this->teacher)->get('/attendance-sessions')->assertOk()->assertSee('Sesi Presensi');
        $this->actingAs($this->teacher)->get('/attendance-sessions/'.$session->id)->assertOk()->assertSee($this->student->user->name);
        $this->actingAs($this->teacher)->get('/attendance-audits')->assertOk()->assertSee('Riwayat Perubahan')->assertSee($this->student->user->name);
        $this->actingAs($this->otherTeacher)->get('/attendance-audits')->assertOk()->assertDontSee($this->student->user->name);
    }

    public function test_historical_sessions_protect_students_schedules_and_attendance_from_deletion(): void
    {
        $session = $this->openSession();
        $this->recordAttendance($session)->assertOk();
        $this->actingAs($this->admin)->delete('/admin/reports/truncate', ['academic_year_id' => $this->year->id, 'start_date' => '2026-10-26', 'end_date' => '2026-10-26'])->assertSessionHasErrors('report');
        $this->assertDatabaseCount('attendances', 1);
        $this->actingAs($this->admin)->delete('/admin/schedules/'.$this->schedule->id)->assertSessionHasErrors('schedules');
        $this->actingAs($this->admin)->delete('/admin/students/'.$this->student->id)->assertSessionHasErrors('student');
    }

    public function test_stale_batch_rolls_back_other_student_changes_and_audit_entries(): void
    {
        $second = $this->schoolStudent();
        $session = $this->openSession();
        $this->recordAttendance($session)->assertOk();
        $auditCount = AttendanceAudit::count();
        $this->actingAs($this->teacher)->postJson('/attendance-sessions/'.$session->id.'/attendances', [
            'reason' => 'Koreksi dari halaman yang sudah lama.', 'attendances' => [
                ['student_id' => $second->id, 'status' => 'hadir', 'expected_status' => null],
                ['student_id' => $this->student->id, 'status' => 'izin', 'expected_status' => 'alpa'],
            ],
        ])->assertUnprocessable();
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('attendance_audits', $auditCount);
    }

    public function test_future_start_blocks_recording_and_early_alpa_even_when_session_is_prepared(): void
    {
        $session = $this->plannedSession(['start_time' => '10:00', 'end_time' => '11:00']);
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/open')->assertSessionHasNoErrors();
        $this->recordAttendance($session)->assertForbidden();
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/close', ['mark_unrecorded_absent' => true])->assertSessionHasErrors('session');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_saving_after_the_bell_is_allowed_within_grace_period_and_later_requires_reason(): void
    {
        $second = $this->schoolStudent();
        $session = $this->openSession();
        Carbon::setTestNow('2026-10-26 09:59:00');
        $this->recordAttendance($session)->assertOk();
        Carbon::setTestNow('2026-10-26 10:00:00');
        $payload = ['attendances' => [['student_id' => $second->id, 'status' => 'hadir']]];
        $this->actingAs($this->teacher)->postJson('/attendance-sessions/'.$session->id.'/attendances', $payload)->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->assertDatabaseMissing('attendances', ['student_id' => $second->id]);
        $this->actingAs($this->teacher)->postJson('/attendance-sessions/'.$session->id.'/attendances', $payload + ['reason' => 'Lupa menyimpan saat jam pelajaran.'])->assertOk();
        $this->assertDatabaseHas('attendance_audits', ['student_id' => $second->id, 'new_status' => 'hadir', 'reason' => 'Pencatatan susulan: Lupa menyimpan saat jam pelajaran.']);
        $this->actingAs($this->teacher)->get('/attendance-sessions/'.$session->id.'/scanner')->assertOk()->assertSee('sudah lewat');
    }

    public function test_grace_period_is_configurable(): void
    {
        config(['attendance.grace_minutes' => 0]);
        $session = $this->openSession();
        Carbon::setTestNow('2026-10-26 09:00:00');
        $this->recordAttendance($session)->assertUnprocessable()->assertJsonValidationErrors('reason');
    }

    public function test_session_not_opened_on_its_day_can_be_opened_and_recorded_late(): void
    {
        $session = $this->plannedSession();
        $future = $this->plannedSession(['lesson_date' => '2026-11-02']);
        Carbon::setTestNow('2026-10-27 13:00:00');
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$future->id.'/open')->assertSessionHasErrors('session');
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/open')->assertSessionHasNoErrors();
        $this->recordAttendance($session)->assertUnprocessable();
        $this->recordAttendance($session, 'hadir', ['reason' => 'Presensi kertas dimasukkan susulan.'])->assertOk();
        $this->assertDatabaseHas('attendances', ['attendance_session_id' => $session->id, 'attendance_date' => '2026-10-26', 'status' => 'hadir']);
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/close')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendance_sessions', ['id' => $session->id, 'status' => 'closed']);
    }

    public function test_legacy_scanner_save_after_the_bell_creates_the_session(): void
    {
        Carbon::setTestNow('2026-10-26 09:01:00');
        $this->actingAs($this->teacher)->postJson('/absensi/save-session/'.$this->schedule->id, ['attendances' => [['student_id' => $this->student->id, 'status' => 'hadir']]])->assertOk();
        $this->assertDatabaseHas('attendance_sessions', ['schedule_id' => $this->schedule->id, 'lesson_date' => '2026-10-26', 'status' => 'open']);
        $this->assertDatabaseHas('attendances', ['student_id' => $this->student->id, 'status' => 'hadir']);
    }

    public function test_scanner_prefers_the_running_lesson_over_one_in_its_grace_period(): void
    {
        $next = Schedule::create(['subject_id' => $this->schedule->subject_id, 'teacher_id' => $this->teacher->id, 'class_id' => '11-A',
            'day' => 'Senin', 'start_time' => '09:00:00', 'end_time' => '10:00:00', 'semester' => 'Ganjil', 'academic_year_id' => $this->year->id]);
        $this->openSession();
        Carbon::setTestNow('2026-10-26 09:30:00');
        $this->actingAs($this->teacher)->get('/scanner')->assertOk()->assertViewHas('activeSchedule', fn ($schedule) => $schedule->id === $next->id);
        Carbon::setTestNow('2026-10-26 10:30:00');
        $this->actingAs($this->teacher)->get('/scanner')->assertOk()->assertViewHas('activeSchedule', fn ($schedule) => $schedule->id === $next->id);
        Carbon::setTestNow('2026-10-26 11:00:00');
        $this->actingAs($this->teacher)->get('/scanner')->assertOk()->assertViewHas('activeSchedule', fn ($schedule) => $schedule === null)->assertSee('Akses Scanner Ditutup');
    }

    public function test_rescheduled_meeting_cannot_overlap_an_unmaterialized_weekly_class(): void
    {
        Schedule::create(['subject_id' => $this->schedule->subject_id, 'teacher_id' => $this->otherTeacher->id,
            'class_id' => '10-A', 'day' => 'Selasa', 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'semester' => 'Ganjil', 'academic_year_id' => $this->year->id]);
        $this->actingAs($this->teacher)->post('/attendance-sessions', ['schedule_id' => $this->schedule->id, 'lesson_date' => '2026-10-27'])->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('attendance_sessions', 0);
    }

    public function test_class_rename_updates_planned_sessions_while_open_sessions_keep_their_snapshot(): void
    {
        $open = $this->openSession();
        $planned = $this->plannedSession(['lesson_date' => '2026-11-02']);
        $class = Classroom::firstOrFail();
        $this->actingAs($this->admin)->put('/admin/classrooms/'.$class->id, ['name' => '10-B'])->assertSessionHasNoErrors();
        $this->assertSame('10-B', $planned->fresh()->class_id);
        $this->assertSame('10-A', $open->fresh()->class_id);
        $this->recordAttendance($open)->assertOk();
    }

    public function test_planned_sessions_and_substitute_teachers_are_protected_without_attendance(): void
    {
        $this->actingAs($this->admin)->post('/attendance-sessions', ['schedule_id' => $this->schedule->id, 'lesson_date' => '2026-10-26', 'teacher_id' => $this->otherTeacher->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->delete('/admin/schedules/'.$this->schedule->id)->assertSessionHasErrors('schedules');
        $this->actingAs($this->admin)->delete('/admin/users/'.$this->otherTeacher->id)->assertSessionHasErrors('user');
    }

    public function test_optional_empty_reschedule_fields_keep_current_times_and_teacher(): void
    {
        $session = $this->plannedSession();
        $this->actingAs($this->admin)->patch('/attendance-sessions/'.$session->id, [
            'lesson_date' => '2026-10-27', 'start_time' => '', 'end_time' => '', 'teacher_id' => '', 'reason' => 'Tanggal pertemuan dipindahkan.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendance_sessions', ['id' => $session->id, 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'teacher_id' => $this->teacher->id]);
    }

    public function test_rescheduled_meeting_can_return_to_its_previous_date_without_recreating_other_meetings(): void
    {
        $session = $this->plannedSession();
        foreach (['2026-10-27', '2026-10-26'] as $date) {
            $this->actingAs($this->teacher)->patch('/attendance-sessions/'.$session->id, [
                'lesson_date' => $date, 'reason' => 'Waktu pertemuan disesuaikan kembali.',
            ])->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->assertSame('2026-10-26', $session->fresh()->lesson_date->toDateString());
        $this->assertDatabaseCount('attendance_sessions', 1);
    }
}
