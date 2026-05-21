<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Portal Siswa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Kolom Kiri: Kartu Pelajar Digital & Keamanan -->
                <div class="md:col-span-1">
                    
                    <!-- Kartu Pelajar Digital -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center border-t-4 border-blue-500">
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Kartu Pelajar Digital</h3>
                        <p class="text-sm text-gray-500 mb-6">Gunakan QR ini jika kartu fisik tertinggal.</p>
                        
                        <div class="bg-gray-50 p-4 rounded-xl inline-block shadow-inner mb-4">
                            @if($student->qr_code_path)
                                <!-- Menampilkan SVG QR Code -->
                                <img src="{{ asset('storage/' . $student->qr_code_path) }}" alt="QR Code NISN {{ $student->nisn }}" class="w-48 h-48 mx-auto">
                            @else
                                <div class="w-48 h-48 mx-auto flex items-center justify-center bg-gray-200 text-gray-500 rounded-lg">
                                    <span>QR Belum Tersedia</span>
                                </div>
                            @endif
                        </div>
                        
                        <div class="mt-4">
                            <h4 class="font-bold text-xl text-gray-900">{{ Auth::user()->name }}</h4>
                            <p class="text-md font-mono text-blue-600 tracking-wider">NISN: {{ $student->nisn }}</p>
                            <span class="inline-block mt-2 px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                Kelas: {{ $student->class_id }}
                            </span>
                        </div>
                    </div>

                    <!-- Ringkasan Kehadiran -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6 border-t-4 border-green-500">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Kehadiran (30 Hari Terakhir)</h3>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-500">Persentase Hadir</span>
                            <span class="text-sm font-bold text-green-600">{{ $attendancePercentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-green-500 h-2.5 rounded-full" style="width: {{ $attendancePercentage }}%"></div>
                        </div>
                    </div>

                    <!-- FITUR BARU: Modul Keamanan (Ganti Password) -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6 border-t-4 border-yellow-400">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Keamanan Akun</h3>
                        
                        @if(session('success'))
                            <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 rounded relative text-sm mb-4">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded relative text-sm mb-4">
                                {{ session('error') }}
                            </div>
                        @endif

                        <!-- Form Mengarah ke Rute yang kita buat di web.php -->
                        <form action="{{ route('student.password.update') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Sandi Saat Ini (Default: NISN)</label>
                                <input type="password" name="current_password" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 text-sm p-2">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Sandi Baru (Min. 8 Karakter)</label>
                                <input type="password" name="new_password" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 text-sm p-2">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Ulangi Sandi Baru</label>
                                <input type="password" name="new_password_confirmation" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 text-sm p-2">
                            </div>
                            <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-md text-sm transition-colors">
                                Perbarui Sandi
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Kolom Kanan: Riwayat Absensi -->
                <div class="md:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                            <h3 class="text-lg font-bold text-gray-800">Riwayat Kehadiran (30 Hari Terakhir)</h3>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mata Pelajaran</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($attendances as $attendance)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $attendance->schedule->subject->name ?? 'Mata Pelajaran Dihapus' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($attendance->timestamp)->translatedFormat('l, d M Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($attendance->timestamp)->format('H:i:s') }} WIB</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            @if($attendance->status == 'hadir')
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Hadir</span>
                                            @elseif($attendance->status == 'izin')
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Izin</span>
                                            @elseif($attendance->status == 'sakit')
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Sakit</span>
                                            @else
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Alpa</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-10 text-center text-gray-500 text-sm">
                                            Belum ada riwayat kehadiran yang tercatat.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>