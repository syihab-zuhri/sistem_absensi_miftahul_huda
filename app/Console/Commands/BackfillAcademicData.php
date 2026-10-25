<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentClassHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillAcademicData extends Command
{
    protected $signature = 'academic:backfill {--year= : Nama tahun ajaran tujuan}';

    protected $description = 'Backfill data lama untuk sistem tahun ajaran baru (membuat tahun ajaran default, set status siswa, isi history kelas)';

    public function handle()
    {
        $this->info('Memulai proses backfill data tahun ajaran...');

        DB::beginTransaction();

        try {
            // 1. Buat tahun ajaran default
            $name = $this->option('year');
            if ($name && ! preg_match('/^\d{4}\/\d{4}$/', $name)) {
                throw new \InvalidArgumentException('Format tahun ajaran harus YYYY/YYYY.');
            }
            $academicYear = $name ? AcademicYear::where('name', $name)->first() : AcademicYear::active()->first();
            if (! $academicYear) {
                $start = now()->month >= 7 ? now()->year : now()->year - 1;
                $name ??= $start.'/'.($start + 1);
                $start = (int) explode('/', $name)[0];
                $academicYear = AcademicYear::firstOrCreate(['name' => $name], ['is_active' => ! AcademicYear::active()->exists(), 'start_date' => $start.'-07-01', 'end_date' => ($start + 1).'-06-30']);
            }
            $this->info("[1/4] Tahun ajaran '{$academicYear->name}' dibuat/ditemukan (is_active: ".($academicYear->is_active ? 'Ya' : 'Tidak').').');

            // 2. Set semua siswa lama menjadi status 'aktif' jika statusnya masih default kosong
            $updatedStudents = Student::where('status', 'aktif')
                ->orWhereNull('status')
                ->orWhere('status', '')
                ->count();
            Student::whereNull('status')->orWhere('status', '')->update(['status' => 'aktif']);
            $this->info("[2/4] Total siswa aktif saat ini: {$updatedStudents}");

            // 3. Hubungkan jadwal lama ke tahun ajaran default
            $updatedSchedules = Schedule::whereNull('academic_year_id')->update(['academic_year_id' => $academicYear->id]);
            $this->info("[3/4] Jadwal lama yang dihubungkan ke tahun ajaran: {$updatedSchedules}");

            // 4. Buat student_class_histories untuk semua siswa aktif
            $students = Student::where('status', 'aktif')->get();
            $historyCreated = 0;
            $historySkipped = 0;

            foreach ($students as $student) {
                $history = StudentClassHistory::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYear->id,
                    ],
                    [
                        'class_id' => $student->class_id,
                        'status' => 'aktif',
                    ]
                );

                if ($history->wasRecentlyCreated) {
                    $historyCreated++;
                } else {
                    $historySkipped++;
                }
            }
            $this->info("[4/4] History kelas siswa: {$historyCreated} dibuat, {$historySkipped} sudah ada (skip).");

            DB::commit();

            $this->newLine();
            $this->info('Backfill selesai dengan sukses!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Terjadi kesalahan: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
