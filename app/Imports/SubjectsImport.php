<?php

namespace App\Imports;

use App\Models\Subject;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SubjectsImport implements ToModel, WithHeadingRow
{
    /**
    * Format Header Excel baris pertama wajib bernama: "nama_mapel"
    */
    public function model(array $row)
    {
        // Cegah error jika ada baris kosong atau tidak ada header 'nama_mapel'
        if (!isset($row['nama_mapel'])) {
            return null;
        }

        // Cek apakah mata pelajaran sudah ada agar tidak ganda (duplikat)
        $existingSubject = Subject::where('name', $row['nama_mapel'])->first();
        if ($existingSubject) {
            return null; 
        }

        // Simpan mata pelajaran baru
        return new Subject([
            'name' => $row['nama_mapel'],
        ]);
    }
}