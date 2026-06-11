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

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Alpine.js (Untuk Menu Mobile) -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Font Awesome (Opsional untuk icon) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @stack('styles')
</head>
<!-- Alpine x-data untuk mengontrol state sidebar di mobile -->
<body class="bg-gray-50 font-sans antialiased flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">

    <!-- Overlay Gelap untuk Mobile (Muncul saat sidebar dibuka) -->
    <div x-show="sidebarOpen" 
        x-transition.opacity 
        @click="sidebarOpen = false"
        class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
        style="display: none;">
    </div>

    <!-- Sidebar Kiri (Responsif: Tersembunyi di Mobile, Muncul di Desktop) -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-gray-200 shadow-sm flex flex-col transition-transform duration-300 lg:static lg:translate-x-0">
        
        <!-- Logo / Header Sidebar -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-gray-200">
            <span class="text-xl font-bold text-blue-600">Absensi<span class="text-gray-800">Sekolah</span></span>
            <!-- Tombol Tutup (Hanya di Mobile) -->
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-500 hover:text-red-500 focus:outline-none">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>

        <!-- Menu Navigasi Global -->
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            
            <!-- MENU ADMIN -->
            @role('admin')
            <a href="/admin/dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('admin/dashboard') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-house w-5 text-center"></i>
                <span class="text-sm font-medium">Dashboard Admin</span>
            </a>
            
            <div class="px-3 pt-4 pb-2"><p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Master Data</p></div>
            
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
            <a href="/admin/schedules" class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ request()->is('admin/schedules*') ? 'text-blue-600 bg-blue-50 font-bold' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' }} transition-colors">
                <i class="fa-solid fa-calendar-alt w-5 text-center"></i>
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
            @endrole
            
            @hasanyrole('admin|guru')
            <!-- MENU UMUM (ADMIN & GURU) -->
            <div class="px-3 pt-4 pb-2"><p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Rekapitulasi</p></div>
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
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Header Atas -->
        <header class="h-16 flex items-center justify-between px-4 lg:px-8 bg-white border-b border-gray-200 shrink-0">
            <!-- Hamburger Menu (Mobile Only) -->
            <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-md text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                let errorHtml = '<ul class="text-left text-sm text-red-600 list-disc pl-5">';
                validationErrors.forEach(error => { errorHtml += '<li>' + error + '</li>'; });
                errorHtml += '</ul>';
                Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', html: errorHtml });
            }
        });
    </script>

    <script>
    function showAdminProfile() {
        Swal.fire({
            title: 'Profil {{ Auth::user()->name }}',
            html: `
                <div class="text-left bg-gray-50 p-4 rounded-lg mt-4 border border-gray-200">
                    <p class="mb-2"><strong>Nama Lengkap:</strong> {{ Auth::user()->name }}</p>
                    <p class="mb-2"><strong>Alamat Email:</strong> {{ Auth::user()->email }}</p>
                    <p><strong>Hak Akses:</strong> <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-sm font-bold uppercase">{{ Auth::user()->roles->first()->name ?? 'Pengguna' }}</span></p>
                </div>
            `,
            icon: 'info',
            confirmButtonText: 'Tutup Panel',
            confirmButtonColor: '#3b82f6',
            showClass: { popup: 'animate__animated animate__fadeInDown' },
            hideClass: { popup: 'animate__animated animate__fadeOutUp' }
        });
    }
    </script>
    
    <!-- Tempat untuk script tambahan dari masing-masing halaman -->
    @stack('scripts')
</body>
</html>