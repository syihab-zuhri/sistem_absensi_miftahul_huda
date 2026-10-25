import { Html5Qrcode } from 'html5-qrcode';
import { attendanceChanges, colorStatus, renderStudents } from './scanner-table';

export function initializeScanner(container) {
    const tbody = document.getElementById('studentTableBody');
    const saveButton = document.getElementById('btnSaveSession');
    const printButton = document.getElementById('btnPrintPdf');
    const correctionReason = document.getElementById('correction-reason');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    let selects = new Map();
    let unsaved = false;
    let loaded = false;
    let saving = false;
    let processing = false;
    let camera = null;
    let cameraRunning = false;
    let devices = [];
    let cameraIndex = 0;
    let audio;

    function showMessage(message) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 3;
        cell.className = 'p-6 text-center text-gray-700';
        cell.textContent = message;
        row.append(cell);
        tbody.replaceChildren(row);
    }

    function markUnsaved(select) {
        colorStatus(select);
        unsaved = attendanceChanges(selects).length > 0;
        saveButton.disabled = !unsaved;
        saveButton.textContent = unsaved ? 'Simpan Perubahan' : 'Sesi Tersimpan';
    }

    async function jsonRequest(url, options = {}) {
        const response = await fetch(url, { ...options, headers: { Accept: 'application/json', ...options.headers } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const errors = data.errors ? Object.values(data.errors).flat().join('\n') : '';
            throw new Error(errors || data.message || `Permintaan gagal (${response.status}). Muat ulang halaman dan periksa sesi login.`);
        }
        return data;
    }

    async function load() {
        try {
            const data = await jsonRequest(container.dataset.sessionUrl);
            if (!Array.isArray(data)) throw new Error('Daftar siswa tidak dapat dibaca.');
            selects = renderStudents(tbody, data, markUnsaved);
            loaded = true;
            if (data.length === 0) showMessage('Tidak ada siswa aktif di kelas ini.');
        } catch (error) {
            showMessage(error.message);
        }
    }

    saveButton.addEventListener('click', async () => {
        if (!unsaved || saving) return;
        const attendances = attendanceChanges(selects);
        if (!attendances.length) return Swal.fire({ icon: 'info', titleText: 'Belum ada data', text: 'Pilih status kehadiran siswa sebelum menyimpan.' });
        const reason = correctionReason.value.trim();
        if (attendances.some(row => row.expected_status !== null) && reason.length < 10) {
            correctionReason.focus();
            return Swal.fire({ icon: 'warning', titleText: 'Alasan koreksi diperlukan', text: 'Isi alasan minimal 10 karakter untuk mengubah status yang sudah tersimpan.' });
        }
        saving = true;
        saveButton.disabled = true;
        for (const select of selects.values()) select.disabled = true;
        Swal.fire({ titleText: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const result = await jsonRequest(container.dataset.saveUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ attendances, reason: reason || null }) });
            if (!result.success) throw new Error(result.message || 'Absensi belum tersimpan.');
            unsaved = false;
            correctionReason.value = '';
            saveButton.textContent = 'Sesi Tersimpan';
            await load();
            Swal.fire({ icon: 'success', titleText: 'Tersimpan', text: result.message });
        } catch (error) {
            Swal.fire({ icon: 'error', titleText: 'Absensi belum tersimpan', text: error.message });
        } finally {
            saving = false;
            saveButton.disabled = !unsaved;
            for (const select of selects.values()) select.disabled = false;
        }
    });

    function beep() {
        try {
            const Audio = window.AudioContext || window.webkitAudioContext;
            if (!Audio) return;
            audio ||= new Audio();
            const oscillator = audio.createOscillator();
            const gain = audio.createGain();
            oscillator.frequency.value = 1000;
            gain.gain.value = 0.05;
            oscillator.connect(gain);
            gain.connect(audio.destination);
            oscillator.start();
            oscillator.stop(audio.currentTime + 0.12);
        } catch { /* Audio support must not prevent recording attendance. */ }
    }

    function handleScan(text) {
        if (processing || saving || !loaded) return;
        processing = true;
        try {
            const select = selects.get(String(text).trim());
            if (!select) {
                Swal.fire({ icon: 'error', titleText: 'QR tidak terdaftar', text: 'Siswa tidak terdaftar sebagai siswa aktif di kelas ini.', timer: 2000, showConfirmButton: false });
                return;
            }
            if (select.value !== 'hadir') { select.value = 'hadir'; markUnsaved(select); }
            beep();
            const name = select.closest('tr').querySelector('.student-name-text').textContent;
            Swal.fire({ toast: true, position: 'top-start', icon: 'success', titleText: `${name} berstatus Hadir`, timer: 1800, showConfirmButton: false });
        } finally {
            setTimeout(() => { processing = false; }, 1500);
        }
    }

    const cameraInfo = document.getElementById('kamera-info');
    const controls = document.getElementById('kamera-kontrol');
    const power = document.createElement('button');
    power.type = 'button';
    power.className = 'min-h-11 bg-blue-600 text-white rounded-lg px-4 py-2 font-semibold text-sm';
    power.textContent = 'Aktifkan Kamera';
    controls.append(power);
    cameraInfo.textContent = 'Kamera belum diaktifkan. Absensi manual tersedia.';

    async function startCamera() {
        camera ||= new Html5Qrcode('reader');
        await camera.start(devices[cameraIndex].id, { fps: 10, qrbox: (width, height) => ({ width: Math.floor(Math.min(width, height, 320) * 0.7), height: Math.floor(Math.min(width, height, 320) * 0.7) }) }, handleScan, () => {});
        cameraRunning = true;
        cameraInfo.textContent = 'Kamera aktif.';
        power.textContent = 'Matikan Kamera';
    }

    power.addEventListener('click', async () => {
        power.disabled = true;
        try {
            if (cameraRunning) {
                await camera.stop(); cameraRunning = false;
                power.textContent = 'Aktifkan Kamera'; cameraInfo.textContent = 'Kamera dimatikan.';
            } else {
                if (!devices.length) {
                    devices = await Html5Qrcode.getCameras();
                    if (!devices.length) throw new Error('Kamera tidak ditemukan.');
                    const back = devices.findIndex(device => /back|environment|belakang/i.test(device.label));
                    cameraIndex = back >= 0 ? back : 0;
                    if (devices.length > 1) {
                        const switchButton = document.createElement('button');
                        switchButton.type = 'button'; switchButton.textContent = 'Ganti Kamera';
                        switchButton.className = 'min-h-11 rounded-lg bg-gray-200 text-gray-800 px-4 py-2 text-sm';
                        switchButton.addEventListener('click', async () => {
                            switchButton.disabled = true;
                            try {
                                if (cameraRunning) { await camera.stop(); cameraRunning = false; }
                                cameraIndex = (cameraIndex + 1) % devices.length;
                                await startCamera();
                            } catch { cameraInfo.textContent = 'Kamera tidak dapat diganti. Coba aktifkan kembali.'; }
                            finally { switchButton.disabled = false; }
                        });
                        controls.append(switchButton);
                    }
                }
                await startCamera();
            }
        } catch {
            cameraInfo.textContent = 'Kamera tidak tersedia atau izin ditolak. Periksa izin kamera dan gunakan HTTPS. Absensi manual tetap tersedia.';
        } finally { power.disabled = false; }
    });

    window.addEventListener('beforeunload', event => {
        if (unsaved) { event.preventDefault(); event.returnValue = ''; }
    });
    window.addEventListener('pagehide', () => { if (cameraRunning) camera.stop().catch(() => {}); audio?.close().catch(() => {}); });
    printButton.addEventListener('click', event => {
        if (unsaved || saving) {
            event.preventDefault();
            Swal.fire({ icon: 'warning', titleText: 'Simpan perubahan terlebih dahulu', text: 'Laporan menggunakan absensi yang sudah tersimpan.' });
        }
    });
    load();
}
