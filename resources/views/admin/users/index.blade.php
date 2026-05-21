@extends('layouts.dashboard')

@section('title', 'Manajemen Pegawai')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- KOLOM KIRI: FORM TAMBAH USER -->
    <div class="lg:col-span-1">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-blue-600"></i> Buat Akun Pegawai
            </h2>
            <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-50 focus:bg-white transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Login</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-50 focus:bg-white transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role (Hak Akses)</label>
                    <select name="role" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-50 focus:bg-white transition-colors">
                        <option value="guru">Guru Pengajar (Bisa Scan Absen)</option>
                        <option value="admin">Administrator (Akses Penuh)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="Minimal 6 Karakter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-50 focus:bg-white transition-colors">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors mt-2">
                    Buat Akun Baru
                </button>
            </form>
        </div>
    </div>

    <!-- KOLOM KANAN: TABEL DAFTAR PEGAWAI -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Daftar Pegawai Terdaftar</h3>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-bold">{{ $users->count() }} Total</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-500 uppercase bg-white border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4">Nama & Email</th>
                            <th class="px-6 py-4 text-center">Hak Akses</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($users as $user)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800 text-base">{{ $user->name }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $user->email }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($user->hasRole('admin'))
                                    <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider">Admin</span>
                                @else
                                    <span class="bg-emerald-100 text-emerald-800 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider">Guru</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 flex justify-center gap-2">
                                <!-- Cegah Admin menghapus dirinya sendiri -->
                                @if($user->id !== auth()->id())
                                    <button type="button" onclick="confirmSingleDelete('{{ $user->id }}')" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded transition font-bold text-xs flex items-center gap-2" title="Hapus Akun">
                                        <i class="fa-solid fa-trash"></i> Hapus
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 font-bold bg-gray-100 px-3 py-1.5 rounded flex items-center gap-2 cursor-not-allowed border border-gray-200">
                                        <i class="fa-solid fa-lock"></i> Sedang Dipakai
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // FUNGSI KONFIRMASI HAPUS DENGAN SWEETALERT (SERAGAM DENGAN HALAMAN LAIN)
    function confirmSingleDelete(id) {
        Swal.fire({
            title: 'Hapus Akun Pegawai?',
            text: "Akun ini akan dihapus secara permanen dan tidak bisa mengakses sistem lagi.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Buat form dadakan untuk mengirim request DELETE
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/users/' + id;
                
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