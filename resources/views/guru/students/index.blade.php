@extends('layouts.dashboard')

@section('title', 'Data Siswa')

@section('content')
<div class="space-y-6">
    <!-- Banner Info & Filter -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col md:flex-row justify-between gap-4 items-center border-l-4 border-purple-500">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Daftar Siswa Terdaftar</h2>
            <p class="text-gray-500 text-sm">Mode ini bersifat <span class="font-bold text-gray-700">Read-Only</span>. Anda dapat mencari dan melihat QR Code siswa.</p>
        </div>
        
        <!-- Form Pencarian (Filter) -->
        <form action="{{ route('guru.students.index') }}" method="GET" class="w-full md:w-1/3 relative">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari NISN, Nama, atau Kelas..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            <i class="fa-solid fa-search absolute left-3 top-3.5 text-gray-400"></i>
            @if(request('search'))
                <a href="{{ route('guru.students.index') }}" class="absolute right-3 top-3 text-red-500 text-sm font-bold hover:underline">Clear</a>
            @endif
        </form>
    </div>

    <!-- ID tabel-siswa DITAMBAHKAN DI SINI UNTUK TARGET SCROLL -->
    <div id="tabel-siswa" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-4 w-16 text-center">No</th>
                        <th class="px-6 py-4">Nama Siswa</th>
                        <th class="px-6 py-4">NISN & Kelas</th>
                        <th class="px-6 py-4 text-center">QR Code</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($students as $index => $student)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-center font-medium">{{ $students->firstItem() + $index }}</td>
                        <td class="px-6 py-4 font-bold text-gray-800">{{ $student->user->name ?? 'User Terhapus' }}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-mono text-gray-600">{{ $student->nisn }}</div>
                            <span class="bg-gray-200 text-gray-800 px-2 py-0.5 rounded text-xs font-bold whitespace-nowrap">{{ $student->class_id }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($student->qr_code_path)
                                <button type="button" onclick="showQRCode('{{ Storage::url($student->qr_code_path) }}', '{{ $student->nisn }}', '{{ addslashes($student->user->name ?? '') }}')" class="bg-purple-100 hover:bg-purple-200 text-purple-700 px-3 py-1 rounded-full text-xs font-bold transition flex items-center gap-1 mx-auto shadow-sm">
                                    <i class="fa-solid fa-qrcode"></i> Lihat QR
                                </button>
                            @else
                                <span class="text-red-500 text-xs font-bold">Kosong</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-users-slash text-4xl mb-3 text-gray-300"></i>
                            <p>Belum ada data siswa atau hasil pencarian tidak ditemukan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginasi -->
    <div class="mt-4">
        {{ $students->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    // FUNGSI BARU: Auto Scroll ke Tabel saat Pindah Halaman
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.search.includes('page=')) {
            const tabelSiswa = document.getElementById('tabel-siswa');
            if (tabelSiswa) {
                setTimeout(() => {
                    tabelSiswa.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        }
    });

    // Popup QR Code
    function showQRCode(url, nisn, name) {
        Swal.fire({
            title: name,
            text: 'NISN: ' + nisn,
            imageUrl: url,
            imageWidth: 250,
            imageHeight: 250,
            imageAlt: 'QR Code ' + nisn,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-download"></i> Unduh',
            cancelButtonText: 'Tutup',
            confirmButtonColor: '#9333ea', 
        }).then((result) => {
            if (result.isConfirmed) {
                const link = document.createElement('a');
                link.href = url;
                link.download = 'QR_' + nisn + '_' + name.replace(/\s+/g, '_') + '.svg';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        });
    }
</script>
@endpush