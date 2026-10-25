<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AttendanceAuditController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceSessionController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\StudentSettingsController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\RequirePasswordChange;
use Illuminate\Support\Facades\Route;

// ==========================================
// HALAMAN AWAL
// ==========================================
Route::get('/', function () {
    return redirect()->route('login');
});

// ==========================================
// AREA WAJIB LOGIN
// ==========================================
Route::middleware(['auth', 'auth.session', RequirePasswordChange::class])->group(function () {

    // ==========================================
    // PROFIL USER
    // ==========================================
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ==========================================
    // DASHBOARD CERDAS (POLISI LALU LINTAS)
    // ==========================================
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasRole('guru')) {
            return redirect()->route('guru.dashboard');
        }
        if ($user->hasRole('siswa')) {
            return redirect()->route('student.portal');
        }
        abort(403);
    })->name('dashboard');

    // ==========================================
    // KHUSUS ROLE: GURU SAJA
    // ==========================================
    Route::middleware(['role:guru'])->group(function () {
        Route::get('/guru/dashboard', function () {
            return view('guru.dashboard');
        })->name('guru.dashboard');

        // Data Siswa untuk Guru
        Route::get('/guru/students', [StudentController::class, 'guruIndex'])
            ->name('guru.students.index');
    });

    // ==========================================
    // KHUSUS ROLE: ADMIN SAJA
    // ==========================================
    Route::middleware(['role:admin'])->group(function () {
        // Dashboard Admin
        Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        // ==========================================
        // CRUD SISWA
        // ==========================================
        Route::post('/admin/students/import', [StudentController::class, 'import'])->name('admin.students.import');
        Route::post('/admin/students/{student}/reset-password', [StudentController::class, 'resetPassword'])->name('admin.students.reset-password');
        Route::delete('/admin/students/bulk-delete', [StudentController::class, 'destroyBulk'])->name('students.destroyBulk');
        Route::post('/admin/students/bulk-update-status', [StudentController::class, 'updateStatusBulk'])->name('students.updateStatusBulk');

        // Rute POST Edit Kelas (Harus di atas resource dan satu-satunya)
        Route::post('/admin/students/bulk-update-class', [StudentController::class, 'updateClassBulk'])->name('students.updateClassBulk');

        // Download Kartu Pelajar (PDF)
        Route::get('/admin/students/{student}/card', [StudentController::class, 'downloadCard'])->name('admin.students.card');

        Route::resource('/admin/students', StudentController::class)->except(['create', 'show', 'edit']);

        // ==========================================
        // CRUD JADWAL
        // ==========================================
        Route::delete('/admin/schedules/truncate', [ScheduleController::class, 'truncate'])->name('admin.schedules.truncate');
        Route::delete('/admin/schedules/bulk-delete', [ScheduleController::class, 'destroyBulk'])->name('admin.schedules.destroyBulk');

        // TAMBAHAN LANGKAH 4: Rute untuk Toggle ON/OFF Semester
        Route::post('/admin/schedules/toggle', [ScheduleController::class, 'toggleSemester'])->name('admin.schedules.toggle');

        Route::delete('/admin/schedules/truncate-by-year', [ScheduleController::class, 'truncateByYear'])->name('admin.schedules.truncateByYear');

        Route::resource('/admin/schedules', ScheduleController::class)
            ->except(['create', 'show', 'edit'])
            ->names('admin.schedules');

        // ==========================================
        // CRUD USER
        // ==========================================
        Route::resource('/admin/users', UserController::class)->only(['index', 'store', 'destroy']);

        // ==========================================
        // CRUD MATA PELAJARAN
        // ==========================================
        Route::post('/admin/subjects/import', [SubjectController::class, 'import'])->name('admin.subjects.import');
        Route::resource('/admin/subjects', SubjectController::class)->except(['create', 'show', 'edit']);

        // ==========================================
        // CRUD KELAS
        // ==========================================
        Route::resource('/admin/classrooms', ClassroomController::class)->except(['create', 'show', 'edit']);

        // ==========================================
        // CRUD TAHUN AJARAN
        // ==========================================
        Route::resource('/admin/academic-years', AcademicYearController::class)
            ->except(['show'])
            ->names('admin.academic-years');
        Route::post('/admin/academic-years/{academicYear}/activate', [AcademicYearController::class, 'activate'])->name('admin.academic-years.activate');

        // ==========================================
        // RUTE RESET ABSENSI (HANYA ADMIN)
        // ==========================================
        Route::delete('/admin/reports/truncate', [ReportController::class, 'truncate'])->name('reports.truncate');

    });

    // ==========================================
    // FITUR BERSAMA (GURU & ADMIN BISA AKSES)
    // ==========================================
    Route::middleware(['role:guru|admin'])->group(function () {

        Route::get('/attendance-sessions', [AttendanceSessionController::class, 'index'])->name('attendance-sessions.index');
        Route::post('/attendance-sessions', [AttendanceSessionController::class, 'store'])->name('attendance-sessions.store');
        Route::get('/attendance-sessions/{session}', [AttendanceSessionController::class, 'show'])->name('attendance-sessions.show');
        Route::patch('/attendance-sessions/{session}', [AttendanceSessionController::class, 'update'])->name('attendance-sessions.update');
        Route::post('/attendance-sessions/{session}/open', [AttendanceSessionController::class, 'open'])->name('attendance-sessions.open');
        Route::post('/attendance-sessions/{session}/close', [AttendanceSessionController::class, 'close'])->name('attendance-sessions.close');
        Route::post('/attendance-sessions/{session}/cancel', [AttendanceSessionController::class, 'cancel'])->name('attendance-sessions.cancel');
        Route::get('/attendance-sessions/{session}/scanner', [AttendanceSessionController::class, 'scanner'])->name('attendance-sessions.scanner');
        Route::get('/attendance-sessions/{session}/roster', [AttendanceSessionController::class, 'roster'])->name('attendance-sessions.roster');
        Route::post('/attendance-sessions/{session}/attendances', [AttendanceSessionController::class, 'save'])->name('attendance-sessions.save');
        Route::post('/attendance-sessions/{session}/corrections', [AttendanceSessionController::class, 'correct'])->name('attendance-sessions.correct');
        Route::get('/attendance-audits', [AttendanceAuditController::class, 'index'])->name('attendance-audits.index');

        // Modul Laporan Filter
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/custom-pdf', [ReportController::class, 'exportCustomPDF'])->name('reports.custom.pdf');
        Route::get('/reports/custom-excel', [ReportController::class, 'exportCustomExcel'])->name('reports.custom.excel');

        // Modul Laporan per Sesi (Dari Scanner)
        Route::get('/reports/session/{schedule_id}', [ReportController::class, 'exportSessionPDF'])->name('reports.session');

        Route::get('/scanner', [AttendanceController::class, 'index'])->name('scanner.index');
        // Modul Scanner
        // Route::get('/scanner', function () {
        //     $activeSchedule = app(\App\Http\Controllers\AttendanceController::class)->getActiveSession();
        //     return view('scanner', compact('activeSchedule'));
        // })->name('scanner.index');

        // Modul API / Pemrosesan Absensi (BATCH SAVE)
        Route::get('/absensi/session-data/{schedule_id}', [AttendanceController::class, 'getSessionData']);
        Route::post('/absensi/save-session/{schedule_id}', [AttendanceController::class, 'saveSession']);
    });

    Route::middleware(['role:guru|admin|siswa'])->group(function () {
        Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
        Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->middleware('throttle:20,1')->name('leave-requests.store');
        Route::post('/leave-requests/{leaveRequest}/review', [LeaveRequestController::class, 'review'])->name('leave-requests.review');
        Route::post('/leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');
        Route::get('/leave-requests/{leaveRequest}/attachment', [LeaveRequestController::class, 'attachment'])->name('leave-requests.attachment');
    });

    // ==========================================
    // KHUSUS ROLE: SISWA SAJA
    // ==========================================
    Route::middleware(['role:siswa'])->group(function () {
        Route::get('/student/portal', [StudentPortalController::class, 'index'])->name('student.portal');
        Route::get('/student/settings', [StudentSettingsController::class, 'index'])->name('student.settings');
        Route::post('/student/password', [StudentSettingsController::class, 'updatePassword'])->name('student.password.update');
        Route::post('/student/email', [StudentSettingsController::class, 'updateEmail'])->name('student.email.update');
    });
});

require __DIR__.'/auth.php';
