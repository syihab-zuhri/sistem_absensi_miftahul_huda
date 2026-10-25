<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceDuplicateArchive extends Model
{
    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['original_record' => 'array', 'archived_at' => 'datetime'];
    }
}
