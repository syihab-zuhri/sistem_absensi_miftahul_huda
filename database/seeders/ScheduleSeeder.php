<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan ada mata pelajaran
        $subject = Subject::firstOrCreate(['name' => 'Pemrograman Web']);

        // 2. Pastikan ada akun guru (atau pakai akun admin ID 1)
        $teacher = User::find(1); 

        if (!$teacher) {
            $this->command->error('Akun guru/admin tidak ditemukan. Jalankan RoleSeeder dulu.');
            return;
        }

        // 3. Ambil hari ini secara dinamis (Misal: "Sabtu")
        $hariIni = Carbon::now()->locale('id')->dayName;

        // 4. Buat jadwal yang aktif SEPANJANG HARI INI
        Schedule::updateOrCreate(
            [
                'subject_id' => $subject->id,
                'class_id' => '10-A',
                'day' => $hariIni,
            ],
            [
                'teacher_id' => $teacher->id,
                'start_time' => '00:00:00', // Dari jam 12 malam
                'end_time' => '23:59:59',   // Sampai jam 11:59 malam
            ]
        );

        $this->command->info("Jadwal simulasi berhasil dibuat untuk hari: {$hariIni}");
    }
}