<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\Subject;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $classId = $request->input('class_id');
        $subjectId = $request->input('subject_id');

        $classes = \App\Models\Classroom::orderBy('name')->pluck('name');
        $subjects = Subject::orderBy('name')->get();

        $query = Attendance::with(['student.user', 'schedule.subject', 'schedule.teacher'])
            ->whereDate('timestamp', '>=', $startDate)
            ->whereDate('timestamp', '<=', $endDate);

        if ($classId) {
            $query->whereHas('schedule', function ($q) use ($classId) { $q->where('class_id', $classId); });
        }
        if ($subjectId) {
            $query->whereHas('schedule', function ($q) use ($subjectId) { $q->where('subject_id', $subjectId); });
        }

        $attendances = $query->orderBy('timestamp', 'desc')->get();

        return view('reports.index', compact('attendances', 'startDate', 'endDate', 'classes', 'subjects', 'classId', 'subjectId'));
    }

    public function exportCustomPDF(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $classId = $request->input('class_id');
        $subjectId = $request->input('subject_id');

        $query = Attendance::with(['student.user', 'schedule.subject', 'schedule.teacher'])
            ->whereDate('timestamp', '>=', $startDate)
            ->whereDate('timestamp', '<=', $endDate);

        if ($classId) $query->whereHas('schedule', function ($q) use ($classId) { $q->where('class_id', $classId); });
        if ($subjectId) $query->whereHas('schedule', function ($q) use ($subjectId) { $q->where('subject_id', $subjectId); });

        $attendances = $query->orderBy('timestamp', 'asc')->get();
        $subjectName = $subjectId ? Subject::find($subjectId)->name : 'Semua Mata Pelajaran';
        $className = $classId ? $classId : 'Semua Kelas';

        $dateRange = Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y');
        $printDate = Carbon::now()->locale('id')->translatedFormat('l, d F Y - H:i');

        $pdf = Pdf::loadView('reports.custom_pdf', compact('attendances', 'dateRange', 'printDate', 'subjectName', 'className'));
        
        return $pdf->stream('Laporan_Absensi_'.$startDate.'_sd_'.$endDate.'.pdf');
    }

    public function exportCustomExcel(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $classId = $request->input('class_id');
        $subjectId = $request->input('subject_id');

        $query = Attendance::with(['student.user', 'schedule.subject'])->whereDate('timestamp', '>=', $startDate)->whereDate('timestamp', '<=', $endDate);

        if ($classId) $query->whereHas('schedule', function ($q) use ($classId) { $q->where('class_id', $classId); });
        if ($subjectId) $query->whereHas('schedule', function ($q) use ($subjectId) { $q->where('subject_id', $subjectId); });

        $attendances = $query->orderBy('timestamp', 'asc')->get();
        
        $subjectName = $subjectId ? \App\Models\Subject::find($subjectId)->name : 'Semua Mata Pelajaran';
        $className = $classId ? $classId : 'Semua Kelas';
        $dateRange = Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y');
        $printDate = Carbon::now()->locale('id')->translatedFormat('d F Y - H:i');
        $printerName = auth()->user()->name;

        $fileName = 'Rekap_Absensi_'.$startDate.'_sd_'.$endDate.'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendancesExport($attendances, $dateRange, $className, $subjectName, $printDate, $printerName),
            $fileName
        );
    }

    public function exportSessionPDF($schedule_id)
    {
        $schedule = Schedule::with(['subject', 'teacher'])->findOrFail($schedule_id);
        $attendances = Attendance::with(['student.user'])->where('schedule_id', $schedule_id)->orderBy('timestamp', 'asc')->get();
        $date = Carbon::now()->locale('id')->translatedFormat('l, d F Y - H:i');
        $pdf = Pdf::loadView('reports.attendance', compact('schedule', 'attendances', 'date'));
        $safeSubjectName = preg_replace('/[^A-Za-z0-9\-]/', '_', $schedule->subject->name ?? 'Mapel');
        
        return $pdf->stream('Laporan_Sesi_'.$safeSubjectName.'_'.$schedule->class_id.'.pdf');
    }

    /**
     * FUNGSI BARU: Kosongkan Seluruh Riwayat Absensi
     */
    public function truncate()
    {
        // Fitur ini hanya bisa diakses via Rute Admin
        \App\Models\Attendance::truncate();
        
        return redirect()->back()->with('success', 'Berhasil! Seluruh riwayat absensi telah dihapus secara permanen.');
    }
}