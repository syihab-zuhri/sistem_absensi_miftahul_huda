<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL commits every DDL statement, so an interrupted run leaves partial tables behind.
        // They only hold data derived from attendances, so they are discarded and rebuilt from scratch.
        $this->discardInterruptedRun();
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->string('class_id');
            $table->date('lesson_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 20)->default('planned');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->unique(['schedule_id', 'lesson_date']);
            $table->index(['teacher_id', 'lesson_date']);
        });
        Schema::create('attendance_session_students', function (Blueprint $table) {
            $table->foreignId('attendance_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->primary(['attendance_session_id', 'student_id']);
        });
        Schema::create('attendance_session_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('attendance_session_id')->constrained()->restrictOnDelete();
            $table->date('original_date');
            $table->unique(['schedule_id', 'original_date']);
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('attendance_session_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('type', 10);
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['attendance_session_id', 'student_id', 'status'], 'leave_requests_session_student_status');
        });
        Schema::create('attendance_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('leave_request_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('action', 40);
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20)->nullable();
            $table->text('reason')->nullable();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->timestamp('created_at');
            $table->index(['attendance_session_id', 'created_at']);
        });
        $this->linkHistoricalAttendance();
    }

    // Preserve original attendance values; historical rosters contain only known participants.
    // Set-based statements per batch of schedules keep large histories to a few queries each.
    private function linkHistoricalAttendance(): void
    {
        $now = now();
        DB::table('schedules')->select('id')->orderBy('id')->chunkById(100, function ($schedules) use ($now) {
            $ids = $schedules->pluck('id');
            DB::transaction(function () use ($ids, $now) {
                DB::table('attendance_sessions')->insertUsing(
                    ['schedule_id', 'teacher_id', 'class_id', 'lesson_date', 'start_time', 'end_time', 'status', 'opened_at', 'closed_at', 'created_at', 'updated_at'],
                    DB::table('attendances')->join('schedules', 'schedules.id', '=', 'attendances.schedule_id')->whereIn('attendances.schedule_id', $ids)
                        ->selectRaw('attendances.schedule_id, schedules.teacher_id, schedules.class_id, attendances.attendance_date, schedules.start_time, schedules.end_time, ?, MIN(attendances.timestamp), MAX(attendances.timestamp), ?, ?', ['closed', $now, $now])
                        ->groupBy('attendances.schedule_id', 'attendances.attendance_date', 'schedules.teacher_id', 'schedules.class_id', 'schedules.start_time', 'schedules.end_time'));
                DB::table('attendances')->whereIn('schedule_id', $ids)->update(['attendance_session_id' => DB::raw(
                    '(select id from attendance_sessions where attendance_sessions.schedule_id = attendances.schedule_id and attendance_sessions.lesson_date = attendances.attendance_date)')]);
                DB::table('attendance_session_students')->insertUsing(['attendance_session_id', 'student_id'],
                    DB::table('attendances')->whereIn('schedule_id', $ids)->select('attendance_session_id', 'student_id'));
            });
        });
    }

    private function discardInterruptedRun(): void
    {
        foreach (['leave_requests', 'attendance_audits', 'attendance_session_moves'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Tabel {$table} sudah berisi data dari aplikasi. Pulihkan backup database sebelum menjalankan ulang migrasi ini.");
            }
        }
        $this->down();
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_audits');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendance_session_students');
        Schema::dropIfExists('attendance_session_moves');
        if (Schema::hasColumn('attendances', 'attendance_session_id')) {
            $foreign = collect(Schema::getForeignKeys('attendances'))->contains(fn ($key) => $key['columns'] === ['attendance_session_id']);
            Schema::table('attendances', function (Blueprint $table) use ($foreign) {
                if ($foreign) {
                    $table->dropForeign(['attendance_session_id']);
                }
                $table->dropColumn('attendance_session_id');
            });
        }
        Schema::dropIfExists('attendance_sessions');
    }
};
