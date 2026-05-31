@extends('layouts.dashboard')

@section('title', 'Manajemen Jadwal Pelajaran')

@section('content')
<div x-data="{ 
        activeSemester: '{{ $activeSemesterGlobal }}',
        editModalOpen: false, 
        editForm: { id: '', subject_id: '', teacher_id: '', class_id: '', day: '', start_time: '', end_time: '', semester: '' }
    }">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- KOLOM KIRI: FORM TAMBAH JADWAL -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-plus text-blue-600"></i> Tambah Jadwal Baru
                </h2>
                <form action="{{ route('schedules.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="semester" x-model="activeSemester">
                    
                    <div class="bg-blue-50 text-blue-700 text-xs px-3 py-2 rounded border border-blue-100 font-medium">
                        Jadwal akan ditambahkan ke: <span class="font-bold uppercase" x-text="'Semester ' + activeSemester"></span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hari</label>
                        <select name="day" required class="w-full rounded-lg border-gray-300 border p-2.5 bg-white focus:ring-2 focus:ring-blue-500">
                            <option value="Senin">Senin</option><option value="Selasa">Selasa</option><option value="Rabu">Rabu</option><option value="Kamis">Kamis</option><option value="Jumat">Jumat</option><option value="Sabtu">Sabtu</option><option value="Minggu">Minggu</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jam Mulai</label>
                            <input type="time" name="start_time" required class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jam Selesai</label>
                            <input type="time" name="end_time" required class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran</label>
                        <select name="subject_id" required class="w-full rounded-lg border-gray-300 border p-2.5 bg-white focus:ring-2 focus:ring-blue-500">
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Guru Pengajar</label>
                        <select name="teacher_id" required class="w-full rounded-lg border-gray-300 border p-2.5 bg-white focus:ring-2 focus:ring-blue-500">
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas Target</label>
                        <input type="text" name="class_id" required placeholder="Cth: 10-A" oninput="this.value = this.value.toUpperCase()" class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors">
                        Simpan Jadwal
                    </button>
                </form>
            </div>
        </div>

        <!-- KOLOM KANAN: TABEL DAFTAR JADWAL -->
        <div class="lg:col-span-2 space-y-4">
            
            <!-- Selector Tab Semester (dari kode 1: dengan badge ON) -->
            <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-200 flex gap-2">
                <button @click="activeSemester = 'Ganjil'" 
                        :class="activeSemester === 'Ganjil' ? 'bg-blue-600 text-white shadow-md' : 'bg-transparent text-gray-600 hover:bg-gray-100'"
                        class="flex-1 py-3 px-4 rounded-xl font-bold transition-all text-sm flex items-center justify-center gap-2">
                        </i> Semester Ganjil
                    @if($activeSemesterGlobal === 'Ganjil')
                        <span class="bg-green-400 text-green-900 text-[10px] px-2 py-0.5 rounded-full ml-1 shadow-sm">ON</span>
                    @endif
                </button>
                <button @click="activeSemester = 'Genap'" 
                        :class="activeSemester === 'Genap' ? 'bg-indigo-600 text-white shadow-md' : 'bg-transparent text-gray-600 hover:bg-gray-100'"
                        class="flex-1 py-3 px-4 rounded-xl font-bold transition-all text-sm flex items-center justify-center gap-2">
                    </i> Semester Genap
                    @if($activeSemesterGlobal === 'Genap')
                        <span class="bg-green-400 text-green-900 text-[10px] px-2 py-0.5 rounded-full ml-1 shadow-sm">ON</span>
                    @endif
                </button>
            </div>

            <!-- Toolbar Info (dari kode 1: dengan status aktif, tombol toggle, dan hapus semester) -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col xl:flex-row justify-between gap-4 items-center">
                
                <div class="text-sm font-medium flex items-center gap-2" :class="activeSemester === '{{ $activeSemesterGlobal }}' ? 'text-green-700' : 'text-gray-500'">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Status <span class="font-bold" x-text="'Semester ' + activeSemester"></span>:</span>
                    
                    <template x-if="activeSemester === '{{ $activeSemesterGlobal }}'">
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold border border-green-200">
                            <i class="fa-solid fa-check"></i> AKTIF & DIGUNAKAN
                        </span>
                    </template>
                    <template x-if="activeSemester !== '{{ $activeSemesterGlobal }}'">
                        <span class="bg-gray-100 text-gray-500 px-2 py-1 rounded text-xs font-bold border border-gray-200">
                            <i class="fa-solid fa-ban"></i> NONAKTIF
                        </span>
                    </template>
                </div>

                <div class="flex flex-wrap gap-2 w-full xl:w-auto">
                    <form action="{{ route('schedules.toggle') }}" method="POST" x-show="activeSemester !== '{{ $activeSemesterGlobal }}'" class="w-full xl:w-auto">
                        @csrf
                        <input type="hidden" name="semester" x-model="activeSemester">
                        <button type="submit" class="w-full xl:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg text-sm transition shadow-sm flex items-center justify-center gap-2">
                            <i class="fa-solid fa-power-off"></i> Aktifkan Semester
                        </button>
                    </form>

                    <button type="button" x-show="activeSemester === 'Ganjil'" onclick="confirmTruncateSemester('Ganjil')" class="w-full xl:w-auto bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-bold py-2 px-4 rounded-lg text-sm transition shadow-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-trash-can"></i> Hapus Semua Mata Pelajaran
                    </button>
                    <button type="button" x-show="activeSemester === 'Genap'" onclick="confirmTruncateSemester('Genap')" class="w-full xl:w-auto bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-bold py-2 px-4 rounded-lg text-sm transition shadow-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-trash-can"></i> Hapus Semua Mata Pelajaran
                    </button>
                </div>
            </div>

            <!-- Tabel Data (tampilan dari kode 2) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 whitespace-nowrap">Jam Aktif</th>
                                <th class="px-6 py-3 whitespace-nowrap">Mata Pelajaran & Guru</th>
                                <th class="px-6 py-3 text-center whitespace-nowrap">Kelas</th>
                                <th class="px-6 py-3 text-center whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        
                        <!-- BODY SEMESTER GANJIL -->
                        <tbody x-show="activeSemester === 'Ganjil'" class="divide-y divide-gray-100">
                            @forelse($schedules['Ganjil'] ?? [] as $day => $daySchedules)
                                <tr class="bg-blue-50 border-y border-blue-100">
                                    <td colspan="4" class="px-6 py-3 font-bold text-blue-800 uppercase tracking-wider text-xs whitespace-nowrap">
                                        <i class="fa-regular fa-calendar-check mr-2"></i> Hari {{ $day }}
                                    </td>
                                </tr>
                                @foreach($daySchedules as $schedule)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} WIB</div>
                                        <div class="text-xs text-gray-500">s/d {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }} WIB</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-800">{{ $schedule->subject->name ?? 'Dihapus' }}</div>
                                        <div class="text-xs text-blue-600">{{ $schedule->teacher->name ?? 'Dihapus' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="bg-gray-200 text-gray-800 px-2 py-1 rounded text-xs font-bold whitespace-nowrap">{{ $schedule->class_id }}</span>
                                    </td>
                                    <td class="px-6 py-4 flex justify-center gap-2 whitespace-nowrap">
                                        <button type="button" 
                                            @click="editForm = { id: '{{ $schedule->id }}', subject_id: '{{ $schedule->subject_id }}', teacher_id: '{{ $schedule->teacher_id }}', class_id: '{{ $schedule->class_id }}', day: '{{ $schedule->day }}', start_time: '{{ $schedule->start_time }}', end_time: '{{ $schedule->end_time }}', semester: '{{ $schedule->semester }}' }; editModalOpen = true" 
                                            class="text-yellow-600 hover:text-yellow-800 bg-yellow-50 hover:bg-yellow-100 px-2 py-1.5 rounded transition whitespace-nowrap" title="Edit Jadwal">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" onclick="confirmSingleDelete('{{ $schedule->id }}')" 
                                            class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2 py-1.5 rounded transition whitespace-nowrap" title="Hapus Jadwal">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                        <i class="fa-regular fa-calendar-xmark text-4xl mb-3 block text-gray-300"></i>
                                        <p>Belum ada jadwal yang terdaftar di Semester Ganjil.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        <!-- BODY SEMESTER GENAP -->
                        <tbody x-show="activeSemester === 'Genap'" class="divide-y divide-gray-100" style="display: none;">
                            @forelse($schedules['Genap'] ?? [] as $day => $daySchedules)
                                <tr class="bg-indigo-50 border-y border-indigo-100">
                                    <td colspan="4" class="px-6 py-3 font-bold text-indigo-800 uppercase tracking-wider text-xs whitespace-nowrap">
                                        <i class="fa-regular fa-calendar-check mr-2"></i> Hari {{ $day }}
                                    </td>
                                </tr>
                                @foreach($daySchedules as $schedule)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} WIB</div>
                                        <div class="text-xs text-gray-500">s/d {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }} WIB</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-800">{{ $schedule->subject->name ?? 'Dihapus' }}</div>
                                        <div class="text-xs text-indigo-600">{{ $schedule->teacher->name ?? 'Dihapus' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="bg-gray-200 text-gray-800 px-2 py-1 rounded text-xs font-bold whitespace-nowrap">{{ $schedule->class_id }}</span>
                                    </td>
                                    <td class="px-6 py-4 flex justify-center gap-2 whitespace-nowrap">
                                        <button type="button" 
                                            @click="editForm = { id: '{{ $schedule->id }}', subject_id: '{{ $schedule->subject_id }}', teacher_id: '{{ $schedule->teacher_id }}', class_id: '{{ $schedule->class_id }}', day: '{{ $schedule->day }}', start_time: '{{ $schedule->start_time }}', end_time: '{{ $schedule->end_time }}', semester: '{{ $schedule->semester }}' }; editModalOpen = true" 
                                            class="text-yellow-600 hover:text-yellow-800 bg-yellow-50 hover:bg-yellow-100 px-2 py-1.5 rounded transition whitespace-nowrap" title="Edit Jadwal">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" onclick="confirmSingleDelete('{{ $schedule->id }}')" 
                                            class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2 py-1.5 rounded transition whitespace-nowrap" title="Hapus Jadwal">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                        <i class="fa-regular fa-calendar-xmark text-4xl mb-3 block text-gray-300"></i>
                                        <p>Belum ada jadwal yang terdaftar di Semester Genap.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT JADWAL (dari kode 2) -->
    <div x-show="editModalOpen" x-transition.opacity style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div @click.away="editModalOpen = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">Edit Jadwal Pelajaran</h3>
                <button @click="editModalOpen = false" class="text-gray-400 hover:text-red-500"><i class="fa-solid fa-times text-xl"></i></button>
            </div>
            
            <form :action="'/admin/schedules/' + editForm.id" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                    <select name="semester" x-model="editForm.semester" required class="w-full rounded-lg border-gray-300 border p-2.5 bg-white focus:ring-2 focus:ring-blue-500">
                        <option value="Ganjil">Semester Ganjil</option>
                        <option value="Genap">Semester Genap</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hari</label>
                    <select name="day" x-model="editForm.day" required class="w-full rounded-lg border-gray-300 border p-2.5 bg-white focus:ring-2 focus:ring-blue-500">
                        <option value="Senin">Senin</option><option value="Selasa">Selasa</option><option value="Rabu">Rabu</option><option value="Kamis">Kamis</option><option value="Jumat">Jumat</option><option value="Sabtu">Sabtu</option><option value="Minggu">Minggu</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jam Mulai</label>
                        <input type="time" name="start_time" x-model="editForm.start_time" required class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jam Selesai</label>
                        <input type="time" name="end_time" x-model="editForm.end_time" required class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran</label>
                    <select name="subject_id" x-model="editForm.subject_id" required class="w-full rounded-lg border-gray-300 border p-2.5 bg-white focus:ring-2 focus:ring-blue-500">
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Guru</label>
                    <select name="teacher_id" x-model="editForm.teacher_id" required class="w-full rounded-lg border-gray-300 border p-2.5 bg-white focus:ring-2 focus:ring-blue-500">
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                    <input type="text" name="class_id" x-model="editForm.class_id" required oninput="this.value = this.value.toUpperCase()" class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="editModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-4 rounded-lg transition-colors">Batal</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Konfirmasi Hapus Satuan
    function confirmSingleDelete(id) {
        Swal.fire({
            title: 'Hapus Sesi Jadwal?', 
            text: "Sesi ini akan dihapus dari daftar jadwal.", 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#d33', 
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form'); 
                form.method = 'POST'; 
                form.action = '/admin/schedules/' + id;
                const csrf = document.createElement('input'); csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
                const method = document.createElement('input'); method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE';
                form.appendChild(csrf); form.appendChild(method); document.body.appendChild(form); form.submit();
            }
        });
    }

    // Konfirmasi Hapus Semua per Semester (dari kode 1)
    function confirmTruncateSemester(semester) {
        Swal.fire({ 
            title: 'Kosongkan Semester ' + semester + '?', 
            text: 'Seluruh jadwal di semester ini akan dihapus permanen!',
            icon: 'error', 
            showCancelButton: true, 
            confirmButtonColor: '#d33', 
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Hapus Semua' 
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form'); 
                form.method = 'POST'; 
                form.action = '{{ route("schedules.truncate") }}';
                const csrf = document.createElement('input'); csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
                const method = document.createElement('input'); method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE';
                const semInput = document.createElement('input'); semInput.type = 'hidden'; semInput.name = 'semester'; semInput.value = semester;
                form.appendChild(csrf); form.appendChild(method); form.appendChild(semInput); 
                document.body.appendChild(form); form.submit();
            }
        });
    }
</script>
@endpush