<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = ['schedule_id', 'teacher_id', 'class_id', 'lesson_date', 'start_time', 'end_time', 'status', 'opened_at', 'closed_at', 'cancellation_reason'];

    protected function casts(): array
    {
        return ['lesson_date' => 'date', 'opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function setLessonDateAttribute($value): void
    {
        $this->attributes['lesson_date'] = Carbon::parse($value)->toDateString();
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'attendance_session_students');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function audits()
    {
        return $this->hasMany(AttendanceAudit::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->when(! $user->hasRole('admin'), fn ($q) => $q->where('teacher_id', $user->id));
    }

    public function isCurrentTerm(): bool
    {
        $year = $this->schedule->academicYear;

        return $year && $year->is_active && $this->schedule->semester === $year->active_semester
            && (! $year->start_date || $year->start_date->lte($this->lesson_date))
            && (! $year->end_date || $year->end_date->gte($this->lesson_date));
    }

    public function hasStarted(): bool
    {
        return $this->lesson_date->copy()->setTimeFromTimeString($this->start_time)->lte(now());
    }

    // Saving after this moment is a late recording that requires a reason.
    public function lateAfter(): Carbon
    {
        return $this->lesson_date->copy()->setTimeFromTimeString($this->end_time)->addMinutes(config('attendance.grace_minutes'));
    }

    public function isLate(): bool
    {
        return now()->gte($this->lateAfter());
    }

    public function accepts(Student $student): bool
    {
        return $this->opened_at
            ? $this->students()->whereKey($student->id)->exists()
            : $student->status === 'aktif' && $student->class_id === $this->class_id;
    }
}
