<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        // Preserve original attendance values; historical rosters contain only known participants.
        DB::table('attendances')->select('schedule_id', 'attendance_date')->distinct()->orderBy('schedule_id')->orderBy('attendance_date')
            ->chunk(500, function ($groups) {
                foreach ($groups as $group) {
                    $schedule = DB::table('schedules')->find($group->schedule_id);
                    $query = DB::table('attendances')->where('schedule_id', $group->schedule_id)->where('attendance_date', $group->attendance_date);
                    $sessionId = DB::table('attendance_sessions')->insertGetId([
                        'schedule_id' => $schedule->id, 'teacher_id' => $schedule->teacher_id, 'class_id' => $schedule->class_id,
                        'lesson_date' => $group->attendance_date, 'start_time' => $schedule->start_time, 'end_time' => $schedule->end_time,
                        'status' => 'closed', 'opened_at' => (clone $query)->min('timestamp'), 'closed_at' => (clone $query)->max('timestamp'),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    foreach ((clone $query)->pluck('student_id')->unique() as $studentId) {
                        DB::table('attendance_session_students')->insert(['attendance_session_id' => $sessionId, 'student_id' => $studentId]);
                    }
                    $query->update(['attendance_session_id' => $sessionId]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_audits');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendance_session_students');
        Schema::dropIfExists('attendance_session_moves');
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['attendance_session_id']);
            $table->dropColumn('attendance_session_id');
        });
        Schema::dropIfExists('attendance_sessions');
    }
};
