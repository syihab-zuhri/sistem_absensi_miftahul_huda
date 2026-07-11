@extends('layouts.dashboard')

@section('title', 'Tahun Ajaran')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
            <!-- Search and Create -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <div class="w-full md:w-1/3 mb-4 md:mb-0">
                    <form method="GET" action="{{ route('admin.academic-years.index') }}">
                        <div class="flex">
                            <input
                                type="text"
                                name="search"
                                placeholder="Cari tahun ajaran..."
                                class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-2 focus:ring-blue-500"
                                value="{{ request('search') }}"
                            />
                            <button type="submit" class="ml-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors">
                                Cari
                            </button>
                            @if(request('search'))
                                <a href="{{ route('admin.academic-years.index') }}" class="ml-2 px-4 py-2.5 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <div>
                    <a href="{{ route('admin.academic-years.create') }}" class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold transition-colors">
                        <i class="fa-solid fa-plus mr-1"></i> Tambah Tahun Ajaran
                    </a>
                </div>
            </div>

            <!-- Academic Years Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 whitespace-nowrap">Nama</th>
                            <th class="px-6 py-3 whitespace-nowrap">Tanggal Mulai</th>
                            <th class="px-6 py-3 whitespace-nowrap">Tanggal Selesai</th>
                            <th class="px-6 py-3 whitespace-nowrap">Status</th>
                            <th class="px-6 py-3 whitespace-nowrap">Siswa</th>
                            <th class="px-6 py-3 whitespace-nowrap">Jadwal</th>
                            <th class="px-6 py-3 whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($academicYears as $year)
                            <tr class="hover:bg-gray-50 transition-colors {{ $year->is_active ? 'bg-green-50' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap font-bold {{ $year->is_active ? 'text-green-800' : 'text-gray-800' }}">
                                    {{ $year->name }}
                                    @if($year->is_active)
                                        <span class="ml-1 text-green-600"><i class="fa-solid fa-check-circle"></i></span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $year->start_date ? \Carbon\Carbon::parse($year->start_date)->format('d M Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $year->end_date ? \Carbon\Carbon::parse($year->end_date)->format('d M Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($year->is_active)
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold border border-green-200">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-medium border border-gray-200">
                                            Non-Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $year->studentClassHistories()->count() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $year->schedules()->count() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.academic-years.edit', $year) }}" class="text-yellow-600 hover:text-yellow-800 bg-yellow-50 hover:bg-yellow-100 px-2 py-1.5 rounded transition" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <button type="button" onclick="confirmDelete({{ $year->id }})" class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-2 py-1.5 rounded transition" title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <form id="delete-form-{{ $year->id }}" method="POST" action="{{ route('admin.academic-years.destroy', $year) }}" class="hidden">
                                            @csrf
                                            @method('DELETE')
                                        </form>

                                        @if(!$year->is_active)
                                            <button type="button" onclick="confirmActivate({{ $year->id }}, '{{ $year->name }}')" class="text-green-600 hover:text-green-800 bg-green-50 hover:bg-green-100 px-2 py-1.5 rounded transition" title="Aktifkan">
                                                <i class="fa-solid fa-power-off"></i>
                                            </button>
                                            <form id="activate-form-{{ $year->id }}" method="POST" action="{{ route('admin.academic-years.activate', $year) }}" class="hidden">
                                                @csrf
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fa-regular fa-calendar-xmark text-4xl mb-3 block text-gray-300"></i>
                                    <p>Belum ada tahun ajaran.</p>
                                    <a href="{{ route('admin.academic-years.create') }}" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium text-sm">
                                        <i class="fa-solid fa-plus mr-1"></i> Tambah Tahun Ajaran Baru
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $academicYears->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Hapus Tahun Ajaran?',
        text: 'Tahun ajaran ini akan dihapus secara permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + id).submit();
        }
    });
}

function confirmActivate(id, name) {
    Swal.fire({
        title: 'Aktifkan Tahun Ajaran?',
        text: 'Tahun ajaran ' + name + ' akan diaktifkan. Tahun ajaran aktif sebelumnya akan dinonaktifkan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#22c55e',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Aktifkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('activate-form-' + id).submit();
        }
    });
}
</script>
@endpush