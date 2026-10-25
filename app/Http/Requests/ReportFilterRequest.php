<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['start_date' => today()->toDateString(), 'end_date' => today()->toDateString(), 'academic_year_id' => AcademicYear::active()->value('id')]);
    }

    public function rules(): array
    {
        return [
            'start_date' => 'required|date_format:Y-m-d', 'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'class_id' => 'nullable|string|max:255|exists:classrooms,name', 'subject_id' => 'nullable|integer|exists:subjects,id',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $validator->errors()->hasAny(['start_date', 'end_date']) && Carbon::parse($this->start_date)->diffInDays($this->end_date) > 366) {
                $validator->errors()->add('end_date', 'Pilih rentang laporan maksimal 366 hari.');
            }
        }];
    }
}
