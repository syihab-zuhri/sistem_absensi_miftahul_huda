<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    // Kolom yang diizinkan untuk diisi secara massal
    protected $fillable = [
        'user_id',
        'nisn',
        'personal_email',
        'class_id',
        'qr_code_path',
        'status',
    ];

    /**
     * Relasi ke tabel Users
     * Seorang siswa adalah seorang User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke tabel Attendances (Kehadiran)
     * Seorang siswa bisa memiliki banyak catatan kehadiran
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Relasi ke riwayat kelas siswa (StudentClassHistory)
     */
    public function classHistories()
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    public function archivedAttendances()
    {
        return $this->hasMany(AttendanceDuplicateArchive::class);
    }

    public function attendanceSessions()
    {
        return $this->belongsToMany(AttendanceSession::class, 'attendance_session_students');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Scope to filter only active students.
     */
    public function scopeActive($query)
    {
        return $query->where('students.status', 'aktif');
    }
}
