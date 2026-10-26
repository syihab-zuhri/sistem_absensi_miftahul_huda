<?php

namespace App\Http\Controllers;

use App\Imports\StudentsImport;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\User;
use App\Services\LoginIdentity;
use App\Services\StudentProvisioner;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        // REVISI LOGIKA URUTAN: Gabungkan dengan tabel users untuk mengurutkan nama A-Z
        $query = Student::select('students.*')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->with('user')
            ->orderBy('students.class_id', 'asc') // Urutkan kelas dulu (10, 11, 12)
            ->orderBy('users.name', 'asc');       // Lalu urutkan nama (A - Z)

        // Filter Status: Default hanya aktif, bisa filter all/lulus/pindah/keluar
        $status = $request->input('status', 'aktif');
        if ($status !== 'all') {
            $query->where('students.status', $status);
        }

        // Fitur Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('students.nisn', 'like', "%{$search}%")
                    ->orWhere('students.class_id', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(50)->withQueryString();
        $classrooms = Classroom::orderBy('name')->get();

        return view('admin.students.index', compact('students', 'classrooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nisn' => ['required', 'regex:/^\\d{1,20}$/', 'unique:students,nisn'],
            'class_id' => 'required|string|max:255|exists:classrooms,name',
        ]);

        $result = app(StudentProvisioner::class)->create($request->only('name', 'nisn', 'class_id'));

        return back()->with('success', 'Siswa berhasil ditambahkan. Bagikan sandi awal secara pribadi.')
            ->with('initial_credentials', [$result['credential']]);
    }

    // Fungsi Update (Edit) Siswa
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|string|max:255|exists:classrooms,name',
            // Pastikan NISN unik, KECUALI untuk siswa ini sendiri
            'nisn' => ['required', 'regex:/^\\d{1,20}$/', 'unique:students,nisn,'.$student->id],
        ]);

        if ($student->nisn !== $request->nisn && $student->user->email === LoginIdentity::studentLoginEmail($student->nisn)
            && ! LoginIdentity::emailAvailable(LoginIdentity::studentLoginEmail($request->nisn), $student->user_id, $student->id)) {
            throw ValidationException::withMessages(['nisn' => 'Alamat login untuk NISN baru sudah digunakan.']);
        }
        $oldPath = $student->qr_code_path;
        if ($student->class_id !== $request->class_id && ! AcademicYear::active()->exists()) {
            throw ValidationException::withMessages(['class_id' => 'Aktifkan tahun ajaran sebelum memindahkan kelas.']);
        }
        $newPath = 'qrcodes/'.$request->nisn.'.svg';
        try {
            DB::transaction(function () use ($request, $student) {
                // Cek apakah NISN berubah (karena mempengaruhi QR dan Email)
                $nisnChanged = $student->nisn !== $request->nisn;
                $classChanged = $student->class_id !== $request->class_id;

                // 1. Update User
                $student->user->update([
                    'name' => $request->name,
                    'email' => $nisnChanged && $student->user->email === LoginIdentity::studentLoginEmail($student->nisn) ? LoginIdentity::studentLoginEmail($request->nisn) : $student->user->email,
                ]);

                // 2. Update Student
                $student->update([
                    'nisn' => $request->nisn,
                    'class_id' => $request->class_id,
                ]);

                // 3. Update history tahun ajaran aktif jika kelas berubah
                if ($classChanged) {
                    $activeYear = AcademicYear::active()->first();
                    if ($activeYear) {
                        StudentClassHistory::updateOrCreate(
                            ['student_id' => $student->id, 'academic_year_id' => $activeYear->id],
                            ['class_id' => $request->class_id, 'status' => $student->status]
                        );
                    }
                }

                // 4. Generate Ulang QR Code jika NISN berubah
                if ($nisnChanged) {

                    // Buat QR Baru
                    $qr = QrCode::format('svg')->size(300)->margin(2)->generate($student->nisn);
                    $path = 'qrcodes/'.$student->nisn.'.svg';
                    if (! Storage::disk('public')->put($path, $qr)) {
                        throw new \RuntimeException('Berkas QR tidak dapat disimpan.');
                    }
                    $student->update(['qr_code_path' => $path]);
                }

            });
        } catch (\Throwable $exception) {
            if ($newPath !== $oldPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $exception;
        }
        if ($oldPath && $oldPath !== $student->qr_code_path) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function resetPassword(Student $student)
    {
        $user = $student->user;
        $password = Str::password(16);
        $user->update(['password' => $password, 'must_change_password' => true, 'remember_token' => Str::random(60)]);
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        return back()->with('success', 'Sandi awal baru berhasil dibuat.')->with('initial_credentials', [['name' => $user->name, 'nisn' => $student->nisn, 'password' => $password]]);
    }

    public function destroy(Student $student)
    {
        if ($student->attendances()->exists() || $student->archivedAttendances()->exists() || $student->attendanceSessions()->exists() || $student->leaveRequests()->exists() || $student->classHistories()->whereHas('academicYear', fn ($q) => $q->where('is_active', false))->exists()) {
            throw ValidationException::withMessages(['student' => 'Siswa dengan riwayat akademik tidak dapat dihapus. Gunakan perubahan status.']);
        }
        if ($student->qr_code_path && Storage::disk('public')->exists($student->qr_code_path)) {
            Storage::disk('public')->delete($student->qr_code_path);
        }
        $student->user->delete();

        return redirect()->back()->with('success', 'Data siswa dan akun berhasil dihapus.');
    }

    public function downloadCard(Student $student)
    {
        $student->load('user');

        $qrBase64 = null;
        if ($student->qr_code_path && Storage::disk('public')->exists($student->qr_code_path)) {
            $svgContent = Storage::disk('public')->get($student->qr_code_path);
            $qrBase64 = 'data:image/svg+xml;base64,'.base64_encode($svgContent);
        }

        $printDate = Carbon::now()->locale('id')->translatedFormat('d F Y - H:i');

        // A4 landscape, kita atur margin dan konten via CSS di dalam PDF
        $pdf = Pdf::loadView('admin.students.card', compact('student', 'qrBase64', 'printDate'))
            ->setPaper('A4', 'landscape');

        return $pdf->stream('QR_Siswa_'.$student->nisn.'.pdf');
    }

    // Fitur Baru: Hapus Massal (Roadmap Item 3)
    public function destroyBulk(Request $request)
    {
        $data = $request->validate(['student_ids' => 'required|array|min:1|max:1000', 'student_ids.*' => 'required|integer|distinct|exists:students,id']);
        $ids = $data['student_ids'];
        if (Student::whereIn('id', $ids)->where(fn ($q) => $q->whereHas('attendances')->orWhereHas('archivedAttendances')->orWhereHas('attendanceSessions')->orWhereHas('leaveRequests')->orWhereHas('classHistories', fn ($q) => $q->whereHas('academicYear', fn ($q) => $q->where('is_active', false))))->exists()) {
            throw ValidationException::withMessages(['student_ids' => 'Pilihan berisi siswa dengan riwayat akademik. Gunakan perubahan status.']);
        }
        $students = Student::whereIn('id', $ids)->get();

        foreach ($students as $student) {
            if ($student->qr_code_path && Storage::disk('public')->exists($student->qr_code_path)) {
                Storage::disk('public')->delete($student->qr_code_path);
            }
            $student->user->delete(); // Menghapus user otomatis cascade menghapus student
        }

        return redirect()->back()->with('success', count($students).' data siswa berhasil dihapus.');
    }

    public function updateClassBulk(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1|max:1000',
            'student_ids.*' => 'required|integer|distinct|exists:students,id',
            // Validasi Keamanan: Pastikan new_class_id benar-benar ada di database berdasarkan nama kelas
            'new_class_id' => 'required|string|max:255|exists:classrooms,name',
        ]);

        $ids = $request->input('student_ids');
        $newClass = $request->input('new_class_id');

        // Hanya pindahkan siswa yang statusnya aktif
        $students = Student::whereIn('id', $ids)->where('status', 'aktif')->get();

        if ($students->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada siswa aktif yang dipilih untuk dipindahkan.');
        }

        // Ambil tahun ajaran aktif
        $activeYear = AcademicYear::active()->first();
        if (! $activeYear) {
            throw ValidationException::withMessages(['academic_year_id' => 'Aktifkan tahun ajaran terlebih dahulu.']);
        }

        // Update semua siswa yang dipilih dengan kelas yang baru
        DB::transaction(function () use ($students, $activeYear, $newClass) {
            foreach ($students as $student) {
                $student->update(['class_id' => $newClass]);

                // Update/insert history tahun ajaran aktif
                if ($activeYear) {
                    StudentClassHistory::updateOrCreate(
                        ['student_id' => $student->id, 'academic_year_id' => $activeYear->id],
                        ['class_id' => $newClass, 'status' => 'aktif']
                    );
                }
            }
        });

        return redirect()->back()->with('success', count($students).' siswa berhasil dipindahkan ke kelas '.$newClass.'.');
    }

    public function updateStatusBulk(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array|min:1|max:1000',
            'student_ids.*' => 'required|integer|distinct|exists:students,id',
            'status' => 'required|in:aktif,lulus,pindah,keluar',
        ]);

        $ids = $request->input('student_ids');
        $newStatus = $request->input('status');

        $students = Student::whereIn('id', $ids)->get();

        if ($students->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada siswa yang dipilih.');
        }

        // Ambil tahun ajaran aktif
        $activeYear = AcademicYear::active()->first();
        if (! $activeYear) {
            throw ValidationException::withMessages(['academic_year_id' => 'Aktifkan tahun ajaran terlebih dahulu.']);
        }

        DB::transaction(function () use ($students, $activeYear, $newStatus) {
            foreach ($students as $student) {
                // Update status siswa
                $student->update(['status' => $newStatus]);

                // Update status di history tahun ajaran aktif
                if ($activeYear) {
                    StudentClassHistory::updateOrCreate(
                        ['student_id' => $student->id, 'academic_year_id' => $activeYear->id],
                        ['class_id' => $student->class_id, 'status' => $newStatus, 'ended_at' => $newStatus === 'aktif' ? null : today()]
                    );
                }
            }
        });

        $statusLabel = [
            'aktif' => 'diaktifkan kembali',
            'lulus' => 'diluluskan',
            'pindah' => 'dipindahkan',
            'keluar' => 'dikeluarkan',
        ];

        return redirect()->back()->with('success', count($students).' siswa berhasil '.($statusLabel[$newStatus] ?? 'diubah statusnya').'.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx|max:5120',
        ]);

        $import = new StudentsImport;
        try {
            Excel::import($import, $request->file('file'));

            $errors = $import->getErrors();

            if (count($errors) > 0) {
                return redirect()->back()
                    ->with('warning', count($errors).' siswa gagal diimpor karena kelas tidak ditemukan di database.')
                    ->with('import_errors', $errors)->with('initial_credentials', $import->getInitialCredentials());
            }

            return back()->with('success', 'Data siswa berhasil diimpor. Alumni tidak diaktifkan ulang.')->with('initial_credentials', $import->getInitialCredentials());
        } catch (\Throwable $e) {
            $import->cleanupRolledBackFiles();
            if ($e instanceof ValidationException) {
                throw $e;
            }
            report($e);

            return back()->with('error', 'Impor gagal. Periksa format file dan data yang diunggah.');
        }
    }

    public function guruIndex(Request $request)
    {
        // REVISI LOGIKA URUTAN: Gabungkan dengan tabel users untuk mengurutkan nama A-Z
        // Guru hanya melihat siswa aktif
        $query = Student::select('students.*')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->with('user')
            ->where('students.status', 'aktif')
            ->orderBy('students.class_id', 'asc') // Urutkan kelas dulu (10, 11, 12)
            ->orderBy('users.name', 'asc');       // Lalu urutkan nama (A - Z)

        // Fitur Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('students.nisn', 'like', "%{$search}%")
                    ->orWhere('students.class_id', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(50)->withQueryString();
        $classrooms = Classroom::orderBy('name')->get();

        return view('guru.students.index', compact('students', 'classrooms'));
    }
}
