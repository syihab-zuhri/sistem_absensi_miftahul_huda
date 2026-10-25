<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SaveAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->hasAnyRole(['admin', 'guru'])) {
            return false;
        }
        $session = $this->route('session');
        if ($session instanceof AttendanceSession) {
            return Gate::allows('record', $session);
        }

        return Gate::allows('record', Schedule::findOrFail($this->route('schedule_id')));
    }

    public function rules(): array
    {
        return [
            'attendances' => ['required', 'array', 'min:1', 'max:500'],
            'attendances.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'attendances.*.status' => ['required', Rule::in(Attendance::STATUSES)],
            'attendances.*.expected_status' => ['sometimes', 'nullable', Rule::in(Attendance::STATUSES)],
            'reason' => ['nullable', 'string', 'min:10', 'max:1000'],
        ];
    }
}
