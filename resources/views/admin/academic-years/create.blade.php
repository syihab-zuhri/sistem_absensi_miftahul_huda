@extends('layouts.dashboard')

@section('title', 'Tambah Tahun Ajaran')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-calendar-plus text-blue-600"></i> Tambah Tahun Ajaran Baru
        </h2>

        <form method="POST" action="{{ route('admin.academic-years.store') }}" class="space-y-4 max-w-xl">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Tahun Ajaran <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    placeholder="Contoh: 2026/2027"
                    class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-2 focus:ring-blue-500">
                <p class="mt-1 text-xs text-gray-500">Format: YYYY/YYYY (contoh: <strong>2026/2027</strong>)</p>
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai (Opsional)</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}"
                        class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-2 focus:ring-blue-500">
                    @error('start_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai (Opsional)</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}"
                        class="w-full rounded-lg border-gray-300 border p-2.5 focus:ring-2 focus:ring-blue-500">
                    @error('end_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        {{ old('is_active') ? 'checked' : '' }}>
                    <span class="text-sm font-medium text-gray-700">Aktifkan tahun ajaran ini</span>
                </label>
                <p class="mt-1 text-xs text-gray-400 ml-6">
                    Jika dicentang, tahun ajaran ini akan menjadi tahun ajaran aktif dan tahun ajaran aktif sebelumnya akan dinonaktifkan.
                </p>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-gray-100">
                <a href="{{ route('admin.academic-years.index') }}" class="px-4 py-2.5 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium text-sm transition">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold text-sm transition">
                    <i class="fa-solid fa-save mr-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
@endsection