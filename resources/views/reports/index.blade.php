@extends('layouts.dashboard')

@section('title', 'Laporan Kehadiran Lengkap')

@section('content')
<div class="max-w-7xl mx-auto w-full space-y-6">
    
    <!-- Panel Kontrol: Filter -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-filter text-blue-600"></i> Saring Data Laporan
        </h2>
        <form action="{{ route('reports.index') }}" method="GET" class="flex flex-col lg:flex-row gap-4 items-end">
            <div class="w-full lg:w-1/5">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-700 bg-gray-50 focus:bg-white transition-colors">
            </div>
            <div class="w-full lg:w-1/5">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-700 bg-gray-50 focus:bg-white transition-colors">
            </div>
            <div class="w-full lg:w-1/5">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Filter Kelas</label>
                <select name="class_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-700 bg-gray-50 focus:bg-white transition-colors">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($classes as $cls)
                        <option value="{{ $cls }}" {{ $classId == $cls ? 'selected' : '' }}>Kelas {{ $cls }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full lg:w-1/5">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Mata Pelajaran</label>
                <select name="subject_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-700 bg-gray-50 focus:bg-white transition-colors">
                    <option value="">-- Semua Mapel --</option>
                    @foreach($subjects as $sub)
                        <option value="{{ $sub->id }}" {{ $subjectId == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full lg:w-1/5">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-magnifying-glass"></i> Terapkan Filter
                </button>
            </div>
        </form>

        <!-- Tombol Ekspor -->
        <div class="flex gap-3 mt-6 pt-5 border-t border-gray-100 justify-end">
            <a href="{{ route('reports.custom.excel', ['start_date' => $startDate, 'end_date' => $endDate, 'class_id' => $classId, 'subject_id' => $subjectId]) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center gap-2 text-sm shadow-sm">
                <i class="fa-solid fa-file-excel"></i> Export CSV
            </a>
            <a href="{{ route('reports.custom.pdf', ['start_date' => $startDate, 'end_date' => $endDate, 'class_id' => $classId, 'subject_id' => $subjectId]) }}" target="_blank" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center gap-2 text-sm shadow-sm">
                <i class="fa-solid fa-file-pdf"></i> Cetak PDF
            </a>
        </div>
    </div>

    <!-- Tabel Data -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <h3 class="text-sm font-bold text-gray-800 leading-relaxed">
                <i class="fa-regular fa-calendar-check text-blue-500 mr-1"></i> Hasil: <span class="text-blue-600">{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}</span>
                @if($classId) <br class="md:hidden"><span class="hidden md:inline">|</span> Kelas: <span class="text-blue-600">{{ $classId }}</span> @endif
                @if($subjectId) <br class="md:hidden"><span class="hidden md:inline">|</span> Mapel: <span class="text-blue-600">{{ \App\Models\Subject::find($subjectId)->name ?? '' }}</span> @endif
            </h3>
            <span class="bg-blue-100 text-blue-800 text-xs px-3 py-1.5 rounded-full font-bold shadow-inner">Total: {{ $attendances->count() }} Data</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-500 uppercase bg-white border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-center w-12">No</th>
                        <th class="px-6 py-4">Waktu Absen</th>
                        <th class="px-6 py-4">Nama Siswa</th>
                        <th class="px-6 py-4 text-center">Kelas</th>
                        <th class="px-6 py-4">Mata Pelajaran</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($attendances as $index => $attendance)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-center">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 text-gray-800 font-semibold font-mono text-xs">
                            <div class="text-gray-500">{{ \Carbon\Carbon::parse($attendance->timestamp)->format('d/m/Y') }}</div>
                            <div class="text-gray-800">{{ \Carbon\Carbon::parse($attendance->timestamp)->format('H:i:s') }} WIB</div>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-800">
                            {{ $attendance->student->user->name ?? 'User Terhapus' }}
                            <div class="text-xs font-normal text-gray-400 mt-0.5 font-mono">NISN: {{ $attendance->student->nisn ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="bg-gray-100 text-gray-800 border border-gray-200 px-2 py-1 rounded font-bold text-xs whitespace-nowrap">{{ $attendance->schedule->class_id ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 font-medium">{{ $attendance->schedule->subject->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $statusStr = strtolower($attendance->status);
                                $badgeColor = match($statusStr) {
                                    'hadir' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'sakit' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'izin'  => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'alpa'  => 'bg-red-100 text-red-800 border-red-200',
                                    default => 'bg-gray-100 text-gray-800 border-gray-200',
                                };
                            @endphp
                            <span class="{{ $badgeColor }} border px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                                {{ $attendance->status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                            <i class="fa-solid fa-folder-open text-5xl mb-4 text-gray-300"></i>
                            <p class="text-lg font-semibold text-gray-600">Tidak ada data absen</p>
                            <p class="text-sm mt-1">Tidak ditemukan catatan kehadiran yang sesuai dengan filter Anda saat ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection