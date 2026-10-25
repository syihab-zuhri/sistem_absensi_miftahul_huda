<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendancesExport extends StringValueBinder implements FromQuery, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    private int $row = 0;

    public function __construct(private Builder $attendances, private array $metadata) {}

    public function query(): Builder
    {
        return $this->attendances;
    }

    public function headings(): array
    {
        return [
            ['LAPORAN REKAPITULASI ABSENSI SISWA'], ['Periode', $this->metadata['dateRange']],
            ['Kelas', $this->metadata['className'], 'Mata pelajaran', $this->metadata['subjectName']],
            ['Tahun ajaran', $this->metadata['academicYearName']], ['Dicetak', $this->metadata['printDate'], auth()->user()->name],
            ['No', 'Waktu Pencatatan', 'NISN', 'Nama Siswa', 'Kelas', 'Mata Pelajaran', 'Status'],
        ];
    }

    public function map($attendance): array
    {
        return [++$this->row, $attendance->timestamp, $attendance->student?->nisn ?? '-', $attendance->student?->user?->name ?? 'Siswa Terhapus', $attendance->schedule?->class_id ?? '-', $attendance->schedule?->subject?->name ?? '-', ucfirst($attendance->status)];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'size' => 14]], 6 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DBEAFE']]]];
    }
}
