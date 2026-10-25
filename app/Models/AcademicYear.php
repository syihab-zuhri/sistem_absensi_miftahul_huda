<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
        'active_semester',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'start_date' => 'date', 'end_date' => 'date'];
    }

    /**
     * Scope to get the active academic year.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * A year has many schedules.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * A year has many student class history records.
     */
    public function studentClassHistories()
    {
        return $this->hasMany(StudentClassHistory::class);
    }
}
