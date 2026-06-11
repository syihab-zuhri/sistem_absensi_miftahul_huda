<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\QRCodeController;
use App\Http\Controllers\ReportController;

// ==========================================
// HALAMAN AWAL
// ==========================================
Route::get('/', function () {
    return redirect()->route('login');
});

// ==========================================
// AREA WAJIB LOGIN
// ==========================================
Route::middleware('auth')->group(function () {

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
        if ($user->hasRole('siswa')) return redirect()->route('student.portal');
        if ($user->hasRole('guru')) return redirect()->route('guru.dashboard');
        if ($user->hasRole('admin')) return redirect()->route('admin.dashboard');
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
        Route::get('/guru/students', [\App\Http\Controllers\StudentController::class, 'guruIndex'])
            ->name('guru.students.index');
    });

    // ==========================================
    // KHUSUS ROLE: ADMIN SAJA
    // ==========================================
    Route::middleware(['role:admin'])->group(function () {
        // Dashboard Admin
        Route::get('/admin/dashboard', function () {
            $recentAttendances = \App\Models\Attendance::with(['student.user', 'schedule.subject'])
                ->orderBy('timestamp', 'desc')->take(5)->get();
            return view('admin.dashboard', compact('recentAttendances'));
        })->name('admin.dashboard');

        // ==========================================
        // CRUD SISWA
        // ==========================================
        Route::post('/admin/students/import', [\App\Http\Controllers\StudentController::class, 'import'])->name('admin.students.import');
        Route::delete('/admin/students/bulk-delete', [\App\Http\Controllers\StudentController::class, 'destroyBulk'])->name('students.destroyBulk');
        
        // Rute POST Edit Kelas (Harus di atas resource dan satu-satunya)
        Route::post('/admin/students/bulk-update-class', [\App\Http\Controllers\StudentController::class, 'updateClassBulk'])->name('students.updateClassBulk');
        
        Route::resource('/admin/students', \App\Http\Controllers\StudentController::class)->except(['create', 'show', 'edit']);

        // ==========================================
        // CRUD JADWAL
        // ==========================================
        Route::delete('/admin/schedules/truncate', [\App\Http\Controllers\ScheduleController::class, 'truncate'])->name('schedules.truncate');
        Route::delete('/admin/schedules/bulk-delete', [\App\Http\Controllers\ScheduleController::class, 'destroyBulk'])->name('schedules.destroyBulk');
        
        // TAMBAHAN LANGKAH 4: Rute untuk Toggle ON/OFF Semester
        Route::post('/admin/schedules/toggle', [\App\Http\Controllers\ScheduleController::class, 'toggleSemester'])->name('schedules.toggle');
        
        Route::resource('/admin/schedules', \App\Http\Controllers\ScheduleController::class)->except(['create', 'show', 'edit']);

        // ==========================================
        // CRUD USER
        // ==========================================
        Route::resource('/admin/users', \App\Http\Controllers\UserController::class);

        // ==========================================
        // CRUD MATA PELAJARAN
        // ==========================================
        Route::post('/admin/subjects/import', [\App\Http\Controllers\SubjectController::class, 'import'])->name('admin.subjects.import');
        Route::resource('/admin/subjects', \App\Http\Controllers\SubjectController::class)->except(['create', 'show', 'edit']);

        // ==========================================
        // CRUD KELAS
        // ==========================================
        Route::resource('/admin/classrooms', \App\Http\Controllers\ClassroomController::class)->except(['create', 'show', 'edit']);

        // ==========================================
        // RUTE RESET ABSENSI (HANYA ADMIN)
        // ==========================================
        Route::delete('/admin/reports/truncate', [\App\Http\Controllers\ReportController::class, 'truncate'])->name('reports.truncate');

        // TEST QR
        Route::view('/test-qr', 'test-qr')->name('test.qr');
    });

    // ==========================================
    // FITUR BERSAMA (GURU & ADMIN BISA AKSES)
    // ==========================================
    Route::middleware(['role:guru|admin'])->group(function () {
        
        // Modul Laporan Filter
        Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/custom-pdf', [\App\Http\Controllers\ReportController::class, 'exportCustomPDF'])->name('reports.custom.pdf');
        Route::get('/reports/custom-excel', [\App\Http\Controllers\ReportController::class, 'exportCustomExcel'])->name('reports.custom.excel');

        // Modul Laporan per Sesi (Dari Scanner)
        Route::get('/reports/session/{schedule_id}', [\App\Http\Controllers\ReportController::class, 'exportSessionPDF'])->name('reports.session');


        Route::get('/scanner', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('scanner.index');
        // Modul Scanner
        // Route::get('/scanner', function () {
        //     $activeSchedule = app(\App\Http\Controllers\AttendanceController::class)->getActiveSession();
        //     return view('scanner', compact('activeSchedule'));
        // })->name('scanner.index');

        // Modul API / Pemrosesan Absensi (BATCH SAVE)
        Route::get('/absensi/session-data/{schedule_id}', [\App\Http\Controllers\AttendanceController::class, 'getSessionData']);
        Route::post('/absensi/save-session/{schedule_id}', [\App\Http\Controllers\AttendanceController::class, 'saveSession']);
    });

    // ==========================================
    // KHUSUS ROLE: SISWA SAJA
    // ==========================================
    Route::middleware(['role:siswa'])->group(function () {
        Route::get('/student/portal', [\App\Http\Controllers\StudentPortalController::class, 'index'])->name('student.portal');
        Route::post('/student/password', [\App\Http\Controllers\StudentPortalController::class, 'updatePassword'])->name('student.password.update');
    });
});

require __DIR__.'/auth.php';