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
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-50 text-green-700 rounded-lg border border-green-200 font-bold text-sm">
                    <span class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                    Sesi Aktif & Menerima Absensi
                </div>
            @else
                <!-- REVISI PESAN: Jika guru tidak punya jadwal aktif -->
                <div class="py-8">
                    <i class="fa-solid fa-user-lock text-5xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-700">Akses Scanner Ditutup</h3>
                    <p class="text-gray-500 mt-2 max-w-md mx-auto">Anda tidak dapat menggunakan Scanner karena Anda tidak memiliki jadwal mengajar di kelas manapun pada hari dan jam ini.</p>
                </div>
            @endif
        </div>
    </div>

    @if(isset($activeSchedule))
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        
        <!-- KOLOM KIRI: SCANNER & INPUT MANUAL -->
        <div class="space-y-6">
            
            <!-- Modul Kamera -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Arahkan QR Code</h3>
                </div>
                
                <div class="p-4 bg-black relative">
                    <!-- Target Kotak Scanner di Tengah -->
                    <div class="absolute inset-0 z-10 flex items-center justify-center pointer-events-none">
                        <div class="w-48 h-48 sm:w-64 sm:h-64 border-2 border-white border-dashed opacity-50 relative">
                            <!-- Sudut-sudut target -->
                            <div class="absolute top-0 left-0 w-4 h-4 border-t-4 border-l-4 border-blue-500"></div>
                            <div class="absolute top-0 right-0 w-4 h-4 border-t-4 border-r-4 border-blue-500"></div>
                            <div class="absolute bottom-0 left-0 w-4 h-4 border-b-4 border-l-4 border-blue-500"></div>
                            <div class="absolute bottom-0 right-0 w-4 h-4 border-b-4 border-r-4 border-blue-500"></div>
                        </div>
                    </div>
                    
                    <!-- Area Video HTML5-QRCode -->
                    <div id="reader" class="w-full rounded-lg overflow-hidden"></div>
                </div>
                
                <!-- Posisi Baru Tombol Tukar Kamera (Sesuai Permintaan) -->
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-center">
                    <div id="kamera-kontrol" class="flex items-center gap-3">
                        <span class="text-sm font-medium text-gray-500" id="kamera-info">Mencari Kamera...</span>
                    </div>
                </div>
            </div>

            <!-- Modul Input Manual (Jika QR Code rusak) -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4 border-b pb-2">Plan B: Input Manual</h3>
                <form id="manualScanForm" onsubmit="handleManualScan(event)" class="flex gap-2">
                    <input type="text" id="manualNisn" placeholder="Ketik NISN Siswa..." required class="flex-1 rounded-lg border-gray-300 border px-4 py-2 focus:ring-2 focus:ring-blue-500 text-sm">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-bold py-2 px-4 rounded-lg text-sm transition">Proses</button>
                </form>
            </div>
        </div>

        <!-- KOLOM KANAN: TABEL RIWAYAT & CETAK SESI -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-[600px]">
            
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Log Sesi Ini</h3>
                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-bold" id="totalHadir">0 Hadir</span>
            </div>

            <!-- Posisi Baru Tombol Cetak Sesi Saat Ini (Sesuai Permintaan) -->
            <div class="p-4 border-b border-gray-100 bg-white">
                <a href="{{ route('reports.session', $activeSchedule->id) }}" target="_blank" class="w-full bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold py-2 px-4 rounded-lg transition flex items-center justify-center gap-2 border border-blue-200 shadow-sm text-sm">
                    <i class="fa-solid fa-print"></i> Cetak Laporan Sesi Ini (PDF)
                </a>
            </div>

            <div class="flex-1 overflow-y-auto p-0" id="logContainer">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-400 uppercase bg-white sticky top-0 shadow-sm z-10">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Siswa</th>
                            <th class="px-4 py-3 text-center">Status Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody id="logTableBody" class="divide-y divide-gray-100">
                        <!-- Data akan dimasukkan ke sini oleh JavaScript -->
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-gray-400 italic">Menunggu pemindaian pertama...</td>
                        </tr>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

@if(isset($activeSchedule))
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let isProcessing = false;
    let currentCameraId = null;
    let html5QrCode = null;
    let cameras = [];
    let isCameraRunning = false

    // Audio Feedback
    function playBeep(type = 'success') {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        
        oscillator.type = type === 'success' ? 'sine' : 'sawtooth';
        oscillator.frequency.setValueAtTime(type === 'success' ? 1000 : 300, audioCtx.currentTime);
        
        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime); // Volume rendah
        
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        oscillator.start();
        setTimeout(() => oscillator.stop(), type === 'success' ? 150 : 300);
    }

    // Inisialisasi Kamera
    function initScanner() {
        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                cameras = devices;
                // Gunakan kamera belakang (environment) sebagai default jika ada
                let targetCamera = devices.find(c => c.label.toLowerCase().includes('back') || c.label.toLowerCase().includes('environment'));
                currentCameraId = targetCamera ? targetCamera.id : devices[0].id;
                
                startCamera(currentCameraId);
                buildCameraControls();
            } else {
                document.getElementById('kamera-info').innerHTML = '<span class="text-red-500"><i class="fa-solid fa-triangle-exclamation"></i> Kamera tidak ditemukan.</span>';
            }
        }).catch(err => {
            document.getElementById('kamera-info').innerHTML = '<span class="text-red-500"><i class="fa-solid fa-lock"></i> Izin kamera ditolak.</span>';
            console.error(err);
        });
    }

    // Membangun Tombol Putar & Tombol Nyala/Mati Kamera
    function buildCameraControls() {
        const container = document.getElementById('kamera-kontrol');
        container.innerHTML = ''; // Bersihkan tulisan loading

        // 1. Tombol Putar Kamera
        if (cameras.length > 1) {
            const btnTukar = document.createElement('button');
            btnTukar.className = 'bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1.5 px-3 rounded text-xs transition flex items-center gap-2 shadow-sm';
            btnTukar.innerHTML = '<i class="fa-solid fa-camera-rotate"></i> Putar';
            btnTukar.onclick = () => {
                let currentIndex = cameras.findIndex(c => c.id === currentCameraId);
                let nextIndex = (currentIndex + 1) % cameras.length;
                currentCameraId = cameras[nextIndex].id;
                
                if (isCameraRunning) {
                    html5QrCode.stop().then(() => startCamera(currentCameraId));
                } else {
                    startCamera(currentCameraId);
                    toggleCameraPowerUI(true);
                }
            };
            container.appendChild(btnTukar);
        }

        // 2. Tombol Matikan/Nyalakan Kamera
        const btnPower = document.createElement('button');
        btnPower.id = 'btn-power-kamera';
        btnPower.className = 'bg-red-100 hover:bg-red-200 text-red-700 font-bold py-1.5 px-3 rounded text-xs transition flex items-center gap-2 shadow-sm';
        btnPower.innerHTML = '<i class="fa-solid fa-video-slash"></i> Matikan';
        btnPower.onclick = toggleCameraPower;
        container.appendChild(btnPower);
    }

    // Fungsi Utama: Menghentikan & Memulai Kamera
    function toggleCameraPower() {
        if (isCameraRunning) {
            // Proses Mematikan
            html5QrCode.stop().then(() => {
                isCameraRunning = false;
                toggleCameraPowerUI(false);
            }).catch(err => console.error("Gagal mematikan kamera", err));
        } else {
            // Proses Menyalakan Kembali
            startCamera(currentCameraId);
            toggleCameraPowerUI(true);
        }
    }

    // Fungsi Bantuan: Mengubah warna dan teks tombol Power
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

    // Membangun Tombol Ganti Kamera (Sesuai Permintaan)
    // function buildCameraControls() {
    //     const container = document.getElementById('kamera-kontrol');
    //     container.innerHTML = ''; // Bersihkan loading

    //     if (cameras.length > 1) {
    //         const btn = document.createElement('button');
    //         btn.className = 'bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1.5 px-4 rounded text-xs transition flex items-center gap-2 shadow-sm';
    //         btn.innerHTML = '<i class="fa-solid fa-camera-rotate"></i> Putar Kamera';
    //         btn.onclick = () => {
    //             // Cari kamera berikutnya
    //             let currentIndex = cameras.findIndex(c => c.id === currentCameraId);
    //             let nextIndex = (currentIndex + 1) % cameras.length;
    //             currentCameraId = cameras[nextIndex].id;
                
    //             // Hentikan yang lama, mulai yang baru
    //             html5QrCode.stop().then(() => {
    //                 startCamera(currentCameraId);
    //             });
    //         };
    //         container.appendChild(btn);
    //     } else {
    //         container.innerHTML = '<span class="text-xs text-gray-500"><i class="fa-solid fa-camera"></i> Kamera Siap</span>';
    //     }
    // }

    function startCamera(cameraId) {
        if (!html5QrCode) html5QrCode = new Html5Qrcode("reader");
        
        html5QrCode.start(
            cameraId, 
            { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 },
            (decodedText) => handleScan(decodedText),
            (errorMessage) => { /* Abaikan error pembacaan per frame */ }
        ).then(() => {
            isCameraRunning = true; // Tandai kamera sedang hidup
        }).catch(err => console.error("Gagal memulai kamera:", err));
    }

    // Penanganan Scan QR
    function handleScan(decodedText) {
        if (isProcessing) return;
        isProcessing = true;
        
        // Membunyikan suara tangkapan pertama
        playBeep('success');

        processAttendance(decodedText);
    }

    // Penanganan Scan Manual
    function handleManualScan(e) {
        e.preventDefault();
        if (isProcessing) return;
        isProcessing = true;
        
        const nisn = document.getElementById('manualNisn').value;
        processAttendance(nisn);
        document.getElementById('manualNisn').value = '';
    }

    // Proses Pengiriman ke Server
    function processAttendance(nisn) {
        fetch('/absensi/scan', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ nisn: nisn })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Bunyi sukses
                playBeep('success');
                // Tampilkan pesan toast kecil (tidak mengganggu kamera)
                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                Toast.fire({ icon: 'success', title: data.student_name + ' Berhasil Hadir!' });
                
                // Segarkan tabel log
                refreshLog();
            } else {
                // Bunyi error (sudah absen/salah)
                playBeep('error');
                Swal.fire({ icon: 'error', title: 'Ditolak', text: data.message, timer: 2500, showConfirmButton: false });
            }

            // Kunci 2 detik untuk anti-spam
            setTimeout(() => { isProcessing = false; }, 2000);
        })
        .catch(error => {
            console.error("Error jaringan:", error);
            isProcessing = false;
        });
    }

    // Segarkan Log Tabel & Dropdown Status (Sesuai Permintaan)
    function refreshLog() {
        fetch('/absensi/log/{{ $activeSchedule->id }}')
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('logTableBody');
            tbody.innerHTML = '';
            
            document.getElementById('totalHadir').innerText = data.length + ' Hadir';

            if(data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="px-4 py-8 text-center text-gray-400 italic">Belum ada siswa yang absen.</td></tr>';
                return;
            }

            data.forEach(log => {
                let time = moment(log.timestamp).format('HH:mm:ss');
                
                // Dropdown untuk mengubah status (Sangat Penting - Item No. 8)
                let statusDropdown = `
                    <select onchange="updateStatus(${log.id}, this.value)" class="text-xs font-bold rounded-lg px-2 py-1 border ${log.status === 'hadir' ? 'bg-green-50 text-green-700 border-green-200' : (log.status === 'sakit' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200')} outline-none focus:ring-0">
                        <option value="hadir" ${log.status === 'hadir' ? 'selected' : ''}>HADIR</option>
                        <option value="sakit" ${log.status === 'sakit' ? 'selected' : ''}>SAKIT</option>
                        <option value="izin" ${log.status === 'izin' ? 'selected' : ''}>IZIN</option>
                        <option value="alpa" ${log.status === 'alpa' ? 'selected' : ''}>ALPA</option>
                    </select>
                `;

                let row = `
                    <tr class="border-b border-gray-50 last:border-0 hover:bg-blue-50 transition">
                        <td class="px-4 py-3 text-xs text-gray-500 font-mono">${time}</td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-gray-800">${log.student.user.name}</div>
                            <div class="text-[10px] text-gray-400">NISN: ${log.student.nisn}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            ${statusDropdown}
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', row);
            });
        });
    }

    // Kirim Perubahan Status dari Dropdown ke Server
    window.updateStatus = function(attendanceId, newStatus) {
        fetch('/absensi/update-status/' + attendanceId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const Toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 1500 });
                Toast.fire({ icon: 'success', title: 'Status diubah ke ' + newStatus.toUpperCase() });
                refreshLog(); // Segarkan warna dropdown
            }
        });
    }

    // Jalankan saat halaman dimuat
    window.onload = () => {
        initScanner();
        refreshLog();
        // Cek log baru setiap 10 detik otomatis
        setInterval(refreshLog, 10000); 
    };
</script>
@endif
@endpush