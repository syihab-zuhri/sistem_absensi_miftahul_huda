<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class StudentsImport implements ToModel, WithHeadingRow
{
    private $currentRow = 1; // Mulai dari 1 karena header adalah baris 1
    private $errors = [];
    private $classrooms;

    public function __construct()
    {
        // Ambil semua nama kelas dan jadikan huruf besar untuk perbandingan
        $this->classrooms = \App\Models\Classroom::pluck('name')->map(function($name) {
            return strtoupper(trim($name));
        })->toArray();
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
    * Fungsi ini berjalan berulang-ulang untuk setiap baris di file Excel.
    */
    public function model(array $row)
    {
        $this->currentRow++;

        // 1. Lewati jika baris Excel kosong atau header tidak sesuai
        if (!isset($row['nisn']) || !isset($row['nama']) || !isset($row['kelas'])) {
            return null;
        }

        $kelasExcel = strtoupper(trim($row['kelas']));

        // Validasi Kelas: Jika kelas tidak ada di database, catat sebagai error dan lewati
        if (!in_array($kelasExcel, $this->classrooms)) {
            $this->errors[] = [
                'row' => $this->currentRow,
                'nama' => $row['nama'],
                'nisn' => $row['nisn'],
                'kelas' => $row['kelas'],
                'keterangan' => 'Kelas tidak terdaftar di database'
            ];
            return null; // Skip penyimpanan untuk siswa ini
        }

        // 2. LOGIKA MENCARI DAN MEMPERBARUI USER
        $email = $row['nisn'] . '@siswa.com';
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update(['name' => $row['nama']]);
        } else {
            $user = User::create([
                'name' => $row['nama'],
                'email' => $email,
                'password' => Hash::make($row['nisn']),
            ]);
        }
        
        if (!$user->hasRole('siswa')) {
            $user->assignRole('siswa');
        }

        // 3. LOGIKA UPDATE ATAU CREATE UNTUK TABEL STUDENT
        $student = Student::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nisn' => $row['nisn'],
                'class_id' => $kelasExcel, // Gunakan hasil uppercase
            ]
        );

        // 4. GENERATE QR CODE
        if (!$student->qr_code_path || !Storage::disk('public')->exists($student->qr_code_path)) {
            if (!Storage::disk('public')->exists('qrcodes')) {
                Storage::disk('public')->makeDirectory('qrcodes');
            }
            
            $qr = QrCode::format('svg')
                        ->size(300)
                        ->margin(2)
                        ->generate($student->nisn);
                        
            $path = 'qrcodes/' . $student->nisn . '.svg';
            Storage::disk('public')->put($path, $qr);
            
            $student->update(['qr_code_path' => $path]);
        }

        return $student;
    }
}