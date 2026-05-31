@extends('layouts.dashboard')

@section('title', 'Portal Siswa')

@section('content')
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-8 text-white shadow-lg flex justify-between items-center mb-8">
        <div>
            <h2 class="text-3xl font-bold mb-2">Halo, {{ Auth::user()->name }}! 👋</h2>
            <p class="text-blue-100 max-w-2xl">Selamat datang di portal siswa. Di sini kamu bisa melihat riwayat kehadiranmu dan menggunakan kartu pelajar digital untuk absensi.</p>
        </div>
        <div class="hidden md:block">
            <i class="fa-solid fa-user-graduate text-6xl text-white/20"></i>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Kolom Kiri: Kartu Pelajar & Keamanan -->
        <div class="space-y-8">
            
            <!-- Kartu Pelajar Digital -->
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="p-6 border-b border-gray-50 bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-id-card text-blue-500"></i>
                        Kartu Pelajar Digital
                    </h3>
                </div>
                <div class="p-8 text-center">
                    <div class="bg-white p-4 rounded-2xl inline-block shadow-sm border border-gray-100 mb-6">
                        @if($student->qr_code_path)
                            <img src="{{ asset('storage/' . $student->qr_code_path) }}" alt="QR Code NISN {{ $student->nisn }}" class="w-48 h-48 mx-auto">
                        @else
                            <div class="w-48 h-48 mx-auto flex items-center justify-center bg-gray-50 text-gray-400 rounded-xl border-2 border-dashed border-gray-200">
                                <div class="text-center">
                                    <i class="fa-solid fa-qrcode text-4xl mb-2"></i>
                                    <p class="text-xs">QR Belum Tersedia</p>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <div class="space-y-1">
                        <h4 class="font-bold text-xl text-gray-900">{{ Auth::user()->name }}</h4>
                        <p class="text-sm font-mono text-blue-600 font-bold tracking-wider">NISN: {{ $student->nisn }}</p>
                        <div class="pt-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-full border border-blue-100 uppercase">
                                <i class="fa-solid fa-graduation-cap"></i>
                                Kelas: {{ $student->class_id }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 text-center">
                    <p class="text-xs text-gray-500 italic">Gunakan kode QR di atas untuk melakukan absensi harian melalui alat scan di sekolah.</p>
                </div>
            </div>

            <!-- Keamanan Akun -->
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="p-6 border-b border-gray-50 bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-yellow-500"></i>
                        Keamanan Akun
                    </h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('student.password.update') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Sandi Saat Ini</label>
                            <input type="password" name="current_password" required placeholder="Sandi saat ini (Default: NISN)" 
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Sandi Baru</label>
                            <input type="password" name="new_password" required placeholder="Minimal 8 karakter"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3 transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Konfirmasi Sandi Baru</label>
                            <input type="password" name="new_password_confirmation" required placeholder="Ulangi sandi baru"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3 transition-all">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl text-sm transition-all shadow-sm hover:shadow-md">
                            Perbarui Kata Sandi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Riwayat Absensi -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Ringkasan Statistik -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Persentase Kehadiran -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition">
                    <div class="p-4 bg-green-50 text-green-600 rounded-xl">
                        <i class="fa-solid fa-chart-line text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Persentase Hadir</div>
                        <div class="flex items-center gap-3">
                            <div class="text-3xl font-bold text-gray-800">{{ $attendancePercentage }}%</div>
                            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="bg-green-500 h-full rounded-full" style="width: {{ $attendancePercentage }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Kehadiran (30 Hari) -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition">
                    <div class="p-4 bg-blue-50 text-blue-600 rounded-xl">
                        <i class="fa-solid fa-clipboard-check text-2xl"></i>
                    </div>
                    <div>
                        <div class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Catatan Kehadiran</div>
                        <div class="text-3xl font-bold text-gray-800">{{ $attendances->count() }} <span class="text-sm font-medium text-gray-400">Sesi (30 hari terakhir)</span></div>
                    </div>
                </div>
            </div>

            <!-- Tabel Riwayat -->
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="p-6 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-history text-indigo-500"></i>
                        Riwayat Kehadiran (30 Hari Terakhir)
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">Mata Pelajaran</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">Tanggal & Waktu</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-400 uppercase tracking-wider whitespace-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            @forelse($attendances as $attendance)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                            {{ substr($attendance->schedule->subject->name ?? '?', 0, 1) }}
                                        </div>
                                        <div class="text-sm font-bold text-gray-900">{{ $attendance->schedule->subject->name ?? 'Mata Pelajaran Dihapus' }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium">{{ \Carbon\Carbon::parse($attendance->timestamp)->translatedFormat('l, d M Y') }}</div>
                                    <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($attendance->timestamp)->format('H:i:s') }} WIB</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @php
                                        $statusClasses = [
                                            'hadir' => 'bg-green-100 text-green-700 border-green-200',
                                            'izin' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                            'sakit' => 'bg-blue-100 text-blue-700 border-blue-200',
                                            'alpa' => 'bg-red-100 text-red-700 border-red-200',
                                        ];
                                        $statusClass = $statusClasses[$attendance->status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                                    @endphp
                                    <span class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-full border {{ $statusClass }} uppercase tracking-wider">
                                        {{ ucfirst($attendance->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i class="fa-solid fa-calendar-xmark text-4xl mb-3"></i>
                                        <p class="text-sm">Belum ada riwayat kehadiran yang tercatat.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
