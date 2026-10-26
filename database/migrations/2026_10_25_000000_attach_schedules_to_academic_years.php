<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    // The previous schedule form never sent academic_year_id, so schedules created after
    // `academic:backfill` have no year and would vanish from the scanner, lists and reports.
    public function up(): void
    {
        DB::transaction(function () {
            $years = DB::table('academic_years')->get();
            $active = $years->firstWhere('is_active', true);
            DB::table('schedules')->whereNull('academic_year_id')->lazyById(200)->each(function ($schedule) use ($years, $active) {
                // Attach to the year of the schedule's latest use so it stays with the term it belongs to.
                $date = DB::table('attendances')->where('schedule_id', $schedule->id)->max('timestamp') ?? $schedule->created_at;
                $day = $date ? substr((string) $date, 0, 10) : null;
                $year = $day ? $years->first(fn ($year) => $year->start_date && $year->end_date
                    && substr($year->start_date, 0, 10) <= $day && substr($year->end_date, 0, 10) >= $day) : null;
                $year ??= $active;
                if ($year) {
                    DB::table('schedules')->where('id', $schedule->id)->update(['academic_year_id' => $year->id]);
                }
            });
        });
        $remaining = DB::table('schedules')->whereNull('academic_year_id')->count();
        if ($remaining > 0) {
            Log::warning("{$remaining} jadwal belum memiliki tahun ajaran. Buat/aktifkan tahun ajaran lalu jalankan php artisan academic:backfill.");
        }
    }

    // Year assignments cannot be told apart from ones made by admins, so they are kept.
    public function down(): void {}
};
