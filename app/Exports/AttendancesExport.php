<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AttendancesExport implements FromView, ShouldAutoSize
{
    protected $attendances;
    protected $dateRange;
    protected $className;
    protected $subjectName;
    protected $printDate;
    protected $printerName;
    protected $academicYearName;

    public function __construct($attendances, $dateRange, $className, $subjectName, $printDate, $printerName, $academicYearName)
    {
        $this->attendances = $attendances;
        $this->dateRange = $dateRange;
        $this->className = $className;
        $this->subjectName = $subjectName;
        $this->printDate = $printDate;
        $this->printerName = $printerName;
        $this->academicYearName = $academicYearName;
    }

    public function view(): View
    {
        return view('reports.excel', [
            'attendances' => $this->attendances,
            'dateRange' => $this->dateRange,
            'className' => $this->className,
            'subjectName' => $this->subjectName,
            'printDate' => $this->printDate,
            'printerName' => $this->printerName,
            'academicYearName' => $this->academicYearName,
        ]);
    }
}
