<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $activeAcademicYear = AcademicYear::active()->first();
        $query = Attendance::query()->when($activeAcademicYear, fn ($q) => $q->whereHas('schedule', fn ($s) => $s->where('academic_year_id', $activeAcademicYear->id)));
        $recentAttendances = (clone $query)->with(['student.user', 'schedule.subject'])->orderByDesc('timestamp')->limit(5)->get();
        $summary = (clone $query)->selectRaw('attendance_date, status, COUNT(*) AS total')
            ->whereBetween('attendance_date', [today()->subDays(6)->toDateString(), today()->toDateString()])
            ->groupBy('attendance_date', 'status')->get()->groupBy('attendance_date');
        $todayStats = array_fill_keys(Attendance::STATUSES, 0);
        $weeklyStats = array_fill_keys(Attendance::STATUSES, []);
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $last7Days[] = $day->copy()->locale('id')->translatedFormat('D, d M');
            $counts = $summary->get($day->toDateString(), collect())->pluck('total', 'status');
            foreach (Attendance::STATUSES as $status) {
                $count = (int) ($counts[$status] ?? 0);
                $weeklyStats[$status][] = $count;
                if ($i === 0) {
                    $todayStats[$status] = $count;
                }
            }
        }
        $academicYearStats = $activeAcademicYear ? [
            'name' => $activeAcademicYear->name, 'students_count' => Student::where('status', 'aktif')->count(),
            'schedules_count' => $activeAcademicYear->schedules()->count(), 'total_students_in_history' => $activeAcademicYear->studentClassHistories()->count(),
        ] : null;
        $totalStudents = Student::count();
        $totalTeachers = User::role('guru')->count();
        $totalSchedules = Schedule::count();

        return view('admin.dashboard', compact('recentAttendances', 'todayStats', 'last7Days', 'weeklyStats', 'activeAcademicYear', 'academicYearStats', 'totalStudents', 'totalTeachers', 'totalSchedules'));
    }
}
