@extends('layouts.dashboard')

@section('title', 'Manajemen Data Siswa')

@section('content')
<!-- Bungkus utama menggunakan Alpine.js untuk state Modal Edit & Checkbox -->
<div x-data="{ 
        editModalOpen: false, 
        editForm: { id: '', name: '', nisn: '', class_id: '' },
        selectAll: false,
        toggleAll() {
            let checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => cb.checked = this.selectAll);
        }
    }">

    <!-- Grid Layout Utama -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- KOLOM KIRI: FORM TAMBAH & IMPORT -->
        <div class="space-y-6">
            <!-- Form Tambah Manual -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-blue-600"></i> Tambah Manual
                </h2>
                <form action="{{ route('students.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NISN</label>
                        <input type="text" name="nisn" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                        <input type="text" name="class_id" required placeholder="Cth: 10-A" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors">
                        Simpan & Buat QR
                    </button>
                </form>
            </div>

            <!-- Form Import Excel -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-file-excel text-green-600"></i> Import Excel
                </h2>
                <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 border border-gray-300 rounded-lg cursor-pointer">
                        <div class="mt-2 text-xs text-gray-500">
                            Header (Baris 1): <code class="bg-gray-100 px-1 rounded font-bold">nama</code>, <code class="bg-gray-100 px-1 rounded font-bold">nisn</code>, <code class="bg-gray-100 px-1 rounded font-bold">kelas</code>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors">
                        Upload & Proses
                    </button>
                </form>
            </div>
        </div>

        <!-- KOLOM KANAN: TABEL, FILTER, & PAGINASI -->
        <div class="lg:col-span-2 space-y-4">
            
            <!-- Toolbar Atas: Search, Hapus Massal, & Edit Kelas Massal -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col sm:flex-row justify-between gap-4 items-center">
                <!-- Form Pencarian (Filter) -->
                <form action="{{ route('students.index') }}" method="GET" class="w-full sm:w-1/2 relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari NISN, Nama, atau Kelas..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                    @if(request('search'))
                        <a href="{{ route('students.index') }}" class="absolute right-3 top-2.5 text-red-500 text-sm font-bold hover:underline">Clear</a>
                    @endif
                </form>

                <!-- Tombol Aksi Massal -->
                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="button" onclick="confirmBulkEditClass()" class="flex-1 sm:flex-none bg-indigo-100 hover:bg-indigo-200 text-indigo-700 font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 text-sm shadow-sm">
                        <i class="fa-solid fa-people-arrows"></i> Pindah Kelas
                    </button>
                    <button type="button" onclick="confirmBulkDelete()" class="flex-1 sm:flex-none bg-red-100 hover:bg-red-200 text-red-700 font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 text-sm shadow-sm">
                        <i class="fa-solid fa-trash-can"></i> Hapus Terpilih
                    </button>
                </div>
            </div>

            <!-- Tabel Data Siswa dibungkus dalam Form untuk Hapus Massal -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <form id="bulkDeleteForm" action="{{ route('students.destroyBulk') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                                <tr>
                                    <th class="px-4 py-3 text-center w-12">
                                        <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                    </th>
                                    <th class="px-6 py-3">Nama Siswa</th>
                                    <th class="px-6 py-3">NISN & Kelas</th>
                                    <th class="px-6 py-3 text-center">QR Code</th>
                                    <th class="px-6 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($students as $student)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <!-- Checkbox Hapus Massal -->
                                    <td class="px-4 py-4 text-center">
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-800">{{ $student->user->name ?? 'User Terhapus' }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-mono text-gray-600">{{ $student->nisn }}</div>
                                        <span class="bg-gray-200 text-gray-800 px-2 py-0.5 rounded text-xs font-bold">{{ $student->class_id }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($student->qr_code_path)
                                            <button type="button" onclick="showQRCode('{{ Storage::url($student->qr_code_path) }}', '{{ $student->nisn }}', '{{ $student->user->name ?? '' }}')" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded-full text-xs font-bold transition flex items-center gap-1 mx-auto">
                                                <i class="fa-solid fa-qrcode"></i> Lihat
                                            </button>
                                        @else
                                            <span class="text-red-500 text-xs font-bold">Kosong</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 flex justify-center gap-2">
                                        <!-- Tombol Edit (Memanggil Modal Alpine) -->
                                        <button type="button" @click="editForm = { id: '{{ $student->id }}', name: '{{ addslashes($student->user->name ?? '') }}', nisn: '{{ $student->nisn }}', class_id: '{{ $student->class_id }}' }; editModalOpen = true" class="text-yellow-600 hover:text-yellow-800 bg-yellow-50 hover:bg-yellow-100 px-2 py-1.5 rounded transition" title="Edit Siswa">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <!-- Tombol Hapus Satuan -->
                                        <button type="button" onclick="confirmSingleDelete('{{ $student->id }}')" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2 py-1.5 rounded transition" title="Hapus Siswa">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <i class="fa-solid fa-folder-open text-4xl mb-3 text-gray-300"></i>
                                        <p>Belum ada data atau tidak ditemukan.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            <!-- Paginasi -->
            <div class="mt-4">
                {{ $students->links() }}
            </div>
        </div>
    </div>

    <!-- MODAL EDIT SISWA (Alpine.js) -->
    <div x-show="editModalOpen" 
         x-transition.opacity 
         style="display: none;" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        
        <div @click.away="editModalOpen = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">Edit Data Siswa</h3>
                <button @click="editModalOpen = false" class="text-gray-400 hover:text-red-500"><i class="fa-solid fa-times text-xl"></i></button>
            </div>
            
            <form :action="'/admin/students/' + editForm.id" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" x-model="editForm.name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NISN <span class="text-xs text-red-500 font-normal">(QR Code akan otomatis berubah jika diganti)</span></label>
                    <input type="text" name="nisn" x-model="editForm.nisn" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                    <input type="text" name="class_id" x-model="editForm.class_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" @click="editModalOpen = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-4 rounded-lg transition-colors">Batal</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // FUNGSI 1: Tampilkan Popup QR Code beserta Tombol Unduh
    function showQRCode(url, nisn, name) {
        Swal.fire({
            title: name,
            text: 'NISN: ' + nisn,
            imageUrl: url,
            imageWidth: 250,
            imageHeight: 250,
            imageAlt: 'QR Code ' + nisn,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-download"></i> Unduh QR',
            cancelButtonText: 'Tutup',
            confirmButtonColor: '#10b981', // Emerald green
        }).then((result) => {
            if (result.isConfirmed) {
                // Proses Unduh Gambar
                const link = document.createElement('a');
                link.href = url;
                link.download = 'QR_' + nisn + '_' + name.replace(/\s+/g, '_') + '.svg';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        });
    }

    // FUNGSI 2: Konfirmasi Hapus Massal
    function confirmBulkDelete() {
        const checkboxes = document.querySelectorAll('.student-checkbox:checked');
        if (checkboxes.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih minimal satu siswa untuk dihapus.' });
            return;
        }

        Swal.fire({
            title: 'Hapus ' + checkboxes.length + ' Siswa?',
            text: "Data yang dihapus beserta akun login dan QR Code-nya tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus Semua!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('bulkDeleteForm').submit();
            }
        });
    }

    // FUNGSI 3: Konfirmasi Hapus Satuan (Menebeng Form yang sama)
    function confirmSingleDelete(id) {
        Swal.fire({
            title: 'Hapus Siswa Ini?',
            text: "Data, riwayat absen, dan akunnya akan terhapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Membuat form dadakan untuk menghapus 1 data agar metode DELETE terkirim dengan benar
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/students/' + id;
                
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                
                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                
                form.appendChild(csrf);
                form.appendChild(method);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    
    // FUNGSI 4: Konfirmasi Edit Kelas Massal
    function confirmBulkEditClass() {
        const checkboxes = document.querySelectorAll('.student-checkbox:checked');
        if (checkboxes.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih minimal satu siswa yang ingin dipindahkan kelasnya.' });
            return;
        }

        Swal.fire({
            title: 'Pindahkan ' + checkboxes.length + ' Siswa',
            text: 'Masukkan nama kelas baru untuk siswa yang dipilih:',
            input: 'text',
            inputPlaceholder: 'Contoh: 11-A',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5', // Indigo tailwind
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Simpan Perubahan',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value) {
                    return 'Nama kelas tidak boleh kosong!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Ambil form utama
                const form = document.getElementById('bulkDeleteForm');
                
                // Ubah action menjadi URL Update Kelas
                form.action = "{{ route('students.updateClassBulk') }}";
                
                // REVISI PENTING: Hapus elemen _method (DELETE) agar form kembali menjadi POST murni
                const methodInput = form.querySelector('input[name="_method"]');
                if (methodInput) {
                    methodInput.remove();
                }

                // Tambahkan input tersembunyi untuk menyimpan nama kelas baru
                const newClassInput = document.createElement('input');
                newClassInput.type = 'hidden';
                newClassInput.name = 'new_class_id';
                newClassInput.value = result.value;
                form.appendChild(newClassInput);

                // Kirim form ke server
                form.submit();
            }
        });
    }
</script>
@endpush