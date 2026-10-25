<?php

namespace Tests\Feature;

use App\Imports\StudentsImport;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditRegressionTest extends TestCase
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
        $this->withoutVite();
        Storage::fake('public');
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
        $this->student = Student::create(['user_id' => $this->makeUser('siswa')->id, 'nisn' => '1234567890', 'class_id' => '10-A', 'status' => 'aktif']);
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
        return Schedule::create(array_merge(['subject_id' => $this->subject->id, 'teacher_id' => $this->teacher->id, 'class_id' => '10-A', 'day' => 'Senin', 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'semester' => 'Ganjil', 'academic_year_id' => $this->year->id], $extra));
    }

    private function attendance(Schedule $schedule, string $status = 'alpa', string $time = '2026-10-26 08:15:00'): Attendance
    {
        return Attendance::create(['schedule_id' => $schedule->id, 'student_id' => $this->student->id, 'status' => $status, 'timestamp' => $time]);
    }

    private function scheduleData(array $extra = []): array
    {
        return array_merge(['subject_id' => $this->subject->id, 'teacher_id' => $this->teacher->id, 'class_id' => '10-A', 'day' => 'Senin', 'start_time' => '08:00', 'end_time' => '09:00', 'semester' => 'Ganjil', 'academic_year_id' => $this->year->id], $extra);
    }

    public function test_teacher_cannot_read_another_teachers_roster(): void
    {
        $schedule = $this->schedule(['teacher_id' => $this->otherTeacher->id]);
        $this->actingAs($this->teacher)->getJson('/absensi/session-data/'.$schedule->id)->assertForbidden();
    }

    public function test_teacher_cannot_write_another_teachers_attendance(): void
    {
        $schedule = $this->schedule(['teacher_id' => $this->otherTeacher->id]);
        $this->actingAs($this->teacher)->postJson('/absensi/save-session/'.$schedule->id, ['attendances' => [['student_id' => $this->student->id, 'status' => 'hadir']]])->assertForbidden();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_closed_sessions_cannot_be_saved(): void
    {
        $schedule = $this->schedule(['end_time' => '08:30:00']);
        $this->actingAs($this->teacher)->postJson('/absensi/save-session/'.$schedule->id, ['attendances' => [['student_id' => $this->student->id, 'status' => 'hadir']]])->assertForbidden();
    }

    public function test_inactive_year_and_semester_cannot_be_saved(): void
    {
        foreach ([['academic_year_id' => $this->oldYear->id], ['semester' => 'Genap'], ['day' => 'Selasa']] as $extra) {
            $schedule = $this->schedule($extra);
            $this->actingAs($this->teacher)->postJson('/absensi/save-session/'.$schedule->id, ['attendances' => [['student_id' => $this->student->id, 'status' => 'hadir']]])->assertForbidden();
        }
    }

    public function test_scanner_uses_active_academic_year(): void
    {
        $this->schedule(['academic_year_id' => $this->oldYear->id]);
        $current = $this->schedule();
        $this->actingAs($this->teacher)->get('/scanner')->assertOk()->assertViewHas('activeSchedule', fn ($s) => $s->id === $current->id);
    }

    public function test_invalid_student_causes_entire_batch_to_be_rejected(): void
    {
        $outside = Student::create(['user_id' => $this->makeUser('siswa')->id, 'nisn' => '0000000002', 'class_id' => '11-A', 'status' => 'aktif']);
        $schedule = $this->schedule();
        $this->actingAs($this->teacher)->postJson('/absensi/save-session/'.$schedule->id, ['attendances' => [['student_id' => $this->student->id, 'status' => 'hadir'], ['student_id' => $outside->id, 'status' => 'hadir']]])->assertUnprocessable();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_saving_twice_preserves_first_scan_timestamp(): void
    {
        $schedule = $this->schedule();
        $attendance = $this->attendance($schedule, 'hadir');
        $this->actingAs($this->teacher)->postJson('/absensi/save-session/'.$schedule->id, ['attendances' => [['student_id' => $this->student->id, 'status' => 'izin']], 'reason' => 'Surat izin telah diverifikasi.'])->assertOk();
        $this->assertDatabaseCount('attendances', 1);
        $this->assertSame('2026-10-26 08:15:00', (string) $attendance->fresh()->timestamp);
    }

    public function test_student_cannot_delete_school_attendance_via_profile(): void
    {
        $attendance = $this->attendance($this->schedule());
        $this->actingAs($this->student->user)->delete('/profile', ['password' => 'password'])->assertForbidden();
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
    }

    public function test_dashboard_counts_alpa(): void
    {
        $this->attendance($this->schedule());
        $this->actingAs($this->admin)->get('/admin/dashboard')->assertOk()->assertViewHas('todayStats', fn ($stats) => $stats['alpa'] === 1)->assertViewHas('weeklyStats', fn ($stats) => array_sum($stats['alpa']) === 1);
    }

    public function test_session_pdf_contains_only_requested_date_and_actual_status(): void
    {
        $schedule = $this->schedule();
        $this->attendance($schedule, 'sakit', '2026-10-19 08:15:00');
        $this->attendance($schedule, 'alpa');
        $data = null;
        View::composer('reports.attendance', function ($view) use (&$data) {
            $data = $view->getData();
        });
        $response = $this->actingAs($this->teacher)->get('/reports/session/'.$schedule->id);
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertCount(1, $data['attendances']);
        $document = new \DOMDocument;
        $document->loadHTML(view('reports.attendance', $data)->render(), LIBXML_NONET);
        $text = $document->getElementsByTagName('body')->item(0)->textContent;
        $this->assertStringContainsString('ALPA', strtoupper($text));
        $this->assertStringNotContainsString('SAKIT', strtoupper($text));
        $this->assertStringContainsString('0 Siswa', $text);
    }

    public function test_overlapping_class_schedule_is_rejected(): void
    {
        $this->schedule();
        $this->actingAs($this->admin)->post('/admin/schedules', $this->scheduleData(['teacher_id' => $this->otherTeacher->id, 'start_time' => '08:30', 'end_time' => '09:30']))->assertSessionHasErrors('class_id');
        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_teacher_cannot_have_simultaneous_classes(): void
    {
        Classroom::create(['name' => '11-A']);
        $this->schedule();
        $this->actingAs($this->admin)->post('/admin/schedules', $this->scheduleData(['class_id' => '11-A', 'start_time' => '08:30', 'end_time' => '09:30']))->assertSessionHasErrors('teacher_id');
    }

    public function test_old_year_does_not_block_new_year_schedule(): void
    {
        $this->schedule(['academic_year_id' => $this->oldYear->id]);
        $this->actingAs($this->admin)->post('/admin/schedules', $this->scheduleData(['start_time' => '08:00:00', 'end_time' => '09:00:00']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('schedules', 2);
    }

    public function test_editing_archived_schedule_never_moves_its_attendance_year(): void
    {
        $schedule = $this->schedule(['academic_year_id' => $this->oldYear->id]);
        $this->attendance($schedule);
        $this->actingAs($this->admin)->put('/admin/schedules/'.$schedule->id, $this->scheduleData());
        $this->assertSame($this->oldYear->id, (int) $schedule->fresh()->academic_year_id);
    }

    public function test_graduation_creates_complete_history_in_current_year(): void
    {
        $this->actingAs($this->admin)->post('/admin/students/bulk-update-status', ['student_ids' => [$this->student->id], 'status' => 'lulus'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('student_class_histories', ['student_id' => $this->student->id, 'academic_year_id' => $this->year->id, 'class_id' => '10-A', 'status' => 'lulus']);
    }

    public function test_class_rename_updates_all_references(): void
    {
        $schedule = $this->schedule();
        StudentClassHistory::create(['student_id' => $this->student->id, 'academic_year_id' => $this->year->id, 'class_id' => '10-A', 'status' => 'aktif']);
        $classroom = Classroom::first();
        $this->actingAs($this->admin)->put('/admin/classrooms/'.$classroom->id, ['name' => '10-B'])->assertSessionHasNoErrors();
        $this->assertSame('10-B', $this->student->fresh()->class_id);
        $this->assertSame('10-B', $schedule->fresh()->class_id);
        $this->assertDatabaseHas('student_class_histories', ['class_id' => '10-B']);
    }

    public function test_semester_reset_is_scoped_to_selected_year(): void
    {
        $old = $this->schedule(['academic_year_id' => $this->oldYear->id]);
        $this->schedule();
        $this->actingAs($this->admin)->delete('/admin/schedules/truncate', ['semester' => 'Ganjil', 'academic_year_id' => $this->year->id])->assertRedirect();
        $this->assertDatabaseHas('schedules', ['id' => $old->id]);
        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_schedules_with_attendance_cannot_be_deleted(): void
    {
        $schedule = $this->schedule();
        $attendance = $this->attendance($schedule);
        $this->actingAs($this->admin)->delete('/admin/schedules/truncate', ['semester' => 'Ganjil', 'academic_year_id' => $this->year->id])->assertSessionHasErrors();
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
    }

    public function test_year_reset_route_works_without_route_cache(): void
    {
        $this->schedule();
        $this->actingAs($this->admin)->delete('/admin/schedules/truncate-by-year', ['academic_year_id' => $this->year->id])->assertRedirect();
        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_schedule_bulk_delete_works_and_preserves_other_rows(): void
    {
        $delete = $this->schedule();
        $keep = $this->schedule(['academic_year_id' => $this->oldYear->id]);
        $this->actingAs($this->admin)->delete('/admin/schedules/bulk-delete', ['schedule_ids' => [$delete->id]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('schedules', ['id' => $delete->id]);
        $this->assertDatabaseHas('schedules', ['id' => $keep->id]);
    }

    public function test_personal_email_can_request_and_complete_password_reset(): void
    {
        Notification::fake();
        $this->student->update(['personal_email' => 'pribadi@example.test']);
        $this->post('/forgot-password', ['email' => 'pribadi@example.test'])->assertSessionHasNoErrors();
        Notification::assertSentTo($this->student->user, ResetPassword::class, function ($notification) {
            $this->post('/reset-password', ['email' => 'pribadi@example.test', 'token' => $notification->token, 'password' => 'NewSecret123!', 'password_confirmation' => 'NewSecret123!'])->assertSessionHasNoErrors();

            return true;
        });
        $this->assertTrue(Hash::check('NewSecret123!', $this->student->user->fresh()->password));
    }

    public function test_duplicate_attendance_for_same_day_is_rejected_by_database(): void
    {
        $schedule = $this->schedule();
        $this->attendance($schedule);
        $this->expectException(QueryException::class);
        $this->attendance($schedule);
    }

    public function test_excel_treats_student_names_as_literal_text(): void
    {
        $this->student->user->update(['name' => '=1+1']);
        $this->attendance($this->schedule());
        $response = $this->actingAs($this->admin)->get('/reports/custom-excel');
        $response->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $sheet = IOFactory::load($path)->getActiveSheet();
        $found = false;
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                if ($cell->getValue() === '=1+1') {
                    $found = true;
                    $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
                }
            }
        }
        $this->assertTrue($found);
    }

    public function test_denied_year_deactivation_does_not_leave_open_transaction(): void
    {
        $before = DB::transactionLevel();
        $this->actingAs($this->admin)->put('/admin/academic-years/'.$this->year->id, ['name' => $this->year->name, 'is_active' => false])->assertSessionHas('error');
        $after = DB::transactionLevel();
        while (DB::transactionLevel() > $before) {
            DB::rollBack();
        }
        $this->assertSame($before, $after);
    }

    public function test_semester_survives_cache_flush(): void
    {
        $this->actingAs($this->admin)->post('/admin/schedules/toggle', ['semester' => 'Genap'])->assertSessionHasNoErrors();
        Cache::flush();
        $schedule = $this->schedule(['semester' => 'Genap']);
        session()->flush();
        $this->actingAs($this->teacher)->get('/scanner')->assertViewHas('activeSchedule', fn ($s) => $s && $s->id === $schedule->id);
    }

    public function test_import_preserves_alumni_status_and_uses_nisn_after_email_changes(): void
    {
        $this->student->update(['status' => 'lulus']);
        $this->student->user->update(['email' => 'changed@example.test']);
        $import = new StudentsImport;
        $import->model(['nisn' => $this->student->nisn, 'nama' => 'Updated Name', 'kelas' => '10-A']);
        $this->assertDatabaseCount('students', 1);
        $this->assertSame('lulus', $this->student->fresh()->status);
        $this->assertSame('changed@example.test', $this->student->user->fresh()->email);
    }

    public function test_new_student_receives_random_initial_password(): void
    {
        $this->actingAs($this->admin)->post('/admin/students', ['name' => 'New Student', 'nisn' => '0000000001', 'class_id' => '10-A'])->assertSessionHasNoErrors()->assertSessionHas('initial_credentials');
        $student = Student::where('nisn', '0000000001')->firstOrFail();
        $this->assertFalse(Hash::check('0000000001', $student->user->password));
        $this->assertTrue((bool) $student->user->must_change_password);
    }

    public function test_referenced_teacher_and_subject_cannot_be_deleted(): void
    {
        $schedule = $this->schedule();
        $this->attendance($schedule);
        $this->actingAs($this->admin)->delete('/admin/users/'.$this->teacher->id)->assertSessionHasErrors();
        $this->delete('/admin/subjects/'.$this->subject->id)->assertSessionHasErrors();
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_invalid_report_dates_are_rejected(): void
    {
        $this->actingAs($this->admin)->get('/reports?start_date=invalid&end_date=2026-10-26')->assertSessionHasErrors('start_date');
    }

    public function test_short_new_passwords_are_rejected(): void
    {
        $this->actingAs($this->student->user)->post('/student/password', ['current_password' => 'password', 'new_password' => '12345678', 'new_password_confirmation' => '12345678'])->assertSessionHasErrors('new_password');
    }

    public function test_initial_password_must_be_changed_before_portal_access(): void
    {
        $user = $this->student->user;
        $user->update(['must_change_password' => true]);
        $this->actingAs($user)->get('/student/portal')->assertRedirect(route('student.settings'));
        $this->post('/student/password', ['current_password' => 'password', 'new_password' => 'NewSecret123!', 'new_password_confirmation' => 'NewSecret123!'])->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->must_change_password);
        $this->get('/student/portal')->assertOk();
    }

    public function test_qr_storage_failure_rolls_back_new_account_and_history(): void
    {
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->with('qrcodes/0000000001.svg')->andReturn(false);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldReceive('delete')->with('qrcodes/0000000001.svg')->once();
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        $this->actingAs($this->admin)->post('/admin/students', ['name' => 'Storage Failure', 'nisn' => '0000000001', 'class_id' => '10-A'])->assertServerError();
        $this->assertDatabaseMissing('users', ['email' => '0000000001@siswa.com']);
        $this->assertDatabaseMissing('students', ['nisn' => '0000000001']);
        $this->assertDatabaseCount('student_class_histories', 0);
    }

    public function test_admin_pages_render_with_quoted_student_names(): void
    {
        $this->student->user->update(['name' => "O'Neil <img src=x>"]);
        $this->actingAs($this->admin);
        foreach (['dashboard', 'students', 'classrooms', 'subjects', 'schedules', 'users', 'academic-years'] as $page) {
            $this->get('/admin/'.$page)->assertOk();
        }
    }

    public function test_admin_can_issue_a_new_initial_student_password(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/students/'.$this->student->id.'/reset-password');
        $response->assertRedirect()->assertSessionHas('initial_credentials');
        $credential = session('initial_credentials')[0];
        $user = $this->student->user->fresh();
        $this->assertTrue(Hash::check($credential['password'], $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertSame('1234567890', $this->student->fresh()->nisn);
    }

    public function test_personal_email_cannot_claim_a_case_variant_of_a_staff_login(): void
    {
        $this->teacher->update(['email' => 'TEACHER@EXAMPLE.TEST']);
        $this->actingAs($this->student->user)->post('/student/email', ['personal_email' => 'teacher@example.test'])->assertSessionHasErrors('personal_email');
        $this->assertNull($this->student->fresh()->personal_email);
    }
}
