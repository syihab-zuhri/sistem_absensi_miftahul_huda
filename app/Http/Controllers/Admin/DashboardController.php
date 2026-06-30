<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Data untuk Tabel Recent Attendances
        $recentAttendances = Attendance::with(['student.user', 'schedule.subject'])
            ->orderBy('timestamp', 'desc')
            ->take(5)
            ->get();

        // 2. Statistik Hari Ini (Pie/Doughnut Chart)
        $today = Carbon::today()->toDateString();
        $todayStats = [
            'hadir' => Attendance::whereDate('timestamp', $today)->where('status', 'hadir')->count(),
            'sakit' => Attendance::whereDate('timestamp', $today)->where('status', 'sakit')->count(),
            'izin' => Attendance::whereDate('timestamp', $today)->where('status', 'izin')->count(),
            'alfa' => Attendance::whereDate('timestamp', $today)->where('status', 'alfa')->count(),
        ];

        // 3. Statistik 7 Hari Terakhir (Bar/Line Chart)
        $last7Days = [];
        $weeklyStats = [
            'hadir' => [],
            'sakit' => [],
            'izin' => [],
            'alfa' => []
        ];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $label = Carbon::today()->subDays($i)->locale('id')->translatedFormat('D, d M');

            $last7Days[] = $label;

            $weeklyStats['hadir'][] = Attendance::whereDate('timestamp', $date)->where('status', 'hadir')->count();
            $weeklyStats['sakit'][] = Attendance::whereDate('timestamp', $date)->where('status', 'sakit')->count();
            $weeklyStats['izin'][] = Attendance::whereDate('timestamp', $date)->where('status', 'izin')->count();
            $weeklyStats['alfa'][] = Attendance::whereDate('timestamp', $date)->where('status', 'alfa')->count();
        }

        return view('admin.dashboard', compact(
            'recentAttendances',
            'todayStats',
            'last7Days',
            'weeklyStats'
        ));
    }
}
