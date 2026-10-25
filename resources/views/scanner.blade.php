@extends('layouts.dashboard')

@section('title', 'Scanner Absensi Kelas')

@section('content')
<div @if(isset($activeSchedule)) id="attendance-scanner" data-session-url="{{ isset($activeSession) ? route('attendance-sessions.roster', $activeSession) : url('/absensi/session-data/'.$activeSchedule->id) }}" data-save-url="{{ isset($activeSession) ? route('attendance-sessions.save', $activeSession) : url('/absensi/save-session/'.$activeSchedule->id) }}" @endif class="max-w-7xl mx-auto space-y-6">
    <a class="workflow-secondary" href="{{ isset($activeSession) ? route('attendance-sessions.show', $activeSession) : route('attendance-sessions.index') }}">Kelola sesi pertemuan</a>

    <!-- Status Sesi Aktif -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center text-white">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <i class="fa-solid fa-satellite-dish animate-pulse"></i> Status Sesi Berjalan
            </h2>
            <div class="text-sm font-medium bg-blue-700 px-3 py-1 rounded-full shadow-inner">
                {{ ($activeSession->lesson_date ?? now())->locale('id')->translatedFormat('l, d F Y') }}
            </div>
        </div>
        <div class="p-6 text-center">
            @if(isset($activeSchedule))
                <h3 class="text-2xl font-black text-gray-800 mb-1">{{ $activeSchedule->subject->name ?? 'Mapel Tidak Diketahui' }}</h3>
                <p class="text-gray-700 font-medium mb-3">Kelas: <span class="text-blue-700 font-bold">{{ $activeSession->class_id ?? $activeSchedule->class_id }}</span> | Waktu: {{ substr($activeSession->start_time ?? $activeSchedule->start_time, 0, 5) }} - {{ substr($activeSession->end_time ?? $activeSchedule->end_time, 0, 5) }}</p>
            @else
                <div class="py-8">
                    <i class="fa-solid fa-user-lock text-5xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-700">Akses Scanner Ditutup</h3>
                    <p class="text-gray-500 mt-2 max-w-md mx-auto">Anda tidak dapat menggunakan Scanner karena Anda tidak memiliki jadwal mengajar di kelas manapun pada hari dan jam ini.</p>
                </div>
            @endif
        </div>
    </div>

    @if(isset($activeSchedule))
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- KOLOM KIRI: SCANNER -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden sticky top-6">
                <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider text-center">Kamera Pemindai</h3>
                </div>
                
                <div class="p-0 bg-black relative min-h-48">
                    <div class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none">
                        <div class="w-48 h-48 border-2 border-white border-dashed opacity-50 relative">
                            <div class="absolute top-0 left-0 w-4 h-4 border-t-4 border-l-4 border-blue-500"></div>
                            <div class="absolute top-0 right-0 w-4 h-4 border-t-4 border-r-4 border-blue-500"></div>
                            <div class="absolute bottom-0 left-0 w-4 h-4 border-b-4 border-l-4 border-blue-500"></div>
                            <div class="absolute bottom-0 right-0 w-4 h-4 border-b-4 border-r-4 border-blue-500"></div>
                        </div>
                    </div>
                    <div id="reader" class="w-full"></div>
                </div>
                
                <!-- Tombol Kontrol Kamera Pindah Kesini -->
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-center">
                    <div id="kamera-kontrol" class="flex flex-wrap justify-center gap-2">
                        <span class="text-sm text-gray-700 w-full text-center" id="kamera-info" role="status">Kamera belum diaktifkan.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: DAFTAR SELURUH SISWA (Kelas Aktif) -->
        <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-gray-200 flex flex-col h-[700px]">
            
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex flex-col xl:flex-row justify-between items-start gap-4">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">
                    Daftar Siswa - Kelas {{ $activeSchedule->class_id }}
                </h3>
                <div class="flex flex-wrap gap-2 w-full xl:w-auto">
                    <!-- Tombol Simpan Sesi (Baru) -->
                    <button type="button" disabled id="btnSaveSession" class="flex-1 sm:flex-none min-h-11 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-all flex items-center justify-center gap-2 text-sm shadow-sm disabled:bg-gray-600 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-save"></i> Simpan Sesi
                    </button>
                    <!-- Tombol Cetak PDF -->
                    <a href="{{ route('reports.session', ['schedule_id' => $activeSchedule->id, 'date' => ($activeSession->lesson_date ?? today())->toDateString()]) }}" id="btnPrintPdf" target="_blank" rel="noopener" class="flex-1 sm:flex-none min-h-11 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 text-sm">
                        <i class="fa-solid fa-print"></i> Cetak/Preview Laporan Sesi Ini
                    </a>
                </div>
            </div>

            <div class="border-b border-gray-200 px-6 py-4">
                <label for="correction-reason" class="workflow-label">Alasan perubahan presensi yang sudah tersimpan</label>
                <textarea id="correction-reason" maxlength="1000" rows="2" class="workflow-input" aria-describedby="correction-help"></textarea>
                <p id="correction-help" class="mt-2 text-sm text-gray-700">Wajib minimal 10 karakter bila mengubah status yang sudah tersimpan. Pencatatan pertama tidak memerlukan alasan.</p>
            </div>
            <!-- Tabel Daftar Siswa yang dimuat secara dinamis -->
            <div class="flex-1 overflow-auto p-0">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-600 uppercase bg-white sticky top-0 shadow-sm z-10">
                        <tr>
                            <th class="px-6 py-3 w-12 text-center whitespace-nowrap">No</th>
                            <th class="px-6 py-3 whitespace-nowrap">Nama Lengkap & NISN</th>
                            <th class="px-6 py-3 text-center w-40 whitespace-nowrap">Status Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody" class="divide-y divide-gray-100">
                        <tr><td colspan="3" class="p-8 text-center whitespace-nowrap"><i class="fa-solid fa-spinner fa-spin text-2xl text-blue-500"></i> Memuat data kelas...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    @endif
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

@endsection
