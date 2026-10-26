<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SchoolFixtures;
use Tests\TestCase;

class ScheduleYearMigrationTest extends TestCase
{
    use RefreshDatabase, SchoolFixtures;

    private function legacySchedule(array $extra): Schedule
    {
        return Schedule::create(array_merge(['subject_id' => $this->schedule->subject_id, 'teacher_id' => $this->otherTeacher->id,
            'class_id' => '11-A', 'day' => 'Senin', 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'semester' => 'Ganjil', 'academic_year_id' => null], $extra));
    }

    public function test_schedules_without_year_are_attached_to_the_year_of_their_latest_use(): void
    {
        $oldYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => false, 'start_date' => '2025-07-01', 'end_date' => '2026-06-30']);
        $archived = $this->legacySchedule(['day' => 'Selasa']);
        Attendance::create(['schedule_id' => $archived->id, 'student_id' => $this->student->id, 'status' => 'hadir', 'timestamp' => '2026-03-10 08:15:00']);
        $current = $this->legacySchedule([]);
        $undated = $this->legacySchedule(['day' => 'Rabu']);
        $undated->forceFill(['created_at' => '2024-01-01 00:00:00'])->saveQuietly();

        (require database_path('migrations/2026_10_25_000000_attach_schedules_to_academic_years.php'))->up();

        $this->assertSame($oldYear->id, $archived->fresh()->academic_year_id);
        $this->assertSame($this->year->id, $current->fresh()->academic_year_id);
        $this->assertSame($this->year->id, $undated->fresh()->academic_year_id);
        $this->assertSame($this->year->id, $this->schedule->fresh()->academic_year_id);
        $this->actingAs($this->otherTeacher)->get('/scanner')->assertOk()->assertViewHas('activeSchedule', fn ($schedule) => $schedule?->id === $current->id);
    }
}
