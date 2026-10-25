<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    public const STATUSES = ['hadir', 'sakit', 'izin', 'alpa'];

    protected static function booted(): void
    {
        static::creating(function (self $attendance) {
            $attendance->timestamp ??= now();
            $attendance->attendance_date = Carbon::parse($attendance->timestamp)->toDateString();
        });
    }

    protected $fillable = [
        'schedule_id',
        'student_id',
        'status',
        'timestamp', // Menyimpan waktu presensi presisi
        'attendance_date',
        'attendance_session_id',
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

    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->when(! $user->hasRole('admin'), fn ($q) => $q->where(fn ($owned) => $owned
            ->whereHas('session', fn ($session) => $session->where('teacher_id', $user->id))
            ->orWhere(fn ($legacy) => $legacy->whereNull('attendance_session_id')->whereHas('schedule', fn ($schedule) => $schedule->where('teacher_id', $user->id)))));
    }
}
