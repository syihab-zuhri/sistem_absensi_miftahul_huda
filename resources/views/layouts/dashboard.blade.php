<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Absensi') - Panel Kontrol</title>
    
    <!-- Metadata untuk Notifikasi (Menghindari Syntax Error di JS Editor) -->
    <meta name="success-msg" content="{{ session('success') }}">
    <meta name="error-msg" content="{{ session('error') }}">
    <meta name="warning-msg" content="{{ session('warning') }}">
    <meta name="validation-errors" content="{{ json_encode($errors->all()) }}">

    <!-- Aset lokal dibangun dengan Vite. -->
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<!-- Alpine x-data untuk mengontrol state sidebar di mobile -->
<body class="bg-gray-50 font-sans antialiased flex h-screen overflow-hidden" x-data="{ sidebarOpen: false, desktop: window.innerWidth >= 1024 }" @resize.window="desktop = window.innerWidth >= 1024" @keydown.escape.window="if (!desktop) { sidebarOpen = false; $nextTick(() => requestAnimationFrame(() => $refs.menuTrigger.focus())); }">

    <!-- Overlay Gelap untuk Mobile (Muncul saat sidebar dibuka) -->
    <div x-show="sidebarOpen" 
        x-transition.opacity 
        @click="sidebarOpen = false; $nextTick(() => requestAnimationFrame(() => $refs.menuTrigger.focus()))"
        class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
        style="display: none;">
    </div>

    <!-- Sidebar Kiri (Responsif: Tersembunyi di Mobile, Muncul di Desktop) -->
    <aside id="dashboard-sidebar" :inert="!desktop && !sidebarOpen" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-gray-200 shadow-sm flex flex-col transition-transform duration-300 lg:static lg:translate-x-0">
        
        <!-- Logo / Header Sidebar -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-gray-200">
            <span class="text-xl font-bold text-blue-600">Absensi<span class="text-gray-800">Sekolah</span></span>
            <!-- Tombol Tutup (Hanya di Mobile) -->
            <button aria-label="Tutup menu" x-ref="sidebarClose" @click="sidebarOpen = false; $nextTick(() => requestAnimationFrame(() => $refs.menuTrigger.focus()))" class="lg:hidden min-h-11 min-w-11 text-gray-500 hover:text-red-500 focus:outline-none">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>

        <!-- Menu Navigasi Global -->
        <nav aria-label="Navigasi utama" class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            
            <!-- MENU ADMIN -->
            @role('admin')
            <a href="/admin/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('admin/dashboard') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-house w-5 text-center"></i>
                <span class="text-sm font-medium">Dashboard Admin</span>
            </a>
            
            <div class="px-3 pt-4 pb-2"><p class="text-xs font-bold text-gray-600 uppercase tracking-wider">Master Data</p></div>
            
            <a href="/admin/users" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('admin/users*') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-user-tie w-5 text-center"></i>
                <span class="font-medium text-sm">Manajemen Guru & Admin</span>
            </a>
            <a href="/admin/students" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('admin/students*') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-users w-5 text-center"></i>
                <span class="font-medium text-sm">Data Siswa</span>
            </a>
            <a href="/admin/classrooms" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('admin/classrooms*') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-users-rectangle w-5 text-center"></i>
                <span class="font-medium text-sm">Kelas</span>
            </a>
            <a href="/admin/subjects" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('admin/subjects*') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-book w-5 text-center"></i>
                <span class="font-medium text-sm">Mata Pelajaran</span>
            </a>
            <a href="/admin/academic-years" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('admin/academic-years*') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-calendar-alt w-5 text-center"></i>
                <span class="font-medium text-sm">Tahun Ajaran</span>
            </a>
            <a href="/admin/schedules" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('admin/schedules*') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-clock w-5 text-center"></i>
                <span class="font-medium text-sm">Jadwal Pelajaran</span>
            </a>
            <a href="/scanner" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('scanner') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-camera w-5 text-center"></i>
                <span class="font-medium text-sm">Scan QR code</span>
            </a>
            @endrole

            <!-- MENU GURU -->
            @role('guru')
            <a href="/guru/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('guru/dashboard') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-home w-5 text-center"></i>
                <span class="text-sm font-medium">Dashboard Guru</span>
            </a>
            <a href="/scanner" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('scanner') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-camera w-5 text-center"></i>
                <span class="font-medium text-sm">Scan QR code</span>
            </a>
            <!-- Placeholder untuk Fitur Data Siswa Guru (Sesuai Roadmap No. 9) -->
            <a href="{{ route('guru.students.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('guru.students.index') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-users w-5 text-center"></i>
                <span class="font-medium text-sm">Data Siswa</span>
            </a>
            @endrole

            <!-- MENU SISWA -->
            @role('siswa')
            <a href="{{ route('student.portal') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('student.portal') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-user-graduate w-5 text-center"></i>
                <span class="text-sm font-medium">Portal Siswa</span>
            </a>
            <a href="{{ route('student.settings') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->routeIs('student.settings') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-cog w-5 text-center"></i>
                <span class="text-sm font-medium">Pengaturan</span>
            </a>
            @endrole
            
            @hasanyrole('admin|guru')
            <a href="{{ route('attendance-sessions.index') }}" class="flex min-h-11 items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('attendance-sessions.*') ? 'bg-blue-50 font-bold text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}"><i aria-hidden="true" class="fa-solid fa-calendar-check w-5 text-center"></i><span class="text-sm font-medium">Sesi Presensi</span></a>
            <a href="{{ route('attendance-audits.index') }}" class="flex min-h-11 items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('attendance-audits.*') ? 'bg-blue-50 font-bold text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}"><i aria-hidden="true" class="fa-solid fa-clock-rotate-left w-5 text-center"></i><span class="text-sm font-medium">Riwayat Perubahan</span></a>
            @endhasanyrole
            @hasanyrole('admin|guru|siswa')
            <a href="{{ route('leave-requests.index') }}" class="flex min-h-11 items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('leave-requests.*') ? 'bg-blue-50 font-bold text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700' }}"><i aria-hidden="true" class="fa-solid fa-envelope-open-text w-5 text-center"></i><span class="text-sm font-medium">Pengajuan Izin/Sakit</span></a>
            @endhasanyrole
            @hasanyrole('admin|guru')
            <!-- MENU UMUM (ADMIN & GURU) -->
            <div class="px-3 pt-4 pb-2"><p class="text-xs font-bold text-gray-600 uppercase tracking-wider">Rekapitulasi</p></div>
            <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('reports*') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-file-invoice w-5 text-center"></i>
                <span class="font-medium text-sm">Rekapitulasi Absensi</span>
            </a>
            @endhasanyrole

            <!-- Tombol Keluar -->
            <form method="POST" action="{{ route('logout') }}" class="w-full mt-auto pt-4 border-t border-gray-100">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 transition-colors">
                    <i class="fa-solid fa-sign-out-alt w-5 text-center"></i>
                    <span class="font-medium text-sm">Keluar Sistem</span>
                </button>
            </form>
        </nav>
    </aside>

    <!-- Konten Utama Kanan -->
    <div :inert="sidebarOpen && !desktop" class="min-w-0 flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Header Atas -->
        <header class="h-16 flex items-center justify-between px-4 lg:px-8 bg-white border-b border-gray-200 shrink-0">
            <!-- Hamburger Menu (Mobile Only) -->
            <button aria-label="Buka menu" aria-controls="dashboard-sidebar" :aria-expanded="sidebarOpen" x-ref="menuTrigger" @click="sidebarOpen = true; $nextTick(() => requestAnimationFrame(() => $refs.sidebarClose.focus()))" class="lg:hidden min-h-11 min-w-11 p-2 rounded-md text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>

            <!-- Judul Halaman Dinamis -->
            <h1 class="text-lg font-semibold text-gray-800 ml-4 lg:ml-0 truncate">@yield('title', 'Dashboard')</h1>
            
            <!-- Profil -->
            <button onclick="showAdminProfile()" class="flex items-center gap-3 hover:bg-gray-100 p-2 rounded-lg transition-colors border border-transparent hover:border-gray-200">
                <span class="text-sm font-medium text-gray-600">👤 {{ Auth::user()->name }}</span>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full uppercase font-bold">{{ Auth::user()->roles->first()->name ?? 'Pengguna' }}</span>
            </button>
        </header>

        <!-- Area Konten (Dapat digulir) -->
        <main class="flex-1 overflow-y-auto p-4 lg:p-8 bg-gray-50 relative">
            @yield('content')
        </main>
    </div>

    <!-- Script Global SweetAlert untuk Notifikasi Session & Error -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mengambil data dari HTML meta tags, bukan langsung dari PHP tag 
            // Ini akan 100% menghilangkan "SyntaxError" di browser maupun di VS Code
            let successMsg = document.querySelector('meta[name="success-msg"]').content;
            let errorMsg = document.querySelector('meta[name="error-msg"]').content;
            let warningMsg = document.querySelector('meta[name="warning-msg"]').content;
            let validationRaw = document.querySelector('meta[name="validation-errors"]').content;
            
            let validationErrors = [];
            if (validationRaw) {
                try { validationErrors = JSON.parse(validationRaw); } catch (e) {}
            }

            if (successMsg) Swal.fire({ icon: 'success', title: 'Berhasil!', text: successMsg, timer: 3000, showConfirmButton: false });
            if (errorMsg) Swal.fire({ icon: 'error', title: 'Oops...', text: errorMsg });
            if (warningMsg) Swal.fire({ icon: 'warning', title: 'Perhatian', text: warningMsg });

            if (validationErrors && validationErrors.length > 0) {
                Swal.fire({ icon: 'error', titleText: 'Terjadi Kesalahan', text: validationErrors.join('\n') });
            }
        });
    </script>

    <script>
    function showAdminProfile() {
        const profile = @js(['name' => Auth::user()->name, 'email' => Auth::user()->email, 'role' => Auth::user()->roles->first()->name ?? 'Pengguna']);
        const details = document.createElement('div');
        details.className = 'text-left space-y-2';
        for (const [label, value] of [['Nama Lengkap', profile.name], ['Alamat Email', profile.email], ['Hak Akses', profile.role]]) {
            const line = document.createElement('p');
            line.textContent = label + ': ' + value;
            details.append(line);
        }
        Swal.fire({ titleText: 'Profil ' + profile.name, html: details, icon: 'info', confirmButtonText: 'Tutup' });
    }
    </script>
    
    <!-- Tempat untuk script tambahan dari masing-masing halaman -->
    @stack('scripts')
</body>
</html>
