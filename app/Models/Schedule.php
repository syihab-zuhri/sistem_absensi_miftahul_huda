<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'teacher_id',
        'class_id',
        'day',
        'start_time',
        'end_time',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // Relasi ke Guru (User)
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}