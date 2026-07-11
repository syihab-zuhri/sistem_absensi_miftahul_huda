<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;

class StudentController extends Controller{
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
            $query->where(function($q) use ($search) {
                $q->where('students.nisn', 'like', "%{$search}%")
                ->orWhere('students.class_id', 'like', "%{$search}%")
                ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(50)->withQueryString();
        $classrooms = \App\Models\Classroom::orderBy('name')->get();

        return view('admin.students.index', compact('students', 'classrooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nisn' => 'required|string|unique:students,nisn',
            'class_id' => 'required|string',
        ]);

        // Cek tahun ajaran aktif
        $activeYear = \App\Models\AcademicYear::active()->first();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tidak ada tahun ajaran aktif. Silakan hubungi admin untuk mengaktifkan tahun ajaran.');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->nisn . '@siswa.com',
            'password' => Hash::make($request->nisn),
        ]);

        $user->assignRole('siswa');

        $student = Student::create([
            'user_id' => $user->id,
            'nisn' => $request->nisn,
            'class_id' => $request->class_id,
            'status' => 'aktif',
        ]);

        // Buat history kelas siswa untuk tahun ajaran aktif
        \App\Models\StudentClassHistory::create([
            'student_id' => $student->id,
            'academic_year_id' => $activeYear->id,
            'class_id' => $request->class_id,
            'status' => 'aktif',
        ]);

        if (!Storage::disk('public')->exists('qrcodes')) {
            Storage::disk('public')->makeDirectory('qrcodes');
        }

        $qr = QrCode::format('svg')->size(300)->margin(2)->generate($student->nisn);
        $path = 'qrcodes/' . $student->nisn . '.svg';
        Storage::disk('public')->put($path, $qr);

        $student->update(['qr_code_path' => $path]);

        return redirect()->back()->with('success', 'Siswa berhasil ditambahkan dan QR Code telah dibuat.');
    }

    // Fungsi Update (Edit) Siswa
    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|string',
            // Pastikan NISN unik, KECUALI untuk siswa ini sendiri
            'nisn' => 'required|string|unique:students,nisn,' . $student->id,
        ]);

        // Cek apakah NISN berubah (karena mempengaruhi QR dan Email)
        $nisnChanged = $student->nisn !== $request->nisn;
        $classChanged = $student->class_id !== $request->class_id;

        // 1. Update User
        $student->user->update([
            'name' => $request->name,
            'email' => $nisnChanged ? $request->nisn . '@siswa.com' : $student->user->email,
        ]);

        // 2. Update Student
        $student->update([
            'nisn' => $request->nisn,
            'class_id' => $request->class_id,
        ]);

        // 3. Update history tahun ajaran aktif jika kelas berubah
        if ($classChanged) {
            $activeYear = \App\Models\AcademicYear::active()->first();
            if ($activeYear) {
                \App\Models\StudentClassHistory::updateOrCreate(
                    ['student_id' => $student->id, 'academic_year_id' => $activeYear->id],
                    ['class_id' => $request->class_id, 'status' => $student->status]
                );
            }
        }

        // 4. Generate Ulang QR Code jika NISN berubah
        if ($nisnChanged) {
            // Hapus QR lama
            if ($student->qr_code_path && Storage::disk('public')->exists($student->qr_code_path)) {
                Storage::disk('public')->delete($student->qr_code_path);
            }

            // Buat QR Baru
            $qr = QrCode::format('svg')->size(300)->margin(2)->generate($student->nisn);
            $path = 'qrcodes/' . $student->nisn . '.svg';
            Storage::disk('public')->put($path, $qr);
            $student->update(['qr_code_path' => $path]);
        }

        return redirect()->back()->with('success', 'Data siswa berhasil diperbarui.');
    }


    public function destroy(Student $student)
    {
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
            $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($svgContent);
        }

        $printDate = \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y - H:i');

        // A4 landscape, kita atur margin dan konten via CSS di dalam PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.students.card', compact('student', 'qrBase64', 'printDate'))
            ->setPaper('A4', 'landscape');

        return $pdf->stream('QR_Siswa_' . $student->nisn . '.pdf');
    }

    // Fitur Baru: Hapus Massal (Roadmap Item 3)
    public function destroyBulk(Request $request)
    {
        $ids = $request->input('student_ids');
        if (!$ids || count($ids) == 0) {
            return redirect()->back()->with('error', 'Tidak ada siswa yang dipilih.');
        }

        $students = Student::whereIn('id', $ids)->get();

        foreach ($students as $student) {
            if ($student->qr_code_path && Storage::disk('public')->exists($student->qr_code_path)) {
                Storage::disk('public')->delete($student->qr_code_path);
            }
            $student->user->delete(); // Menghapus user otomatis cascade menghapus student
        }

        return redirect()->back()->with('success', count($students) . ' data siswa berhasil dihapus.');
    }

    public function updateClassBulk(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            // Validasi Keamanan: Pastikan new_class_id benar-benar ada di database berdasarkan nama kelas
            'new_class_id' => 'required|string|max:255|exists:classrooms,name',
        ]);

        $ids = $request->input('student_ids');
        $newClass = strtoupper($request->input('new_class_id'));

        // Hanya pindahkan siswa yang statusnya aktif
        $students = Student::whereIn('id', $ids)->where('status', 'aktif')->get();

        if ($students->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada siswa aktif yang dipilih untuk dipindahkan.');
        }

        // Ambil tahun ajaran aktif
        $activeYear = \App\Models\AcademicYear::active()->first();

        // Update semua siswa yang dipilih dengan kelas yang baru
        foreach ($students as $student) {
            $student->update(['class_id' => $newClass]);

            // Update/insert history tahun ajaran aktif
            if ($activeYear) {
                \App\Models\StudentClassHistory::updateOrCreate(
                    ['student_id' => $student->id, 'academic_year_id' => $activeYear->id],
                    ['class_id' => $newClass, 'status' => 'aktif']
                );
            }
        }

        return redirect()->back()->with('success', count($students) . ' siswa berhasil dipindahkan ke kelas ' . $newClass . '.');
    }

    public function updateStatusBulk(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'status' => 'required|in:aktif,lulus,pindah,keluar',
        ]);

        $ids = $request->input('student_ids');
        $newStatus = $request->input('status');

        $students = Student::whereIn('id', $ids)->get();

        if ($students->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada siswa yang dipilih.');
        }

        // Ambil tahun ajaran aktif
        $activeYear = \App\Models\AcademicYear::active()->first();

        foreach ($students as $student) {
            // Update status siswa
            $student->update(['status' => $newStatus]);

            // Update status di history tahun ajaran aktif
            if ($activeYear) {
                \App\Models\StudentClassHistory::updateOrCreate(
                    ['student_id' => $student->id, 'academic_year_id' => $activeYear->id],
                    ['status' => $newStatus]
                );
            }
        }

        $statusLabel = [
            'aktif' => 'diaktifkan kembali',
            'lulus' => 'diluluskan',
            'pindah' => 'dipindahkan',
            'keluar' => 'dikeluarkan',
        ];

        return redirect()->back()->with('success', count($students) . ' siswa berhasil ' . ($statusLabel[$newStatus] ?? 'diubah statusnya') . '.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx'
        ]);

        try {
            $import = new StudentsImport();
            Excel::import($import, $request->file('file'));

            $errors = $import->getErrors();

            if (count($errors) > 0) {
                return redirect()->back()
                    ->with('warning', count($errors) . ' siswa gagal diimpor karena kelas tidak ditemukan di database.')
                    ->with('import_errors', $errors);
            }

            return redirect()->back()->with('success', 'Semua data siswa berhasil diimport!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengimport file: ' . $e->getMessage());
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
            $query->where(function($q) use ($search) {
                $q->where('students.nisn', 'like', "%{$search}%")
                ->orWhere('students.class_id', 'like', "%{$search}%")
                ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(50)->withQueryString();
        $classrooms = \App\Models\Classroom::orderBy('name')->get();

        return view('guru.students.index', compact('students', 'classrooms'));
    }
}