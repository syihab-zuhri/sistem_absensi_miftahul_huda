@extends('layouts.dashboard')

@section('title', 'Scanner Absensi Kelas')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Status Sesi Aktif -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-blue-600 px-6 py-4 flex justify-between items-center text-white">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <i class="fa-solid fa-satellite-dish animate-pulse"></i> Status Sesi Berjalan
            </h2>
            <div class="text-sm font-medium bg-blue-700 px-3 py-1 rounded-full shadow-inner">
                {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y') }}
            </div>
        </div>
        <div class="p-6 text-center">
            @if(isset($activeSchedule))
                <h3 class="text-2xl font-black text-gray-800 mb-1">{{ $activeSchedule->subject->name ?? 'Mapel Tidak Diketahui' }}</h3>
                <p class="text-gray-500 font-medium mb-3">Kelas: <span class="text-blue-600 font-bold">{{ $activeSchedule->class_id }}</span> | Waktu: {{ \Carbon\Carbon::parse($activeSchedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($activeSchedule->end_time)->format('H:i') }}</p>
            @else
                <div class="py-8">
                    <i class="fa-solid fa-user-lock text-5xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-700">Akses Scanner Ditutup</h3>
                    <p class="text-gray-500 mt-2 max-w-md mx-auto">Anda tidak dapat menggunakan Scanner karena Anda tidak memiliki jadwal mengajar di kelas manapun pada hari dan jam ini.</p>
                </div>
            @endif
        </div>
    </div>

    @if(isset($activeSchedule))
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- KOLOM KIRI: SCANNER -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden sticky top-6">
                <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider text-center">Kamera Pemindai</h3>
                </div>
                
                <div class="p-0 bg-black relative">
                    <div class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none">
                        <div class="w-48 h-48 border-2 border-white border-dashed opacity-50 relative">
                            <div class="absolute top-0 left-0 w-4 h-4 border-t-4 border-l-4 border-blue-500"></div>
                            <div class="absolute top-0 right-0 w-4 h-4 border-t-4 border-r-4 border-blue-500"></div>
                            <div class="absolute bottom-0 left-0 w-4 h-4 border-b-4 border-l-4 border-blue-500"></div>
                            <div class="absolute bottom-0 right-0 w-4 h-4 border-b-4 border-r-4 border-blue-500"></div>
                        </div>
                    </div>
                    <div id="reader" class="w-full"></div>
                </div>
                
                <!-- Tombol Kontrol Kamera Pindah Kesini -->
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-center">
                    <div id="kamera-kontrol" class="flex flex-wrap justify-center gap-2">
                        <span class="text-sm font-medium text-gray-500" id="kamera-info">Mencari Kamera...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: DAFTAR SELURUH SISWA (Kelas Aktif) -->
        <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-gray-200 flex flex-col h-[700px]">
            
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">
                    Daftar Siswa - Kelas {{ $activeSchedule->class_id }}
                </h3>
                <div class="flex gap-2 w-full sm:w-auto">
                    <!-- Tombol Simpan Sesi (Baru) -->
                    <button type="button" onclick="saveSession()" id="btnSaveSession" class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-all flex items-center justify-center gap-2 text-sm shadow-sm opacity-50 cursor-not-allowed">
                        <i class="fa-solid fa-save"></i> Simpan Sesi
                    </button>
                    <!-- Tombol Cetak PDF -->
                    <a href="{{ route('reports.session', $activeSchedule->id) }}" id="btnPrintPdf" class="flex-1 sm:flex-none bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2 text-sm">
                        <i class="fa-solid fa-print"></i> Cetak PDF
                    </a>
                </div>
            </div>

            <!-- Tabel Daftar Siswa yang dimuat secara dinamis -->
            <div class="flex-1 overflow-y-auto p-0">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-400 uppercase bg-white sticky top-0 shadow-sm z-10">
                        <tr>
                            <th class="px-6 py-3 w-12 text-center">No</th>
                            <th class="px-6 py-3">Nama Lengkap & NISN</th>
                            <th class="px-6 py-3 text-center w-40">Status Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody" class="divide-y divide-gray-100">
                        <tr><td colspan="3" class="p-8 text-center"><i class="fa-solid fa-spinner fa-spin text-2xl text-blue-500"></i> Memuat data kelas...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    @endif
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>

@if(isset($activeSchedule))
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let isProcessing = false;
    let isCameraRunning = false;
    let currentCameraId = null;
    let html5QrCode = null;
    let cameras = [];
    
    // STATE: Mencegah guru keluar tanpa menyimpan
    let hasUnsavedChanges = false;

    // ==========================================
    // 1. FUNGSI AUDIO (Berbunyi saat scan berhasil/gagal)
    // ==========================================
    function playBeep(type = 'success') {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        oscillator.type = type === 'success' ? 'sine' : 'sawtooth';
        oscillator.frequency.setValueAtTime(type === 'success' ? 1000 : 300, audioCtx.currentTime);
        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        oscillator.start();
        setTimeout(() => oscillator.stop(), type === 'success' ? 150 : 300);
    }

// ==========================================
    // 2. FUNGSI DATA SISWA & UI (REVISI)
    // ==========================================
    function loadSessionData() {
        fetch('/absensi/session-data/{{ $activeSchedule->id }}')
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('studentTableBody');
            tbody.innerHTML = '';
            
            if(data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-red-500 font-bold">Tidak ada siswa terdaftar di kelas ini!</td></tr>';
                return;
            }

            data.forEach((student, index) => {
                let statusVal = student.status || ''; 
                
                let row = `
                    <tr class="hover:bg-blue-50 transition student-row" id="row-${student.nisn}">
                        <td class="px-6 py-4 text-center font-medium">${index + 1}</td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800 student-name-text">${student.name}</div>
                            <div class="text-xs text-gray-500 font-mono">NISN: ${student.nisn}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <select data-id="${student.id}" data-nisn="${student.nisn}" onchange="markUnsaved(this)" class="status-dropdown w-full text-xs font-bold rounded-lg px-2 py-2 border outline-none focus:ring-2 focus:ring-blue-500 transition-colors shadow-sm cursor-pointer">
                                <option value="" class="text-gray-500">-- BELUM ABSEN --</option>
                                <option value="hadir" ${statusVal === 'hadir' ? 'selected' : ''} class="text-emerald-700"> HADIR</option>
                                <option value="sakit" ${statusVal === 'sakit' ? 'selected' : ''} class="text-blue-700"> SAKIT</option>
                                <option value="izin" ${statusVal === 'izin' ? 'selected' : ''} class="text-yellow-700"> IZIN</option>
                                <option value="alpa" ${statusVal === 'alpa' ? 'selected' : ''} class="text-red-700"> ALPA</option>
                            </select>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', row);
            });
            
            document.querySelectorAll('.status-dropdown').forEach(sel => applySelectColor(sel));
        });
    }

    // Mengubah warna dropdown berdasarkan status yang dipilih
    function applySelectColor(selectElem) {
        selectElem.classList.remove('bg-gray-50','text-gray-500','border-gray-300', 'bg-emerald-50','text-emerald-700','border-emerald-200', 'bg-blue-50','text-blue-700','border-blue-200', 'bg-yellow-50','text-yellow-700','border-yellow-200', 'bg-red-50','text-red-700','border-red-200');
        
        switch(selectElem.value) {
            case 'hadir': selectElem.classList.add('bg-emerald-50','text-emerald-700','border-emerald-200'); break;
            case 'sakit': selectElem.classList.add('bg-blue-50','text-blue-700','border-blue-200'); break;
            case 'izin': selectElem.classList.add('bg-yellow-50','text-yellow-700','border-yellow-200'); break;
            case 'alpa': selectElem.classList.add('bg-red-50','text-red-700','border-red-200'); break;
            default: selectElem.classList.add('bg-gray-50','text-gray-500','border-gray-300'); break;
        }
    }

    // Memicu peringatan Unsaved Changes jika ada dropdown yang dirubah
    window.markUnsaved = function(selectElem) {
        applySelectColor(selectElem);
        hasUnsavedChanges = true;
        
        // Aktifkan visual tombol Simpan (Jadi berkedip hijau)
        const btn = document.getElementById('btnSaveSession');
        btn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-blue-600');
        btn.classList.add('bg-green-600', 'animate-pulse');
        btn.innerHTML = '<i class="fa-solid fa-save"></i> Simpan Sesi';
    }

    // ==========================================
    // 3. FUNGSI SIMPAN MASSAL (BATCH SAVE)
    // ==========================================
    window.saveSession = function() {
        if (!hasUnsavedChanges) return; // Jika tidak ada perubahan, abaikan

        // Kumpulkan data siswa yang sudah diabsen (yang dropdownnya tidak kosong)
        let attendancesData = [];
        document.querySelectorAll('.status-dropdown').forEach(sel => {
            if (sel.value !== '') {
                attendancesData.push({ student_id: sel.dataset.id, status: sel.value });
            }
        });

        if (attendancesData.length === 0) {
            Swal.fire('Kosong', 'Belum ada siswa yang diabsen sama sekali.', 'info');
            return;
        }

        // Tampilkan loading screen
        Swal.fire({ title: 'Menyimpan...', text: 'Memperbarui database absen', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

        // Kirim semua data sekaligus ke server
        fetch('/absensi/save-session/{{ $activeSchedule->id }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ attendances: attendancesData })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                hasUnsavedChanges = false;
                
                // Kembalikan visual tombol simpan ke mode awal
                const btn = document.getElementById('btnSaveSession');
                btn.classList.add('opacity-50', 'cursor-not-allowed', 'bg-blue-600');
                btn.classList.remove('bg-green-600', 'animate-pulse');
                btn.innerHTML = '<i class="fa-solid fa-save"></i> Sesi Tersimpan';

                Swal.fire('Tersimpan!', 'Seluruh data absensi berhasil direkam.', 'success');
            }
        }).catch(err => {
            console.error(err);
            Swal.fire('Error', 'Terjadi kesalahan jaringan saat menyimpan.', 'error');
        });
    }

// ==========================================
    // 4. LOGIKA SCANNER MURNI (REVISI ALERT & OVERRIDE LOGIC)
    // ==========================================
    function handleScan(decodedText) {
        if (isProcessing) return;
        isProcessing = true;

        // Cari Dropdown Siswa berdasarkan NISN di layar 
        let selectElem = document.querySelector(`select[data-nisn="${decodedText}"]`);
        
        if (selectElem) {
            // Ambil nama siswa dari tabel untuk dimasukkan ke notifikasi
            let rowElem = document.getElementById('row-' + decodedText);
            let studentName = rowElem ? rowElem.querySelector('.student-name-text').innerText : 'Siswa';

            // REVISI LOGIKA: Jika status saat ini BUKAN 'hadir' (bisa kosong, sakit, izin, atau alpa), paksa jadi 'hadir'
            if (selectElem.value !== 'hadir') {
                selectElem.value = 'hadir';
                markUnsaved(selectElem); // Memicu tombol simpan berkedip
                playBeep('success');
                
                // REVISI ALERT: Tampilkan di Pojok Kiri Atas (top-start) & TANPA SCROLLING DOWN
                const Toast = Swal.mixin({ 
                    toast: true, 
                    position: 'top-start', 
                    showConfirmButton: false, 
                    timer: 2000,
                    timerProgressBar: true 
                });
                Toast.fire({ 
                    icon: 'success', 
                    title: studentName + ' Berhasil Absensi (Hadir)!' 
                });
            } else {
                // Jika sudah hadir, cukup bunyikan bip success tanpa mengubah apa pun (mencegah double scan)
                playBeep('success'); 
                
                const Toast = Swal.mixin({ toast: true, position: 'top-start', showConfirmButton: false, timer: 1500 });
                Toast.fire({ 
                    icon: 'info', 
                    title: studentName + ' sudah berstatus Hadir.' 
                });
            }
        } else {
            // Bunyi salah jika QR Code siswa luar kelas/tidak terdaftar
            playBeep('error');
            Swal.fire({ icon: 'error', title: 'Ditolak', text: 'NISN ' + decodedText + ' tidak terdaftar di kelas ini!', timer: 2000, showConfirmButton: false });
        }

        // Kunci kamera 1 detik agar tidak terjadi double-scan instan
        setTimeout(() => { isProcessing = false; }, 1500);
    }

    // ==========================================
    // 5. INISIALISASI & PROTEKSI KAMERA
    // ==========================================
    function initScanner() {
        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                cameras = devices;
                let targetCamera = devices.find(c => c.label.toLowerCase().includes('back') || c.label.toLowerCase().includes('environment'));
                currentCameraId = targetCamera ? targetCamera.id : devices[0].id;
                startCamera(currentCameraId);
                buildCameraControls();
            } else {
                document.getElementById('kamera-info').innerHTML = '<span class="text-red-500"><i class="fa-solid fa-triangle-exclamation"></i> Kamera tidak ditemukan.</span>';
            }
        }).catch(err => console.error(err));
    }

    function startCamera(cameraId) {
        if (!html5QrCode) html5QrCode = new Html5Qrcode("reader");
        html5QrCode.start(
            cameraId, { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 },
            (decodedText) => handleScan(decodedText), () => {}
        ).then(() => { isCameraRunning = true; }).catch(err => console.error(err));
    }

    function buildCameraControls() {
        const container = document.getElementById('kamera-kontrol');
        container.innerHTML = '';
        if (cameras.length > 1) {
            const btnTukar = document.createElement('button');
            btnTukar.className = 'bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1.5 px-3 rounded text-xs transition flex items-center gap-2 shadow-sm';
            btnTukar.innerHTML = '<i class="fa-solid fa-camera-rotate"></i> Putar';
            btnTukar.onclick = () => {
                let currentIndex = cameras.findIndex(c => c.id === currentCameraId);
                currentCameraId = cameras[(currentIndex + 1) % cameras.length].id;
                if (isCameraRunning) { html5QrCode.stop().then(() => startCamera(currentCameraId)); } 
                else { startCamera(currentCameraId); toggleCameraPowerUI(true); }
            };
            container.appendChild(btnTukar);
        }
        const btnPower = document.createElement('button');
        btnPower.id = 'btn-power-kamera';
        btnPower.className = 'bg-red-100 hover:bg-red-200 text-red-700 font-bold py-1.5 px-3 rounded text-xs transition flex items-center gap-2 shadow-sm';
        btnPower.innerHTML = '<i class="fa-solid fa-video-slash"></i> Matikan';
        btnPower.onclick = () => {
            if (isCameraRunning) {
                html5QrCode.stop().then(() => { isCameraRunning = false; toggleCameraPowerUI(false); });
            } else { startCamera(currentCameraId); toggleCameraPowerUI(true); }
        };
        container.appendChild(btnPower);
    }

    function toggleCameraPowerUI(isRunning) {
        const btnPower = document.getElementById('btn-power-kamera');
        if (!btnPower) return;
        if (isRunning) {
            btnPower.className = 'bg-red-100 hover:bg-red-200 text-red-700 font-bold py-1.5 px-3 rounded text-xs transition flex items-center gap-2 shadow-sm';
            btnPower.innerHTML = '<i class="fa-solid fa-video-slash"></i> Matikan';
        } else {
            btnPower.className = 'bg-green-100 hover:bg-green-200 text-green-700 font-bold py-1.5 px-3 rounded text-xs transition flex items-center gap-2 shadow-sm';
            btnPower.innerHTML = '<i class="fa-solid fa-video"></i> Nyalakan';
        }
    }

    // ==========================================
    // 6. PROTEKSI HALAMAN (Unsaved Changes)
    // ==========================================
    // Cegah tutup/refresh browser jika lupa tekan simpan
    window.addEventListener('beforeunload', function (e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = ''; // Teks standar browser akan muncul
        }
    });

    // Cegah cetak PDF sebelum disimpan
    document.getElementById('btnPrintPdf').addEventListener('click', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Disimpan!',
                text: 'Harap klik tombol "Simpan Sesi" terlebih dahulu agar data masuk ke dalam PDF cetak.',
                confirmButtonColor: '#4f46e5'
            });
        }
    });

    // Jalankan inisialisasi saat halaman pertama dibuka
    window.onload = () => {
        initScanner();
        loadSessionData();
    };
</script>
@endif
@endpush