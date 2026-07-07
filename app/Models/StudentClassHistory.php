<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentClassHistory extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'class_id',
        'status',
        'started_at',
        'ended_at',
    ];

    /**
     * Get the student for this history record.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the academic year for this history record.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
