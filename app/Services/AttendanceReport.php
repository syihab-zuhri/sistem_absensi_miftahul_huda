<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AttendanceReport
{
    public static function query(array $filters): Builder
    {
        return Attendance::with(['student.user', 'schedule.subject', 'schedule.teacher', 'schedule.academicYear', 'session.teacher'])->visibleTo(auth()->user())
            ->whereBetween('attendance_date', [$filters['start_date'], $filters['end_date']])
            ->whereHas('schedule', function ($query) use ($filters) {
                if ($filters['academic_year_id'] ?? null) {
                    $query->where('academic_year_id', $filters['academic_year_id']);
                }
                if ($filters['class_id'] ?? null) {
                    $query->where('class_id', $filters['class_id']);
                }
                if ($filters['subject_id'] ?? null) {
                    $query->where('subject_id', $filters['subject_id']);
                }
            });
    }

    public static function metadata(array $filters): array
    {
        return [
            'dateRange' => Carbon::parse($filters['start_date'])->translatedFormat('d M Y').' s/d '.Carbon::parse($filters['end_date'])->translatedFormat('d M Y'),
            'printDate' => now()->translatedFormat('d F Y - H:i'),
            'subjectName' => ! empty($filters['subject_id']) ? Subject::find($filters['subject_id'])?->name : 'Semua Mata Pelajaran',
            'className' => $filters['class_id'] ?? 'Semua Kelas',
            'academicYearName' => ! empty($filters['academic_year_id']) ? AcademicYear::find($filters['academic_year_id'])?->name : 'Semua Tahun Ajaran',
        ];
    }
}
