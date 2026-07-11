<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\StudentClassHistory;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of the academic years.
     */
    public function index(Request $request)
    {
        $query = AcademicYear::query();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Order by active status (active first), then by name
        $academicYears = $query->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.academic-years.index', compact('academicYears'));
    }

    /**
     * Show the form for creating a new academic year.
     */
    public function create()
    {
        return view('admin.academic-years.create');
    }

    /**
     * Store a newly created academic year in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:academic_years,name',
                'regex:/^\d{4}\/\d{4}$/' // Format: YYYY/YYYY
            ],
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            // If this year is being set as active, deactivate all others
            if ($request->is_active) {
                AcademicYear::query()->update(['is_active' => false]);
            }

            $academicYear = AcademicYear::create([
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->is_active ?? false,
            ]);

            DB::commit();

            return redirect()->route('admin.academic-years.index')
                ->with('success', 'Tahun ajaran berhasil ditambahkan.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan tahun ajaran: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified academic year.
     */
    public function edit(AcademicYear $academicYear)
    {
        return view('admin.academic-years.edit', compact('academicYear'));
    }

    /**
     * Update the specified academic year in storage.
     */
    public function update(Request $request, AcademicYear $academicYear)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('academic_years', 'name')->ignore($academicYear->id),
                'regex:/^\d{4}\/\d{4}$/' // Format: YYYY/YYYY
            ],
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            // If this year is being set as active, deactivate all others
            if ($request->is_active && !$academicYear->is_active) {
                AcademicYear::query()->update(['is_active' => false]);
            }

            // If this year is being deactivated and it's the only active one, prevent it
            if (!$request->is_active && $academicYear->is_active) {
                $activeCount = AcademicYear::where('is_active', true)->count();
                if ($activeCount <= 1) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Tidak dapat menonaktifkan tahun ajaran ini karena harus ada minimal satu tahun ajaran aktif.');
                }
            }

            $academicYear->update([
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->is_active ?? $academicYear->is_active,
            ]);

            DB::commit();

            return redirect()->route('admin.academic-years.index')
                ->with('success', 'Tahun ajaran berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();

            // Handle unique constraint violation
            if ($e->getCode() === '23000') {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Nama tahun ajaran sudah digunakan.');
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui tahun ajaran: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified academic year from storage.
     */
    public function destroy(AcademicYear $academicYear)
    {
        // Check if the academic year has student class histories
        $studentCount = StudentClassHistory::where('academic_year_id', $academicYear->id)->count();

        // Check if the academic year has schedules
        $scheduleCount = Schedule::where('academic_year_id', $academicYear->id)->count();

        if ($studentCount > 0 || $scheduleCount > 0) {
            return redirect()->back()
                ->with('error', 'Tidak dapat menghapus tahun ajaran ini karena masih memiliki data terkait. ' .
                       '(' . $studentCount . ' siswa, ' . $scheduleCount . ' jadwal)');
        }

        // If this is the only active year, prevent deletion
        if ($academicYear->is_active) {
            $activeCount = AcademicYear::where('is_active', true)->count();
            if ($activeCount <= 1) {
                return redirect()->back()
                    ->with('error', 'Tidak dapat menghapus tahun ajaran aktif ini karena harus ada minimal satu tahun ajaran aktif.');
            }
        }

        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Tahun ajaran berhasil dihapus.');
    }

    /**
     * Set the specified academic year as active.
     */
    public function activate(AcademicYear $academicYear)
    {
        DB::beginTransaction();

        try {
            // Deactivate all academic years
            AcademicYear::query()->update(['is_active' => false]);

            // Activate the selected one
            $academicYear->update(['is_active' => true]);

            DB::commit();

            return redirect()->route('admin.academic-years.index')
                ->with('success', 'Tahun ajaran ' . $academicYear->name . ' berhasil diaktifkan.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Gagal mengaktifkan tahun ajaran: ' . $e->getMessage());
        }
    }
}