<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index()
    {
        $classrooms = Classroom::latest()->get();
        return view('admin.classrooms.index', compact('classrooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classrooms,name',
        ]);

        Classroom::create(['name' => $request->name]);

        return redirect()->back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, Classroom $classroom)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classrooms,name,' . $classroom->id, 
        ]);

        $classroom->update(['name' => $request->name]);

        return redirect()->back()->with('success', 'Nama Kelas berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom)
    {
        $classroom->delete();
        return redirect()->back()->with('success', 'Kelas berhasil dihapus.');
    }
}
