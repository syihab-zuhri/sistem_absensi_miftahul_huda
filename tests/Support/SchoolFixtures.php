<?php

namespace Tests\Support;

use App\Models\AcademicYear;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

trait SchoolFixtures
{
    protected User $teacher;

    protected User $otherTeacher;

    protected User $admin;

    protected Student $student;

    protected AcademicYear $year;

    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        Carbon::setTestNow('2026-10-26 08:30:00');
        foreach (['admin', 'guru', 'siswa'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->admin = $this->schoolUser('admin');
        $this->teacher = $this->schoolUser('guru');
        $this->otherTeacher = $this->schoolUser('guru');
        $this->year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
        Classroom::create(['name' => '10-A']);
        $subject = Subject::create(['name' => 'Matematika']);
        $this->student = $this->schoolStudent();
        $this->schedule = Schedule::create(['subject_id' => $subject->id, 'teacher_id' => $this->teacher->id, 'class_id' => '10-A', 'day' => 'Senin', 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'semester' => 'Ganjil', 'academic_year_id' => $this->year->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function schoolUser(string $role): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole($role);

        return $user;
    }

    protected function schoolStudent(string $class = '10-A'): Student
    {
        return Student::create(['user_id' => $this->schoolUser('siswa')->id, 'nisn' => str_pad((string) (Student::count() + 1), 10, '0', STR_PAD_LEFT), 'class_id' => $class, 'status' => 'aktif']);
    }

    protected function plannedSession(array $data = []): AttendanceSession
    {
        $this->actingAs($this->teacher)->post('/attendance-sessions', array_merge([
            'schedule_id' => $this->schedule->id, 'lesson_date' => '2026-10-26',
        ], $data))->assertRedirect()->assertSessionHasNoErrors();

        return AttendanceSession::latest('id')->firstOrFail();
    }

    protected function openSession(): AttendanceSession
    {
        $session = $this->plannedSession();
        $this->actingAs($this->teacher)->post('/attendance-sessions/'.$session->id.'/open')->assertRedirect()->assertSessionHasNoErrors();

        return $session->fresh();
    }

    protected function recordAttendance($session, string $status = 'hadir', array $extra = [])
    {
        return $this->actingAs($this->teacher)->postJson('/attendance-sessions/'.$session->id.'/attendances', array_merge([
            'attendances' => [['student_id' => $this->student->id, 'status' => $status]],
        ], $extra));
    }
}
