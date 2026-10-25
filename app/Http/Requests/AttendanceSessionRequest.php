<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'guru']);
    }

    public function rules(): array
    {
        return [
            'schedule_id' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'integer', 'exists:schedules,id'],
            'lesson_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['nullable', 'required_with:end_time', 'date_format:H:i,H:i:s'],
            'end_time' => ['nullable', 'required_with:start_time', 'date_format:H:i,H:i:s', 'after:start_time'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'reason' => [$this->isMethod('PATCH') ? 'required' : 'nullable', 'string', 'min:10', 'max:1000'],
        ];
    }
}
