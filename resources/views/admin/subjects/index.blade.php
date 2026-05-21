@extends('layouts.dashboard')

@section('title', 'Manajemen Mata Pelajaran')

@section('content')
<div x-data="{ 
        editModalOpen: false, 
        editForm: { id: '', name: '' }
    }">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- KOLOM KIRI: FORM INPUT & IMPORT -->
        <div class="space-y-6">
            
            <!-- Form Tambah Manual -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-book-medical text-blue-600"></i> Tambah Manual
                </h2>
                <form action="{{ route('subjects.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Mata Pelajaran</label>
                        <input type="text" name="name" required placeholder="Contoh: Matematika Dasar" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors">
                        Simpan Mata Pelajaran
                    </button>
                </form>
            </div>

            <!-- Form Import Excel -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-file-excel text-green-600"></i> Import dari Excel
                </h2>
                <form action="{{ route('admin.subjects.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 border border-gray-300 rounded-lg cursor-pointer">
                        <div class="mt-2 text-xs text-gray-500">
                            Header Excel (Baris 1): <code class="bg-gray-100 px-1 rounded font-bold">nama_mapel</code>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors">
                        Upload & Proses Data
                    </button>
                </form>
            </div>
        </div>

        <!-- KOLOM KANAN: TABEL DATA MATA PELAJARAN -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Daftar Mata Pelajaran</h3>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-bold">{{ $subjects->count() }} Total</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-4 w-16 text-center">No</th>
                                <th class="px-6 py-4">Nama Mata Pelajaran</th>
                                <th class="px-6 py-4 text-center w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($subjects as $index => $subject)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-center">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 font-bold text-gray-800">{{ $subject->name }}</td>
                                <td class="px-6 py-4 flex justify-center gap-2">
                                    <!-- Tombol Edit -->
                                    <button type="button" @click="editForm = { id: '{{ $subject->id }}', name: '{{ addslashes($subject->name) }}' }; editModalOpen = true" class="text-yellow-600 hover:text-yellow-800 bg-yellow-50 hover:bg-yellow-100 px-2 py-1.5 rounded transition" title="Edit Nama">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <button type="button" onclick="confirmSingleDelete('{{ $subject->id }}')" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2 py-1.5 rounded transition" title="Hapus Mapel">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fa-solid fa-book-open-reader text-4xl mb-3 text-gray-300"></i>
                                    <p>Belum ada data mata pelajaran terdaftar.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT MAPEL (Alpine.js) -->
    <div x-show="editModalOpen" x-transition.opacity style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div @click.away="editModalOpen = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">Edit Mata Pelajaran</h3>
                <button @click="editModalOpen = false" class="text-gray-400 hover:text-red-500"><i class="fa-solid fa-times text-xl"></i></button>
            </div>
            
            <form :action="'/admin/subjects/' + editForm.id" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Mata Pelajaran</label>
                    <input type="text" name="name" x-model="editForm.name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
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
    function confirmSingleDelete(id) {
        Swal.fire({
            title: 'Hapus Mata Pelajaran?',
            text: "PERHATIAN: Menghapus mata pelajaran ini juga akan menghapus SEMUA jadwal pelajaran yang menggunakan mata pelajaran ini!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/subjects/' + id;
                
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
</script>
@endpush