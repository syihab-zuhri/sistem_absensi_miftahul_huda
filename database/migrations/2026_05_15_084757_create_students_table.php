<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke tabel users (karena setiap siswa punya 1 akun login)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // NISN wajib unik karena jadi basis QR Code
            $table->string('nisn', 20)->unique();
            $table->string('class_id');
            $table->string('qr_code_path')->nullable(); // Boleh kosong sebelum di-generate
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};