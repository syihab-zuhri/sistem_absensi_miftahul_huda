<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:255']);
        $academicYears = AcademicYear::query()->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderByDesc('is_active')->orderByDesc('name')->paginate(25)->withQueryString();

        return view('admin.academic-years.index', compact('academicYears'));
    }

    public function create()
    {
        return view('admin.academic-years.create');
    }

    public function edit(AcademicYear $academicYear)
    {
        return view('admin.academic-years.edit', compact('academicYear'));
    }

    private function validated(Request $request, ?AcademicYear $year = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('academic_years', 'name')->ignore($year?->id), function ($attribute, $value, $fail) {
                $parts = explode('/', $value);
                if (count($parts) === 2 && (int) $parts[1] !== (int) $parts[0] + 1) {
                    $fail('Tahun ajaran harus terdiri dari dua tahun berurutan.');
                }
            }],
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('start_date'), 'after_or_equal:start_date')],
            'is_active' => 'nullable|boolean',
        ]);
    }

    private function activateYear(AcademicYear $year): void
    {
        AcademicYear::orderBy('id')->lockForUpdate()->get();
        AcademicYear::query()->update(['is_active' => false]);
        $year->update(['is_active' => true]);
        Student::where('status', 'aktif')->chunkById(500, function ($students) use ($year) {
            foreach ($students as $student) {
                StudentClassHistory::firstOrCreate(['student_id' => $student->id, 'academic_year_id' => $year->id], ['class_id' => $student->class_id, 'status' => 'aktif', 'started_at' => today()]);
            }
        });
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $data) {
            $year = AcademicYear::create(array_merge($data, ['is_active' => false]));
            if ($request->boolean('is_active')) {
                $this->activateYear($year);
            }
        });

        return redirect()->route('admin.academic-years.index')->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $data = $this->validated($request, $academicYear);
        if (! $request->boolean('is_active') && $academicYear->is_active) {
            return back()->withInput()->with('error', 'Tahun ajaran aktif tidak dapat dinonaktifkan. Aktifkan tahun lain sebagai pengganti.');
        }
        DB::transaction(function () use ($request, $data, $academicYear) {
            $academicYear->update(collect($data)->except('is_active')->all());
            if ($request->boolean('is_active')) {
                $this->activateYear($academicYear);
            }
        });

        return redirect()->route('admin.academic-years.index')->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function activate(AcademicYear $academicYear)
    {
        DB::transaction(fn () => $this->activateYear($academicYear));

        return redirect()->route('admin.academic-years.index')->with('success', 'Tahun ajaran berhasil diaktifkan.');
    }

    public function destroy(AcademicYear $academicYear)
    {
        if ($academicYear->is_active || $academicYear->schedules()->exists() || $academicYear->studentClassHistories()->exists()) {
            return back()->with('error', 'Tahun ajaran aktif atau yang memiliki riwayat tidak dapat dihapus.');
        }
        $academicYear->delete();

        return back()->with('success', 'Tahun ajaran berhasil dihapus.');
    }
}
