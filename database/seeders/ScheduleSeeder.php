<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('Seeder simulasi hanya untuk pengembangan.');
        }
        // 1. Pastikan ada mata pelajaran
        $subject = Subject::firstOrCreate(['name' => 'Pemrograman Web']);

        // 2. Pastikan ada akun guru (atau pakai akun admin)
        $teacher = User::role(['admin', 'guru'])->first();

        if (! $teacher) {
            // Fallback: cari user admin berdasarkan email
            $teacher = User::where('email', 'admin@sekolah.com')->first();
        }

        if (! $teacher) {
            $this->command->error('Akun guru/admin tidak ditemukan. Jalankan RoleSeeder dulu.');

            return;
        }

        // 3. Ambil hari ini secara dinamis (Misal: "Sabtu")
        $hariIni = Carbon::now()->locale('id')->dayName;

        // 4. Pastikan ada tahun ajaran aktif
        $activeYear = AcademicYear::active()->first();
        if (! $activeYear) {
            $activeYear = AcademicYear::firstOrCreate(
                ['is_active' => true],
                ['name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true]
            );
        }

        // 5. Buat jadwal yang aktif SEPANJANG HARI INI
        Schedule::updateOrCreate(
            [
                'subject_id' => $subject->id,
                'class_id' => '10-A',
                'day' => $hariIni,
                'academic_year_id' => $activeYear->id,
            ],
            [
                'teacher_id' => $teacher->id,
                'start_time' => '00:00:00', // Dari jam 12 malam
                'end_time' => '23:59:59',   // Sampai jam 11:59 malam
                'academic_year_id' => $activeYear->id,
            ]
        );

        $this->command->info("Jadwal simulasi berhasil dibuat untuk hari: {$hariIni}");
    }
}
