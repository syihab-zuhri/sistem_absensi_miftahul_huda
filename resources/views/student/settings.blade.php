@extends('layouts.dashboard')

@section('title', 'Pengaturan Akun')

@section('content')
@if(auth()->user()->must_change_password)<p class="mb-4 rounded-lg bg-blue-50 text-blue-900 p-4" role="status">Ganti sandi awal untuk mengaktifkan akses akun.</p>@endif
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Kolom Kiri: Menu Pengaturan -->
        <div class="space-y-4">
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="p-6 border-b border-gray-50 bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-cog text-blue-500"></i>
                        Pengaturan
                    </h3>
                </div>
                <div class="p-4">
                    <ul class="space-y-2">
                        <li>
                            <a href="#keamanan" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-blue-50 text-blue-600 font-bold">
                                <i class="fa-solid fa-shield-halved w-5 text-center"></i>
                                <span class="text-sm">Keamanan Akun</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Konten Pengaturan -->
        <div class="lg:col-span-2 space-y-8">

            <!-- Email Login Pribadi -->
            <div id="email" class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="p-6 border-b border-gray-50 bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-envelope text-blue-500"></i>
                        Email Login Pribadi
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-500 mb-4">Tambahkan email pribadimu agar bisa login menggunakan email selain NISN dan menerima tautan reset sandi bila lupa sandi. Tanpa email pribadi, reset sandi hanya dapat dilakukan oleh admin.</p>
                    <form action="{{ route('student.email.update') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="field-student-settings-1" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Email Pribadi</label>
                            <input id="field-student-settings-1" type="email" name="personal_email" value="{{ old('personal_email', $student->personal_email) }}" required placeholder="contoh: namamu@gmail.com"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3 transition-all">
                        </div>
                        @if($student->personal_email)
                        <div class="flex items-center gap-2 text-sm text-green-600 bg-green-50 p-3 rounded-lg border border-green-100">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Email aktif: <strong>{{ $student->personal_email }}</strong></span>
                        </div>
                        @endif
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl text-sm transition-all shadow-sm hover:shadow-md">
                            Simpan Email Pribadi
                        </button>
                    </form>
                </div>
            </div>

            <!-- Keamanan Akun -->
            <div id="keamanan" class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="p-6 border-b border-gray-50 bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-yellow-500"></i>
                        Keamanan Akun
                    </h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('student.password.update') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="field-student-settings-2" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Sandi Saat Ini</label>
                            <input id="field-student-settings-2" type="password" name="current_password" required placeholder="Sandi saat ini (Default: NISN)"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3 transition-all">
                        </div>
                        <div>
                            <label for="field-student-settings-3" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Sandi Baru</label>
                            <input id="field-student-settings-3" type="password" name="new_password" required placeholder="Minimal 8 karakter"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3 transition-all">
                        </div>
                        <div>
                            <label for="field-student-settings-4" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Konfirmasi Sandi Baru</label>
                            <input id="field-student-settings-4" type="password" name="new_password_confirmation" required placeholder="Ulangi sandi baru"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3 transition-all">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl text-sm transition-all shadow-sm hover:shadow-md">
                            Perbarui Kata Sandi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection