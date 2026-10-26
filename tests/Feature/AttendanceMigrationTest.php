<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceMigrationTest extends TestCase
{
    use DatabaseMigrations;

    protected function migrateFreshUsing(): array
    {
        // Exercise the historical schema in which the duplicate archive was introduced.
        return ['--realpath' => true, '--path' => array_values(array_filter(
            glob(database_path('migrations/*.php')),
            fn ($path) => basename($path) <= '2026_10_25_000002_persist_academic_and_account_settings.php'
        ))];
    }

    private function legacyDuplicates(): array
    {
        $migration = require database_path('migrations/2026_10_25_000001_protect_attendance_history.php');
        $migration->down();
        $user = User::factory()->create();
        $student = Student::create(['user_id' => $user->id, 'nisn' => '0000000001', 'class_id' => '10-A', 'status' => 'aktif']);
        $subject = Subject::create(['name' => 'Matematika']);
        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $schedule = Schedule::create(['subject_id' => $subject->id, 'teacher_id' => $user->id, 'academic_year_id' => $year->id, 'class_id' => '10-A', 'day' => 'Senin', 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'semester' => 'Ganjil']);
        foreach ([901 => 'alpa', 902 => 'hadir'] as $id => $status) {
            DB::table('attendances')->insert(['id' => $id, 'schedule_id' => $schedule->id, 'student_id' => $student->id, 'status' => $status, 'timestamp' => '2026-10-26 08:15:00', 'created_at' => '2026-10-26 08:15:00', 'updated_at' => $id === 901 ? '2026-10-26 08:15:00' : '2026-10-26 08:30:00']);
        }
        $migration->up();

        return [$migration, $schedule];
    }

    public function test_migration_archives_duplicates_and_rollback_restores_original_rows(): void
    {
        [$migration] = $this->legacyDuplicates();
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', ['id' => 902, 'status' => 'hadir', 'attendance_date' => '2026-10-26']);
        $original = json_decode(DB::table('attendance_duplicate_archives')->value('original_record'), true);
        $this->assertSame(901, $original['id']);
        $this->assertSame('alpa', $original['status']);
        $migration->down();
        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', ['id' => 901, 'status' => 'alpa']);
        $migration->up();
    }

    public function test_interrupted_protection_migration_resumes_without_losing_archived_rows(): void
    {
        [$migration] = $this->legacyDuplicates();
        // Simulate a run that stopped after archiving duplicates but before the final constraints.
        Schema::table('attendances', fn (Blueprint $table) => $table->dropUnique('attendances_daily_unique'));
        $migration->up();
        $migration->up();
        $this->assertTrue(Schema::hasIndex('attendances', 'attendances_daily_unique'));
        $this->assertDatabaseCount('attendance_duplicate_archives', 1);
        $this->assertDatabaseCount('attendances', 1);
        foreach (['schedule_id', 'student_id'] as $column) {
            $this->assertSame('restrict', collect(Schema::getForeignKeys('attendances'))->firstWhere('columns', [$column])['on_delete']);
        }
    }

    public function test_archived_duplicates_protect_the_schedule_after_active_records_are_deleted(): void
    {
        [, $schedule] = $this->legacyDuplicates();
        DB::table('attendances')->delete();
        $this->expectException(QueryException::class);
        DB::table('schedules')->where('id', $schedule->id)->delete();
    }
}
