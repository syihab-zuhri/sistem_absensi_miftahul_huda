<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpa'])->default('hadir');
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();

            // Indexing untuk performa (Fase 10)
            $table->index(['schedule_id', 'student_id']);
            $table->index('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};