@extends('layouts.dashboard')

@section('title', 'Manajemen Kelas')

@section('content')
<div x-data="{ 
        editModalOpen: false, 
        editForm: { id: '', name: '' }
    }">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- KOLOM KIRI: FORM INPUT -->
        <div class="space-y-6">
            
            <!-- Form Tambah Manual -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-users text-blue-600"></i> Tambah Kelas
                </h2>
                <form action="{{ route('classrooms.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kelas</label>
                        <input type="text" name="name" required placeholder="Contoh: X IPA 1" oninput="this.value = this.value.toUpperCase()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors">
                        Simpan Kelas
                    </button>
                </form>
            </div>
        </div>

        <!-- KOLOM KANAN: TABEL DATA KELAS -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Daftar Kelas</h3>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-bold">{{ $classrooms->count() }} Total</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-4 w-16 text-center whitespace-nowrap">No</th>
                                <th class="px-6 py-4 whitespace-nowrap">Nama Kelas</th>
                                <th class="px-6 py-4 text-center w-32 whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($classrooms as $index => $classroom)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-center whitespace-nowrap">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 font-bold text-gray-800 whitespace-nowrap">{{ $classroom->name }}</td>
                                <td class="px-6 py-4 flex justify-center gap-2 whitespace-nowrap">
                                    <!-- Tombol Edit -->
                                    <button type="button" @click="editForm = { id: '{{ $classroom->id }}', name: '{{ addslashes($classroom->name) }}' }; editModalOpen = true" class="text-yellow-600 hover:text-yellow-800 bg-yellow-50 hover:bg-yellow-100 px-2 py-1.5 rounded transition" title="Edit Nama">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <button type="button" onclick="confirmSingleDelete('{{ $classroom->id }}')" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2 py-1.5 rounded transition" title="Hapus Kelas">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fa-solid fa-users-slash text-4xl mb-3 text-gray-300"></i>
                                    <p>Belum ada data kelas terdaftar.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT KELAS (Alpine.js) -->
    <div x-show="editModalOpen" x-transition.opacity style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div @click.away="editModalOpen = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">Edit Kelas</h3>
                <button @click="editModalOpen = false" class="text-gray-400 hover:text-red-500"><i class="fa-solid fa-times text-xl"></i></button>
            </div>
            
            <form :action="'/admin/classrooms/' + editForm.id" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kelas</label>
                    <input type="text" name="name" x-model="editForm.name" required oninput="this.value = this.value.toUpperCase()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
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
            title: 'Hapus Kelas?',
            text: "PERHATIAN: Data kelas ini akan dihapus permanen!",
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
                form.action = '/admin/classrooms/' + id;
                
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
