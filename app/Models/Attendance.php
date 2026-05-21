<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'student_id',
        'status',
        'timestamp', // Menyimpan waktu presensi presisi
    ];

    public $timestamps = true; // Mengaktifkan created_at & updated_at

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}