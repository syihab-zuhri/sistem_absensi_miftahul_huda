<?php

namespace Tests\Feature;

use App\Models\{AcademicYear, Attendance, Classroom, Schedule, Student, Subject, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, DB, Notification};
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Passing tests in this file confirm the reported defects, not safe behavior. */
#[\PHPUnit\Framework\Attributes\Group('audit')]
class ProjectAuditReproductionTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $otherTeacher;
    private User $admin;
    private Student $student;
    private AcademicYear $oldYear;
    private AcademicYear $year;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-26 08:30:00');
        foreach (['admin', 'guru', 'siswa'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->admin = $this->makeUser('admin');
        $this->teacher = $this->makeUser('guru');
        $this->otherTeacher = $this->makeUser('guru');
        $this->oldYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => false]);
        $this->year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        Classroom::create(['name' => '10-A']);
        $this->subject = Subject::create(['name' => 'Matematika']);
        $this->student = Student::create([
            'user_id' => $this->makeUser('siswa')->id,
            'nisn' => '1234567890', 'class_id' => '10-A', 'status' => 'aktif',
        ]);
        Cache::forever('active_semester', 'Ganjil');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeUser(string $role): User
    {
        $n = User::count() + 1;
        $user = User::create(['name' => 'Audit '.$n, 'email' => 'audit'.$n.'@example.test', 'password' => 'password']);
        $user->assignRole($role);
        return $user;
    }

    private function schedule(array $extra = []): Schedule
    {
        return Schedule::create(array_merge([
            'subject_id' => $this->subject->id, 'teacher_id' => $this->teacher->id,
            'class_id' => '10-A', 'day' => 'Senin', 'start_time' => '08:00:00',
            'end_time' => '09:00:00', 'semester' => 'Ganjil', 'academic_year_id' => $this->year->id,
        ], $extra));
    }

    private function attendance(Schedule $schedule, string $status = 'alpa', string $time = '2026-10-26 08:15:00'): Attendance
    {
        return Attendance::create(['schedule_id' => $schedule->id, 'student_id' => $this->student->id, 'status' => $status, 'timestamp' => $time]);
    }

    public function test_reproduces_reading_another_teachers_roster(): void
    {
        $schedule = $this->schedule(['teacher_id' => $this->otherTeacher->id]);
        $this->actingAs($this->teacher)->getJson('/absensi/session-data/'.$schedule->id)
            ->assertOk()->assertJsonFragment(['nisn' => $this->student->nisn]);
    }

    public function test_reproduces_writing_another_teachers_attendance(): void
    {
        $schedule = $this->schedule(['teacher_id' => $this->otherTeacher->id]);
        $this->actingAs($this->teacher)->postJson('/absensi/save-session/'.$schedule->id, [
            'attendances' => [['student_id' => $this->student->id, 'status' => 'hadir']],
        ])->assertOk();
        $this->assertDatabaseHas('attendances', ['schedule_id' => $schedule->id, 'status' => 'hadir']);
    }

    public function test_reproduces_saving_closed_session_in_archived_year_and_inactive_semester(): void
    {
        $schedule = $this->schedule(['academic_year_id' => $this->oldYear->id, 'day' => 'Selasa', 'start_time' => '01:00:00', 'end_time' => '02:00:00', 'semester' => 'Genap']);
        $this->actingAs($this->teacher)->postJson('/absensi/save-session/'.$schedule->id, [
            'attendances' => [['student_id' => $this->student->id, 'status' => 'hadir']],
        ])->assertOk();
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_reproduces_scanner_choosing_archived_year(): void
    {
        $old = $this->schedule(['academic_year_id' => $this->oldYear->id]);
        $this->schedule();
        $this->actingAs($this->teacher)->get('/scanner')->assertOk()
            ->assertViewHas('activeSchedule', fn ($s) => $s->id === $old->id);
    }

    public function test_reproduces_student_supplied_html_reaching_scanner_sink(): void
    {
        $name = '<img src=x onerror="window.__auditXss=1">';
        $this->actingAs($this->student->user)->patch('/profile', ['name' => $name, 'email' => $this->student->user->email])->assertSessionHasNoErrors();
        $schedule = $this->schedule();
        $this->actingAs($this->teacher)->getJson('/absensi/session-data/'.$schedule->id)->assertOk()->assertJsonFragment(['name' => $name]);
    }

    public function test_reproduces_student_deleting_school_attendance_via_profile(): void
    {
        $schedule = $this->schedule();
        $attendance = $this->attendance($schedule);
        $this->actingAs($this->student->user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
    }

    public function test_reproduces_dashboard_ignoring_alpa(): void
    {
        $this->attendance($this->schedule());
        $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk()
            ->assertViewHas('todayStats', fn ($stats) => $stats['alfa'] === 0)
            ->assertViewHas('weeklyStats', fn ($stats) => array_sum($stats['alfa']) === 0);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_reproduces_session_pdf_including_old_dates_and_rendering_all_as_present(): void
    {
        $schedule = $this->schedule();
        $this->attendance($schedule, 'sakit', '2026-10-19 08:15:00');
        $this->attendance($schedule, 'alpa');
        $captured = null;
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('stream')->once()->andReturn(response('pdf'));
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()
            ->withArgs(function ($view, $data) use (&$captured) { $captured = $data; return $view === 'reports.attendance'; })
            ->andReturn($pdf);
        $this->actingAs($this->teacher)->get('/reports/session/'.$schedule->id)->assertOk();
        $this->assertCount(2, $captured['attendances']);
        $html = view('reports.attendance', $captured)->render();
        $this->assertSame(2, substr_count($html, '>Hadir</span>'));
        $this->assertStringNotContainsString('>sakit<', $html);
    }

    public function test_reproduces_overlapping_schedules_accepted(): void
    {
        $this->schedule();
        $this->actingAs($this->admin)->post('/admin/schedules', [
            'subject_id' => $this->subject->id, 'teacher_id' => $this->teacher->id,
            'class_id' => '10-A', 'day' => 'Senin', 'start_time' => '08:30', 'end_time' => '09:30', 'semester' => 'Ganjil',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('schedules', 2);
    }

    public function test_reproduces_old_year_blocking_identical_new_year_schedule(): void
    {
        $this->schedule(['academic_year_id' => $this->oldYear->id]);
        $this->actingAs($this->admin)->post('/admin/schedules', [
            'subject_id' => $this->subject->id, 'teacher_id' => $this->teacher->id,
            'class_id' => '10-A', 'day' => 'Senin', 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'semester' => 'Ganjil',
        ])->assertSessionHasErrors('class_id');
    }

    public function test_reproduces_editing_archived_schedule_reassigning_attendance_year(): void
    {
        $schedule = $this->schedule(['academic_year_id' => $this->oldYear->id]);
        $this->attendance($schedule);
        $this->actingAs($this->admin)->put('/admin/schedules/'.$schedule->id, [
            'subject_id' => $this->subject->id, 'teacher_id' => $this->teacher->id,
            'class_id' => '10-A', 'day' => 'Senin', 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'semester' => 'Ganjil',
        ])->assertSessionHasNoErrors();
        $this->assertSame($this->year->id, (int) $schedule->fresh()->academic_year_id);
        $this->assertSame($this->year->id, (int) Attendance::first()->schedule->academic_year_id);
    }

    public function test_reproduces_bulk_status_failing_without_current_year_history(): void
    {
        $this->actingAs($this->admin)->post('/admin/students/bulk-update-status', [
            'student_ids' => [$this->student->id], 'status' => 'lulus',
        ])->assertServerError();
        $this->assertSame('lulus', $this->student->fresh()->status);
        $this->assertDatabaseCount('student_class_histories', 0);
    }

    public function test_reproduces_renaming_class_leaving_existing_students_and_schedules_orphaned(): void
    {
        $schedule = $this->schedule();
        $classroom = Classroom::first();
        $this->actingAs($this->admin)->put('/admin/classrooms/'.$classroom->id, ['name' => '10-B'])->assertSessionHasNoErrors();
        $this->assertSame('10-A', $this->student->fresh()->class_id);
        $this->assertSame('10-A', $schedule->fresh()->class_id);
        $this->assertDatabaseMissing('classrooms', ['name' => '10-A']);
    }

    public function test_reproduces_semester_reset_deleting_all_academic_years(): void
    {
        $old = $this->schedule(['academic_year_id' => $this->oldYear->id]);
        $current = $this->schedule();
        $this->attendance($old);
        $this->attendance($current);
        $this->actingAs($this->admin)->delete('/admin/schedules/truncate', ['semester' => 'Ganjil', 'academic_year_id' => $this->year->id])->assertRedirect();
        $this->assertDatabaseCount('schedules', 0);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_reproduces_year_reset_route_shadowed_by_resource_route(): void
    {
        $this->schedule();
        $this->actingAs($this->admin)->delete('/admin/schedules/truncate-by-year', ['academic_year_id' => $this->year->id])->assertNotFound();
        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_reproduces_schedule_bulk_delete_calling_missing_method(): void
    {
        $this->actingAs($this->admin)->delete('/admin/schedules/bulk-delete', ['schedule_ids' => []])->assertServerError();
    }

    public function test_reproduces_personal_email_not_working_for_password_reset(): void
    {
        Notification::fake();
        $this->student->update(['personal_email' => 'pribadi@example.test']);
        $this->post('/forgot-password', ['email' => 'pribadi@example.test'])->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_reproduces_duplicate_attendance_rows_allowed_by_database(): void
    {
        $schedule = $this->schedule();
        $this->attendance($schedule);
        $this->attendance($schedule);
        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_reproduces_student_name_exported_as_excel_formula(): void
    {
        $this->student->user->update(['name' => '=1+1']);
        $this->attendance($this->schedule());
        $export = new \App\Exports\AttendancesExport(
            Attendance::with(['student.user', 'schedule.subject'])->get(),
            '26 Oktober 2026', '10-A', 'Matematika', '26 Oktober 2026', 'Audit', '2026/2027'
        );
        $xlsx = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'absensi-audit-formula-');
        file_put_contents($path, $xlsx);
        $zip = new \ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $this->assertStringContainsString('<f>1+1</f>', $xml);
    }

    public function test_reproduces_failed_academic_year_deactivation_leaking_transaction(): void
    {
        $before = DB::transactionLevel();
        $this->actingAs($this->admin)->put('/admin/academic-years/'.$this->year->id, ['name' => $this->year->name, 'is_active' => false])->assertSessionHas('error');
        $this->assertSame($before + 1, DB::transactionLevel());
        DB::rollBack(); // Do not let the reproduced leak contaminate the next test.
    }

    public function test_existing_role_boundaries_block_students_from_staff_endpoints(): void
    {
        $schedule = $this->schedule();
        $this->actingAs($this->student->user)->getJson('/absensi/session-data/'.$schedule->id)->assertForbidden();
        $this->actingAs($this->teacher)->get('/admin/students')->assertForbidden();
    }
}
