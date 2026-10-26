<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $names = DB::table('students')->select('class_id')
            ->union(DB::table('schedules')->select('class_id'))
            ->union(DB::table('student_class_histories')->select('class_id'))->pluck('class_id');
        $invalid = 0;
        DB::transaction(function () use ($names, &$invalid) {
            foreach ($names as $name) {
                if (! is_string($name) || trim($name) === '' || trim($name) !== $name || mb_strlen($name) > 255) {
                    $invalid++;

                    continue;
                }
                DB::table('classrooms')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        if ($invalid > 0) {
            Log::warning("{$invalid} nama kelas lama tidak dimasukkan ke master karena kosong atau tidak valid. Referensi siswa/jadwal tetap dipertahankan untuk pemeriksaan admin.");
        }
    }

    // Entries may already be used by administrators; rollback never removes the catalog.
    public function down(): void {}
};
