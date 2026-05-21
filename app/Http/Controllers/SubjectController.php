<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SubjectsImport;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::latest()->get();
        return view('admin.subjects.index', compact('subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:subjects,name',
        ]);

        Subject::create(['name' => $request->name]);

        return redirect()->back()->with('success', 'Mata Pelajaran berhasil ditambahkan.');
    }

    // FUNGSI BARU: Untuk Mengedit Nama Mata Pelajaran
    public function update(Request $request, Subject $subject)
    {
        $request->validate([
            // Pengecualian unique untuk ID yang sedang diedit agar tidak error jika namanya tidak diganti
            'name' => 'required|string|max:255|unique:subjects,name,' . $subject->id, 
        ]);

        $subject->update(['name' => $request->name]);

        return redirect()->back()->with('success', 'Nama Mata Pelajaran berhasil diperbarui.');
    }

    public function destroy(Subject $subject)
    {
        // PENTING: Karena relasi database (cascade), menghapus mapel juga akan menghapus jadwal yang terkait!
        $subject->delete();
        return redirect()->back()->with('success', 'Mata Pelajaran berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx'
        ]);

        try {
            Excel::import(new SubjectsImport, $request->file('file'));
            return redirect()->back()->with('success', 'Data Mata Pelajaran berhasil diimport!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengimport: Pastikan format header adalah (nama_mapel). Error: ' . $e->getMessage());
        }
    }
}