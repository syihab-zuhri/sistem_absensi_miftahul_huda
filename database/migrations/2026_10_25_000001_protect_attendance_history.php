<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // MySQL commits every DDL statement, so each step is guarded and the data changes run in
    // one transaction: re-running after an interruption resumes instead of failing.
    public function up(): void
    {
        // Rows are only archived after the table is complete, so an empty table may be a partial create.
        if (Schema::hasTable('attendance_duplicate_archives') && ! DB::table('attendance_duplicate_archives')->exists()) {
            Schema::drop('attendance_duplicate_archives');
        }
        if (! Schema::hasTable('attendance_duplicate_archives')) {
            Schema::create('attendance_duplicate_archives', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('original_id')->unique();
                $table->foreignId('schedule_id')->constrained()->restrictOnDelete();
                $table->foreignId('student_id')->constrained()->restrictOnDelete();
                $table->json('original_record');
                $table->timestamp('archived_at');
            });
        }
        if (! Schema::hasColumn('attendances', 'attendance_date')) {
            Schema::table('attendances', fn (Blueprint $table) => $table->date('attendance_date')->nullable());
        }
        DB::transaction(function () {
            DB::table('attendances')->whereNull('attendance_date')->update(['attendance_date' => DB::raw('DATE(timestamp)')]);
            $duplicates = DB::table('attendances')->select('schedule_id', 'student_id', 'attendance_date')
                ->groupBy('schedule_id', 'student_id', 'attendance_date')->havingRaw('COUNT(*) > 1')->get();
            foreach ($duplicates as $group) {
                $rows = DB::table('attendances')->where('schedule_id', $group->schedule_id)
                    ->where('student_id', $group->student_id)->where('attendance_date', $group->attendance_date)
                    ->orderByDesc('updated_at')->orderByDesc('id')->get();
                // Keep the latest correction and preserve every previous row verbatim.
                foreach ($rows->skip(1) as $row) {
                    DB::table('attendance_duplicate_archives')->insert([
                        'original_id' => $row->id, 'schedule_id' => $row->schedule_id, 'student_id' => $row->student_id,
                        'original_record' => json_encode($row, JSON_THROW_ON_ERROR), 'archived_at' => now(),
                    ]);
                    DB::table('attendances')->where('id', $row->id)->delete();
                }
            }
        });
        if (! Schema::hasIndex('attendances', 'attendances_daily_unique')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->date('attendance_date')->nullable(false)->change();
                $table->unique(['schedule_id', 'student_id', 'attendance_date'], 'attendances_daily_unique');
            });
        }
        if (! Schema::hasIndex('attendances', ['attendance_date'])) {
            Schema::table('attendances', fn (Blueprint $table) => $table->index('attendance_date'));
        }
        foreach (['schedule_id' => 'schedules', 'student_id' => 'students'] as $column => $parent) {
            $this->restrictDeletes($column, $parent);
        }
    }

    private function restrictDeletes(string $column, string $parent): void
    {
        $foreign = collect(Schema::getForeignKeys('attendances'))->first(fn ($key) => $key['columns'] === [$column]);
        if ($foreign && $foreign['on_delete'] === 'restrict') {
            return;
        }
        Schema::table('attendances', function (Blueprint $table) use ($foreign, $column, $parent) {
            if ($foreign) {
                $table->dropForeign([$column]);
            }
            $table->foreign($column)->references('id')->on($parent)->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Restore records before dropping their archive and parent protection.
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_daily_unique');
            $table->dropIndex(['attendance_date']);
            $table->dropForeign(['schedule_id']);
            $table->dropForeign(['student_id']);
            $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });
        DB::table('attendance_duplicate_archives')->orderBy('id')->chunkById(500, function ($records) {
            foreach ($records as $record) {
                DB::table('attendances')->insert(json_decode($record->original_record, true, 512, JSON_THROW_ON_ERROR));
            }
        });
        Schema::table('attendances', fn (Blueprint $table) => $table->dropColumn('attendance_date'));
        Schema::dropIfExists('attendance_duplicate_archives');
    }
};
