<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Services\StudentProvisioner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToModel, WithHeadingRow
{
    private int $currentRow = 1;

    private array $errors = [];

    private array $credentials = [];

    private array $createdPaths = [];

    private AcademicYear $activeYear;

    public function __construct()
    {
        $this->activeYear = AcademicYear::active()->first() ?? throw ValidationException::withMessages(['file' => 'Aktifkan tahun ajaran sebelum impor.']);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getInitialCredentials(): array
    {
        return $this->credentials;
    }

    public function cleanupRolledBackFiles(): void
    {
        foreach ($this->createdPaths as $path) {
            if (! Student::where('qr_code_path', $path)->exists()) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    public function model(array $row)
    {
        $this->currentRow++;
        if ($this->currentRow > 1001) {
            throw ValidationException::withMessages(['file' => 'Maksimal 1000 siswa dalam satu file impor.']);
        }
        if (! array_filter($row, fn ($value) => $value !== null && $value !== '')) {
            return null;
        }
        $data = ['nisn' => trim((string) ($row['nisn'] ?? '')), 'nama' => trim((string) ($row['nama'] ?? '')), 'kelas' => trim((string) ($row['kelas'] ?? ''))];
        $validator = Validator::make($data, ['nisn' => ['required', 'regex:/^\d{1,20}$/'], 'nama' => 'required|string|max:255', 'kelas' => 'required|string|max:255']);
        $classroom = Classroom::whereRaw('LOWER(name) = ?', [strtolower($data['kelas'])])->first();
        if ($validator->fails() || ! $classroom) {
            $this->errors[] = ['row' => $this->currentRow, 'nama' => $data['nama'], 'nisn' => $data['nisn'], 'kelas' => $data['kelas'], 'keterangan' => $validator->fails() ? $validator->errors()->first() : 'Kelas tidak terdaftar'];

            return null;
        }
        $student = Student::where('nisn', $data['nisn'])->first();
        if ($student) {
            // Import is not an enrollment/reenrollment action and must not reactivate alumni.
            if ($student->status !== 'aktif') {
                return $student;
            }
            DB::transaction(function () use ($student, $data, $classroom) {
                $student->user->update(['name' => $data['nama']]);
                $student->update(['class_id' => $classroom->name]);
                StudentClassHistory::updateOrCreate(['student_id' => $student->id, 'academic_year_id' => $this->activeYear->id], ['class_id' => $classroom->name, 'status' => $student->status]);
            });

            return $student;
        }
        $data['class_id'] = $classroom->name;
        $result = app(StudentProvisioner::class)->create(['nisn' => $data['nisn'], 'name' => $data['nama'], 'class_id' => $classroom->name]);
        $this->credentials[] = $result['credential'];
        $this->createdPaths[] = $result['student']->qr_code_path;

        return $result['student'];
    }
}
