<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('class_id');
            $table->string('day'); 
            $table->time('start_time');
            $table->time('end_time');
            $table->string('semester')->default('Ganjil'); // TAMBAHAN LANGKAH 3: Simpan status Ganjil/Genap
            $table->timestamps();

            $table->index(['day', 'start_time', 'end_time', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};