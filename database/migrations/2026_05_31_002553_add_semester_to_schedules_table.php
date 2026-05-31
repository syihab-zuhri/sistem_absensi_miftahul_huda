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
        Schema::table('schedules', function (Blueprint $table) {
            // Menambahkan kolom semester setelah kolom end_time
            if (!Schema::hasColumn('schedules', 'semester')) {
                $table->string('semester')->default('Ganjil')->after('end_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'semester')) {
                $table->dropColumn('semester');
            }
        });
    }
};