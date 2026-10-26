<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Read the cache first so an unreachable cache store fails before any column is added.
        $semester = Cache::get('active_semester', 'Ganjil');
        if (! Schema::hasColumn('academic_years', 'active_semester')) {
            Schema::table('academic_years', fn (Blueprint $table) => $table->string('active_semester')->default('Ganjil'));
        }
        if (! Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', fn (Blueprint $table) => $table->boolean('must_change_password')->default(false));
        }
        if (in_array($semester, ['Ganjil', 'Genap'], true)) {
            DB::table('academic_years')->where('is_active', true)->update(['active_semester' => $semester]);
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('must_change_password'));
        Schema::table('academic_years', fn (Blueprint $table) => $table->dropColumn('active_semester'));
    }
};
