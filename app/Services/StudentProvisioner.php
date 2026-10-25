<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentProvisioner
{
    public function create(array $data): array
    {
        $year = AcademicYear::active()->first();
        if (! $year) {
            throw ValidationException::withMessages(['academic_year_id' => 'Aktifkan tahun ajaran terlebih dahulu.']);
        }
        $email = $data['nisn'].'@siswa.com';
        if (! LoginIdentity::emailAvailable($email)) {
            throw ValidationException::withMessages(['nisn' => 'Alamat akun untuk NISN ini sudah digunakan.']);
        }
        $password = Str::password(16);
        $path = 'qrcodes/'.$data['nisn'].'.svg';
        $existed = Storage::disk('public')->exists($path);
        try {
            $student = DB::transaction(function () use ($data, $year, $password, $path) {
                $user = User::create(['name' => $data['name'], 'email' => $data['nisn'].'@siswa.com', 'password' => $password, 'must_change_password' => true]);
                $user->assignRole('siswa');
                $student = Student::create(['user_id' => $user->id, 'nisn' => $data['nisn'], 'class_id' => $data['class_id'], 'status' => 'aktif']);
                StudentClassHistory::create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'class_id' => $student->class_id, 'status' => 'aktif', 'started_at' => today()]);
                $qr = QrCode::format('svg')->size(300)->margin(2)->generate($student->nisn);
                if (! Storage::disk('public')->put($path, $qr)) {
                    throw new \RuntimeException('Berkas QR tidak dapat disimpan.');
                }
                $student->update(['qr_code_path' => $path]);

                return $student;
            });
        } catch (\Throwable $exception) {
            if (! $existed) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }

        return ['student' => $student, 'credential' => ['name' => $data['name'], 'nisn' => $data['nisn'], 'password' => $password]];
    }
}
