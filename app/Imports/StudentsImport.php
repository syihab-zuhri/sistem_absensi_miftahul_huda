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
    /**
    * Fungsi ini berjalan berulang-ulang untuk setiap baris di file Excel.
    */
    public function model(array $row)
    {
        // 1. Lewati jika baris Excel kosong atau header tidak sesuai
        if (!isset($row['nisn']) || !isset($row['nama']) || !isset($row['kelas'])) {
            return null;
        }

        // 2. LOGIKA MENCARI DAN MEMPERBARUI USER (Revisi Anti Error Hash)
        $email = $row['nisn'] . '@siswa.com';
        $user = User::where('email', $email)->first();

        if ($user) {
            // Jika siswa sudah punya akun login, cukup UPDATE namanya saja.
            // Password DIBIARKAN UTUH agar password yang pernah diganti oleh siswa tidak kere-set.
            $user->update(['name' => $row['nama']]);
        } else {
            // Jika ini benar-benar siswa baru, CREATE akun beserta password default (NISN)
            $user = User::create([
                'name' => $row['nama'],
                'email' => $email,
                'password' => Hash::make($row['nisn']),
            ]);
        }
        
        // Pastikan role siswa diberikan (terutama untuk user baru)
        if (!$user->hasRole('siswa')) {
            $user->assignRole('siswa');
        }

        // 3. LOGIKA UPDATE ATAU CREATE UNTUK TABEL STUDENT
        // Cari data siswa berdasarkan user_id. 
        // Jika ketemu (misal: naik kelas), maka kelasnya di-UPDATE. Jika tidak, CREATE baru.
        $student = Student::updateOrCreate(
            ['user_id' => $user->id], // Kriteria Pencarian Utama
            [
                'nisn' => $row['nisn'],
                'class_id' => strtoupper($row['kelas']), // Ini yang akan berubah menjadi kelas baru
            ]
        );

        // 4. GENERATE QR CODE (Hanya jika belum punya QR Code atau file fisiknya hilang)
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
            
            // Simpan path ke database
            $student->update(['qr_code_path' => $path]);
        }

        return $student;
    }
}