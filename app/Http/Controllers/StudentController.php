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

        // Fitur Pencarian 
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('students.nisn', 'like', "%{$search}%")
                ->orWhere('students.class_id', 'like', "%{$search}%")
                ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        $students = $query->paginate(15)->withQueryString();

        return view('admin.students.index', compact('students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nisn' => 'required|string|unique:students,nisn',
            'class_id' => 'required|string',
        ]);

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

        // 3. Generate Ulang QR Code jika NISN berubah
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
            'new_class_id' => 'required|string|max:255',
        ]);

        $ids = $request->input('student_ids');
        $newClass = strtoupper($request->input('new_class_id'));

        // Update semua siswa yang dipilih dengan kelas yang baru
        Student::whereIn('id', $ids)->update(['class_id' => $newClass]);

        return redirect()->back()->with('success', count($ids) . ' siswa berhasil dipindahkan ke kelas ' . $newClass . '.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx'
        ]);

        try {
            Excel::import(new StudentsImport, $request->file('file'));
            return redirect()->back()->with('success', 'Data siswa berhasil diimport!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengimport file: ' . $e->getMessage());
        }
    }

    public function guruIndex(Request $request)
    {
        // REVISI LOGIKA URUTAN: Gabungkan dengan tabel users untuk mengurutkan nama A-Z
        $query = Student::select('students.*')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->with('user')
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

        $students = $query->paginate(15)->withQueryString();

        return view('guru.students.index', compact('students'));
    }
}