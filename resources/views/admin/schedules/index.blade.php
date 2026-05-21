@extends('layouts.dashboard')

@section('title', 'Manajemen Jadwal Pelajaran')

@section('content')
<div x-data="{ 
        editModalOpen: false, 
        editForm: { id: '', subject_id: '', teacher_id: '', class_id: '', day: '', start_time: '', end_time: '' },
        selectAll: false,
        toggleAll() {
            // Centang semua checkbox jadwal
            let checkboxes = document.querySelectorAll('.schedule-checkbox');
            checkboxes.forEach(cb => cb.checked = this.selectAll);
            // Centang juga semua checkbox hari
            let dayCheckboxes = document.querySelectorAll('.day-checkbox');
            dayCheckboxes.forEach(cb => cb.checked = this.selectAll);
        }
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hari</label>
                        <select name="day" required class="w-full rounded-lg border-gray-300 border p-2.5 bg-white focus:ring-2 focus:ring-blue-500">
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
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
                        <input type="text" name="class_id" required placeholder="Cth: 10-A" class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors">
                        Simpan Jadwal
                    </button>
                </form>
            </div>
        </div>

        <!-- KOLOM KANAN: TABEL DAFTAR JADWAL -->
        <div class="lg:col-span-2 space-y-4">
            
            <!-- Toolbar Eksekusi Massal -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col sm:flex-row justify-between gap-4 items-center">
                <div class="text-sm text-gray-500 font-medium">
                    <i class="fa-solid fa-info-circle text-blue-500"></i> Jadwal dikelompokkan berdasarkan hari.
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <!-- Tombol Hapus Massal -->
                    <button type="button" onclick="confirmBulkDelete()" class="flex-1 sm:flex-none bg-red-100 hover:bg-red-200 text-red-700 font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-trash-can"></i> Hapus Terpilih
                    </button>
                    <!-- Tombol Kosongkan Jadwal (Semester Baru) -->
                    <form id="truncateForm" action="{{ route('schedules.truncate') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="button" onclick="confirmTruncate()" class="flex-1 sm:flex-none bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm">
                            <i class="fa-solid fa-skull-crossbones"></i> Kosongkan Total
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tabel Data -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <form id="bulkDeleteForm" action="{{ route('schedules.destroyBulk') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                                <tr>
                                    <th class="px-4 py-3 text-center w-12">
                                        <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded cursor-pointer focus:ring-blue-500">
                                    </th>
                                    <th class="px-6 py-3">Jam Aktif</th>
                                    <th class="px-6 py-3">Mata Pelajaran & Guru</th>
                                    <th class="px-6 py-3 text-center">Kelas</th>
                                    <th class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($schedules as $day => $daySchedules)
                                    <!-- Baris Pemisah Hari (Grouping) DENGAN CHECKBOX -->
                                    <tr class="bg-blue-50 border-y border-blue-100">
                                        <td class="px-4 py-3 text-center">
                                            <!-- Checkbox per Hari -->
                                            <input type="checkbox" class="day-checkbox w-4 h-4 text-blue-600 bg-white border-gray-400 rounded cursor-pointer focus:ring-blue-500" onclick="toggleDay('{{ $day }}', this)" title="Pilih Semua Jadwal Hari {{ $day }}">
                                        </td>
                                        <td colspan="4" class="px-6 py-3 font-bold text-blue-800 uppercase tracking-wider text-xs">
                                            <i class="fa-regular fa-calendar-check mr-2"></i> Hari {{ $day }}
                                        </td>
                                    </tr>
                                    
                                    @foreach($daySchedules as $schedule)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-4 text-center">
                                            <!-- Perhatikan penambahan class 'day-{{ $day }}' di bawah ini -->
                                            <input type="checkbox" name="schedule_ids[]" value="{{ $schedule->id }}" class="schedule-checkbox day-{{ $day }} w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded cursor-pointer focus:ring-blue-500">
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} WIB</div>
                                            <div class="text-xs text-gray-500">s/d {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }} WIB</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-800">{{ $schedule->subject->name ?? 'Dihapus' }}</div>
                                            <div class="text-xs text-blue-600">{{ $schedule->teacher->name ?? 'Dihapus' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="bg-gray-200 text-gray-800 px-2 py-1 rounded text-xs font-bold">{{ $schedule->class_id }}</span>
                                        </td>
                                        <td class="px-6 py-4 flex justify-center gap-2">
                                            <!-- Tombol Edit -->
                                            <button type="button" @click="editForm = { id: '{{ $schedule->id }}', subject_id: '{{ $schedule->subject_id }}', teacher_id: '{{ $schedule->teacher_id }}', class_id: '{{ $schedule->class_id }}', day: '{{ $schedule->day }}', start_time: '{{ $schedule->start_time }}', end_time: '{{ $schedule->end_time }}' }; editModalOpen = true" class="text-yellow-600 hover:text-yellow-800 bg-yellow-50 hover:bg-yellow-100 px-2 py-1.5 rounded transition" title="Edit Jadwal">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- Tombol Hapus Satuan -->
                                            <button type="button" onclick="confirmSingleDelete('{{ $schedule->id }}')" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2 py-1.5 rounded transition" title="Hapus Jadwal">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                            <i class="fa-regular fa-calendar-xmark text-4xl mb-3 text-gray-300"></i>
                                            <p>Belum ada jadwal yang terdaftar.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT JADWAL (Alpine.js) -->
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
                    <input type="text" name="class_id" x-model="editForm.class_id" required class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-2 focus:ring-blue-500">
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
    // FUNGSI BARU: Centang semua jadwal dalam satu hari
    function toggleDay(day, element) {
        // Cari semua checkbox jadwal yang memiliki class day-{nama hari}
        let checkboxes = document.querySelectorAll('.schedule-checkbox.day-' + day);
        checkboxes.forEach(cb => {
            cb.checked = element.checked;
        });
    }

    // Konfirmasi Hapus Massal
    function confirmBulkDelete() {
        const checkboxes = document.querySelectorAll('.schedule-checkbox:checked');
        if (checkboxes.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih minimal satu jadwal untuk dihapus.' }); 
            return;
        }
        Swal.fire({
            title: 'Hapus ' + checkboxes.length + ' Sesi Jadwal?', 
            text: "Jadwal yang dihapus tidak dapat dikembalikan!", 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#d33', 
            confirmButtonText: 'Ya, Hapus Terpilih!'
        }).then((result) => {
            if (result.isConfirmed) document.getElementById('bulkDeleteForm').submit();
        });
    }

    // Konfirmasi Hapus Satuan
    function confirmSingleDelete(id) {
        Swal.fire({
            title: 'Hapus Sesi Jadwal?', 
            text: "Sesi ini akan dihapus dari daftar jadwal.", 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#d33', 
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form'); form.method = 'POST'; form.action = '/admin/schedules/' + id;
                const csrf = document.createElement('input'); csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
                const method = document.createElement('input'); method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE';
                form.appendChild(csrf); form.appendChild(method); document.body.appendChild(form); form.submit();
            }
        });
    }

    // Konfirmasi Kosongkan Tabel (Semester Baru)
    function confirmTruncate() {
        Swal.fire({
            title: 'KOSONGKAN SELURUH JADWAL?', 
            text: "PERINGATAN! Semua jadwal akan terhapus. Fitur ini dirancang untuk pembersihan saat berganti semester. Lanjutkan?", 
            icon: 'error', 
            showCancelButton: true, 
            confirmButtonColor: '#d33', 
            confirmButtonText: 'Ya, Bersihkan Total!'
        }).then((result) => {
            if (result.isConfirmed) document.getElementById('truncateForm').submit();
        });
    }
</script>
@endpush