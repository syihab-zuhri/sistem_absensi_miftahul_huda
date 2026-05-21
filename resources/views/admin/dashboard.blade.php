@extends('layouts.dashboard')

@section('title', 'Ruang Kendali Admin')

@section('content')
    <!-- Welcome Banner dari kode lama Anda -->
    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 rounded-2xl p-8 text-white shadow-lg flex justify-between items-center mb-6">
        <div>
            <h2 class="text-3xl font-bold mb-2">Selamat Datang, Komandan!</h2>
            <p class="text-blue-100 max-w-2xl">Sistem absensi berjalan normal. Anda memiliki akses penuh untuk mengatur data pengguna, siswa, dan jadwal pelajaran.</p>
        </div>
    </div>

    <!-- Widget Statistik Real-time dari kode lama Anda -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Total Pegawai -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition">
            <div class="p-4 bg-indigo-50 text-indigo-600 rounded-xl">
                <i class="fa-solid fa-user-tie text-2xl"></i>
            </div>
            <div>
                <div class="text-gray-500 text-sm font-semibold uppercase tracking-wider mb-1">Total Pegawai</div>
                <div class="text-3xl font-bold text-gray-800">{{ \App\Models\User::role(['admin', 'guru'])->count() }}</div>
            </div>
        </div>

        <!-- Total Siswa -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition">
            <div class="p-4 bg-blue-50 text-blue-600 rounded-xl">
                <i class="fa-solid fa-users text-2xl"></i>
            </div>
            <div>
                <div class="text-gray-500 text-sm font-semibold uppercase tracking-wider mb-1">Total Siswa</div>
                <div class="text-3xl font-bold text-gray-800">{{ \App\Models\Student::count() }}</div>
            </div>
        </div>

        <!-- Total Sesi Jadwal -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition">
            <div class="p-4 bg-purple-50 text-purple-600 rounded-xl">
                <i class="fa-solid fa-calendar-check text-2xl"></i>
            </div>
            <div>
                <div class="text-gray-500 text-sm font-semibold uppercase tracking-wider mb-1">Sesi Jadwal</div>
                <div class="text-3xl font-bold text-gray-800">{{ \App\Models\Schedule::count() }}</div>
            </div>
        </div>
        
    </div>
@endsection