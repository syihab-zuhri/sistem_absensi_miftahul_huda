@extends('layouts.dashboard')

@section('title', 'Ruang Guru')

@section('content')
    <div class="bg-gradient-to-r from-emerald-600 to-teal-700 rounded-2xl p-8 text-white shadow-lg flex justify-between items-center mb-8 relative overflow-hidden">
        <div class="relative z-10">
            <h2 class="text-3xl font-bold mb-2">Selamat Datang, Bapak/Ibu {{ Auth::user()->name }}!</h2>
            <p class="text-emerald-100 max-w-2xl text-lg">Semoga hari Anda menyenangkan. Silakan gunakan menu di bawah ini untuk memulai pemindaian absensi kelas atau melihat laporan kehadiran siswa.</p>
        </div>
        <div class="hidden md:block text-8xl opacity-20 absolute right-8 top-4 transform rotate-12">
            <i class="fa-solid fa-chalkboard-user"></i>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <a href="/scanner" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center gap-4 hover:shadow-md hover:border-emerald-200 transition group">
            <div class="p-5 bg-emerald-50 text-emerald-600 rounded-full group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                <i class="fa-solid fa-qrcode text-4xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Mulai Scanner Kelas</h3>
                <p class="text-gray-500 text-sm">Buka kamera pemindai untuk mencatat kehadiran siswa secara otomatis menggunakan QR Code.</p>
            </div>
        </a>

        <a href="{{ route('reports.index') }}" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center gap-4 hover:shadow-md hover:border-blue-200 transition group">
            <div class="p-5 bg-blue-50 text-blue-600 rounded-full group-hover:bg-blue-600 group-hover:text-white transition-colors">
                <i class="fa-solid fa-file-invoice text-4xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Rekap Laporan</h3>
                <p class="text-gray-500 text-sm">Lihat data riwayat kehadiran siswa dan unduh dokumen laporan ke format PDF atau Excel.</p>
            </div>
        </a>

        <a href="{{ route('guru.students.index') }}" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center gap-4 hover:shadow-md hover:border-purple-200 transition group">
            <div class="p-5 bg-purple-50 text-purple-600 rounded-full group-hover:bg-purple-600 group-hover:text-white transition-colors">
                <i class="fa-solid fa-users text-4xl"></i>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Data Siswa</h3>
                <p class="text-gray-500 text-sm">Melihat daftar seluruh siswa terdaftar dan memverifikasi QR Code mereka.</p>
            </div>
        </a>

    </div>
@endsection