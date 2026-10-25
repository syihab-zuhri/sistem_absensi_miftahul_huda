<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentClassHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClassroomController extends Controller
{
    public function index()
    {
        return view('admin.classrooms.index', ['classrooms' => Classroom::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $request->merge(['name' => strtoupper(trim((string) $request->name))]);
        $data = $request->validate(['name' => 'required|string|max:255|unique:classrooms,name']);
        Classroom::create($data);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, Classroom $classroom)
    {
        $request->merge(['name' => strtoupper(trim((string) $request->name))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('classrooms', 'name')->ignore($classroom->id)]]);
        DB::transaction(function () use ($classroom, $data) {
            $locked = Classroom::lockForUpdate()->findOrFail($classroom->id);
            $old = $locked->name;
            $locked->update($data);
            foreach ([Student::class, Schedule::class, StudentClassHistory::class] as $model) {
                $model::where('class_id', $old)->update(['class_id' => $data['name']]);
            }
            AttendanceSession::where('class_id', $old)->where('status', 'planned')->update(['class_id' => $data['name']]);
        });

        return back()->with('success', 'Nama kelas dan data terkait berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom)
    {
        foreach ([Student::class, Schedule::class, StudentClassHistory::class] as $model) {
            if ($model::where('class_id', $classroom->name)->exists()) {
                throw ValidationException::withMessages(['classroom' => 'Kelas masih digunakan oleh data akademik.']);
            }
        }
        $classroom->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }
}
