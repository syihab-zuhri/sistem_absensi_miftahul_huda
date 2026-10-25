<?php

namespace Tests\Feature;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Support\SchoolFixtures;
use Tests\TestCase;

class AttendanceWorkflowMigrationTest extends TestCase
{
    use DatabaseMigrations, SchoolFixtures;

    public function test_upgrade_links_historical_sessions_without_changing_records_and_rollback_restores_original_shape(): void
    {
        $path = database_path('migrations/2026_10_25_000003_add_attendance_workflows.php');
        $this->assertFileExists($path);
        $migration = require $path;
        $migration->down();
        $attendance = Attendance::create(['schedule_id' => $this->schedule->id, 'student_id' => $this->student->id, 'status' => 'izin', 'timestamp' => '2026-10-19 08:15:00']);
        $original = (array) DB::table('attendances')->find($attendance->id);
        $migration->up();
        $this->assertDatabaseHas('attendance_sessions', ['schedule_id' => $this->schedule->id, 'lesson_date' => '2026-10-19', 'status' => 'closed']);
        $this->assertDatabaseCount('attendance_session_students', 1);
        $this->assertDatabaseCount('attendance_audits', 0);
        $after = (array) DB::table('attendances')->find($attendance->id);
        $this->assertNotNull($after['attendance_session_id']);
        unset($after['attendance_session_id']);
        $this->assertSame($original, $after);
        $migration->down();
        $this->assertSame($original, (array) DB::table('attendances')->find($attendance->id));
        $migration->up();
    }
}
