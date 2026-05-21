<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class QRCodeController extends Controller
{
    /**
     * Fungsi untuk menghasilkan QR Code berdasarkan NISN Siswa.
     * Fitur ini biasanya diakses oleh Admin.
     */
    public function generate(Student $student)
    {
        try {
            // Memastikan folder qrcodes ada di dalam storage public
            if (!Storage::disk('public')->exists('qrcodes')) {
                Storage::disk('public')->makeDirectory('qrcodes');
            }

            // UBAH KE SVG: Generate QR Code format SVG berukuran 300x300 dengan isi NISN
            $qr = QrCode::format('svg')
                        ->size(300)
                        ->margin(2) 
                        ->generate($student->nisn);

            // UBAH EKSTENSI: Menentukan nama dan lokasi file menjadi .svg
            $path = 'qrcodes/' . $student->nisn . '.svg';
            
            // Menyimpan file ke storage public
            Storage::disk('public')->put($path, $qr);

            // Update database siswa dengan path gambar QR
            $student->update(['qr_code_path' => $path]);

            return redirect()->back()->with('success', 'QR Code berhasil di-generate untuk NISN: ' . $student->nisn . ' (Format SVG)');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat QR Code: ' . $e->getMessage());
        }
    }
}