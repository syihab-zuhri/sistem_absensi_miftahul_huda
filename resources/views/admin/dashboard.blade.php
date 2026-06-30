@extends('layouts.dashboard')

@section('title', 'Halaman Utama Admin')

@section('content')
    <!-- Welcome Banner dari kode lama Anda -->
    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 rounded-2xl p-8 text-white shadow-lg flex justify-between items-center mb-6">
        <div>
            <h2 class="text-3xl font-bold mb-2">Selamat Datang, {{ Auth::user()->name }}</h2>
            <p class="text-blue-100 max-w-2xl">Sistem absensi berjalan normal. Anda memiliki akses penuh untuk mengatur data pengguna, siswa, dan jadwal pelajaran.</p>
        </div>
    </div>

    <!-- Widget Statistik Real-time -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Total Pegawai -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition">
            <div class="p-4 bg-indigo-50 text-indigo-600 rounded-xl">
                <i class="fa-solid fa-user-tie text-2xl"></i>
            </div>
            <div>
                <div class="text-gray-500 text-sm font-semibold uppercase tracking-wider mb-1">Total Guru</div>
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

    <!-- Statistik Absensi (Charts) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Chart: Absensi Hari Ini (Pie) -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 lg:col-span-1">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Absensi Hari Ini</h3>
            <div class="relative w-full aspect-square max-h-64 mx-auto">
                <canvas id="todayChart"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2 text-center text-sm">
                <div class="bg-green-50 p-2 rounded text-green-700">Hadir: <span class="font-bold">{{ $todayStats['hadir'] }}</span></div>
                <div class="bg-blue-50 p-2 rounded text-blue-700">Sakit: <span class="font-bold">{{ $todayStats['sakit'] }}</span></div>
                <div class="bg-yellow-50 p-2 rounded text-yellow-700">Izin: <span class="font-bold">{{ $todayStats['izin'] }}</span></div>
                <div class="bg-red-50 p-2 rounded text-red-700">Alfa: <span class="font-bold">{{ $todayStats['alfa'] }}</span></div>
            </div>
        </div>

        <!-- Chart: Tren 7 Hari Terakhir (Bar/Line) -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 lg:col-span-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Tren Kehadiran (7 Hari Terakhir)</h3>
            <div class="relative w-full h-64 mx-auto">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabel Recent Attendances -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800">Aktivitas Presensi Terbaru</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="p-4 text-sm font-semibold text-gray-600">Siswa</th>
                        <th class="p-4 text-sm font-semibold text-gray-600">Mata Pelajaran</th>
                        <th class="p-4 text-sm font-semibold text-gray-600">Waktu</th>
                        <th class="p-4 text-sm font-semibold text-gray-600">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($recentAttendances as $attendance)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4">
                                <div class="font-medium text-gray-800">{{ $attendance->student->user->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $attendance->student->nisn ?? '-' }}</div>
                            </td>
                            <td class="p-4">
                                <div class="text-sm text-gray-700">{{ $attendance->schedule->subject->name ?? '-' }}</div>
                            </td>
                            <td class="p-4 text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($attendance->timestamp)->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="p-4">
                                @if($attendance->status === 'hadir')
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Hadir</span>
                                @elseif($attendance->status === 'sakit')
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Sakit</span>
                                @elseif($attendance->status === 'izin')
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">Izin</span>
                                @else
                                    <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">Alfa</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-gray-500 text-sm">
                                Belum ada data presensi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<!-- Masukkan Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. Pie Chart untuk Hari Ini ---
        const todayCtx = document.getElementById('todayChart').getContext('2d');
        const todayData = @json(array_values($todayStats));

        // Cek apakah ada data hari ini
        const hasDataToday = todayData.some(val => val > 0);

        new Chart(todayCtx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Sakit', 'Izin', 'Alfa'],
                datasets: [{
                    data: hasDataToday ? todayData : [1], // Tampilkan placeholder abu-abu jika kosong
                    backgroundColor: hasDataToday
                        ? ['#22c55e', '#3b82f6', '#eab308', '#ef4444']
                        : ['#e5e7eb'], // Abu-abu
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false // Legend sudah dibuat manual dengan HTML
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (!hasDataToday) return ' Belum ada data hari ini';
                                return ' ' + context.label + ': ' + context.raw + ' siswa';
                            }
                        }
                    }
                }
            }
        });

        // --- 2. Bar Chart untuk 7 Hari Terakhir ---
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: @json($last7Days),
                datasets: [
                    {
                        label: 'Hadir',
                        data: @json($weeklyStats['hadir']),
                        backgroundColor: '#22c55e',
                        borderRadius: 4,
                    },
                    {
                        label: 'Sakit',
                        data: @json($weeklyStats['sakit']),
                        backgroundColor: '#3b82f6',
                        borderRadius: 4,
                    },
                    {
                        label: 'Izin',
                        data: @json($weeklyStats['izin']),
                        backgroundColor: '#eab308',
                        borderRadius: 4,
                    },
                    {
                        label: 'Alfa',
                        data: @json($weeklyStats['alfa']),
                        backgroundColor: '#ef4444',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            precision: 0 // Hanya angka bulat
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    });
</script>
@endpush
