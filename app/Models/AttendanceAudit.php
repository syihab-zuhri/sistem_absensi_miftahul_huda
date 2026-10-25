<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceAudit extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['before_values' => 'array', 'after_values' => 'array', 'created_at' => 'datetime'];
    }

    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
