<?php

namespace App\Http\Controllers;

use App\Exports\AttendancesExport;
use App\Http\Requests\ReportFilterRequest;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Subject;
use App\Services\AttendanceReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(ReportFilterRequest $request)
    {
        $filters = $request->validated();
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $classId = $filters['class_id'] ?? null;
        $subjectId = $filters['subject_id'] ?? null;
        $selectedYearId = $filters['academic_year_id'] ?? null;
        $academicYears = AcademicYear::orderByDesc('is_active')->orderByDesc('name')->get();
        $classes = Classroom::orderBy('name')->pluck('name');
        $subjects = Subject::orderBy('name')->get();
        $attendances = AttendanceReport::query($filters)->orderByDesc('timestamp')->orderByDesc('id')->paginate(50)->withQueryString();

        return view('reports.index', compact('attendances', 'startDate', 'endDate', 'classes', 'subjects', 'classId', 'subjectId', 'academicYears', 'selectedYearId'));
    }

    public function exportCustomPDF(ReportFilterRequest $request)
    {
        $filters = $request->validated();
        $query = AttendanceReport::query($filters);
        if ((clone $query)->limit(1001)->count() > 1000) {
            throw ValidationException::withMessages(['report' => 'PDF dibatasi 1000 baris. Persempit filter atau gunakan Excel.']);
        }
        $data = AttendanceReport::metadata($filters);
        $data['attendances'] = $query->orderBy('timestamp')->orderBy('id')->get();

        return Pdf::loadView('reports.custom_pdf', $data)->stream('Laporan_Absensi_'.$filters['start_date'].'_sd_'.$filters['end_date'].'.pdf');
    }

    public function exportCustomExcel(ReportFilterRequest $request)
    {
        $filters = $request->validated();
        $query = AttendanceReport::query($filters);
        if ((clone $query)->count() > 50000) {
            throw ValidationException::withMessages(['report' => 'Excel dibatasi 50000 baris. Persempit rentang tanggal atau filter kelas.']);
        }

        return Excel::download(new AttendancesExport($query->orderBy('id'), AttendanceReport::metadata($filters)), 'Rekap_Absensi_'.$filters['start_date'].'_sd_'.$filters['end_date'].'.xlsx');
    }

    public function exportSessionPDF(Request $request, $schedule_id)
    {
        $request->validate(['date' => 'nullable|date_format:Y-m-d|before_or_equal:today']);
        $schedule = Schedule::with(['subject', 'teacher', 'academicYear'])->findOrFail($schedule_id);
        $sessionDate = $request->input('date', today()->toDateString());
        $session = AttendanceSession::with('teacher')->where('schedule_id', $schedule->id)->whereDate('lesson_date', $sessionDate)->first();
        Gate::authorize('view', $session ?? $schedule);
        if ($session) {
            $schedule->setRelation('teacher', $session->teacher);
            $schedule->class_id = $session->class_id;
            $schedule->start_time = $session->start_time;
            $schedule->end_time = $session->end_time;
            $schedule->day = $session->lesson_date->locale('id')->dayName;
        }
        $attendances = Attendance::with('student.user')->where('schedule_id', $schedule->id)->where('attendance_date', $sessionDate)->orderBy('timestamp')->get();
        $presentCount = $attendances->where('status', 'hadir')->count();
        $date = now()->translatedFormat('l, d F Y - H:i');

        return Pdf::loadView('reports.attendance', compact('schedule', 'attendances', 'date', 'sessionDate', 'presentCount'))->stream('Laporan_Sesi_'.$schedule->id.'_'.$sessionDate.'.pdf');
    }

    public function truncate(ReportFilterRequest $request)
    {
        $filters = $request->validated();
        if (empty($filters['academic_year_id'])) {
            throw ValidationException::withMessages(['academic_year_id' => 'Pilih satu tahun ajaran untuk penghapusan.']);
        }
        $query = AttendanceReport::query($filters);
        if ((clone $query)->whereNotNull('attendance_session_id')->exists()) {
            throw ValidationException::withMessages(['report' => 'Presensi yang terhubung sesi dipertahankan bersama riwayatnya. Gunakan koreksi dengan alasan.']);
        }
        $query->whereNull('attendance_session_id')->delete();

        return back()->with('success', 'Absensi pada rentang dan tahun yang dipilih berhasil dihapus.');
    }
}
