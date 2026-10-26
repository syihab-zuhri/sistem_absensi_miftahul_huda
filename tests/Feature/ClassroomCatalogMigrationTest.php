<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\StudentClassHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SchoolFixtures;
use Tests\TestCase;

class ClassroomCatalogMigrationTest extends TestCase
{
    use RefreshDatabase, SchoolFixtures;

    public function test_legacy_class_names_are_added_without_changing_students_or_existing_classrooms(): void
    {
        Classroom::query()->delete();
        $existing = Classroom::create(['name' => '12-A']);
        $original = $existing->fresh()->getAttributes();
        $other = $this->schoolStudent('11-A');
        StudentClassHistory::create(['student_id' => $this->student->id, 'academic_year_id' => $this->year->id, 'class_id' => '9-B', 'status' => 'aktif']);
        $path = database_path('migrations/2026_10_26_000001_backfill_classroom_catalog.php');
        $this->assertFileExists($path);
        $migration = require $path;
        $migration->up();
        $migration->up();
        $this->assertSame(['10-A', '11-A', '12-A', '9-B'], Classroom::orderBy('name')->pluck('name')->all());
        $this->assertSame($original, $existing->fresh()->getAttributes());
        $this->assertSame('10-A', $this->student->fresh()->class_id);
        $this->assertSame('11-A', $other->fresh()->class_id);
        $this->assertSame('10-A', $this->schedule->fresh()->class_id);
        $migration->down();
        $this->assertDatabaseCount('classrooms', 4);
    }

    public function test_blank_or_invalid_legacy_class_names_are_not_added_to_the_catalog(): void
    {
        $this->schoolStudent('');
        $this->schoolStudent(str_repeat('A', 256));
        $this->schoolStudent(' 10-B ');
        $path = database_path('migrations/2026_10_26_000001_backfill_classroom_catalog.php');
        $this->assertFileExists($path);
        $migration = require $path;
        $migration->up();
        $this->assertSame(['10-A'], Classroom::pluck('name')->all());
        $this->assertDatabaseHas('students', ['class_id' => ' 10-B ']);
    }
}
