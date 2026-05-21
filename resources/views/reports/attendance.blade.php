<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; color: #666; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 3px; }
        .info-table .label { font-weight: bold; width: 120px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .data-table th { background-color: #f4f4f4; font-weight: bold; text-transform: uppercase; font-size: 11px; }
        .status-badge { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; color: #fff; text-transform: uppercase; }
        .bg-hadir { background-color: #10b981; }
        .footer { margin-top: 40px; text-align: right; font-size: 11px; }
        .signature { margin-top: 60px; font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Laporan Kehadiran Siswa</h2>
        <p>Sistem Absensi Berbasis QR Code</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Mata Pelajaran</td>
            <td>: {{ $schedule->subject->name }}</td>
            <td class="label">Tanggal Cetak</td>
            <td>: {{ $date }}</td>
        </tr>
        <tr>
            <td class="label">Kelas</td>
            <td>: {{ $schedule->class_id }}</td>
            <td class="label">Hari / Waktu</td>
            <td>: {{ $schedule->day }} ({{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }})</td>
        </tr>
        <tr>
            <td class="label">Guru Pengajar</td>
            <td>: {{ $schedule->teacher->name ?? 'Tidak Terdaftar' }}</td>
            <td class="label">Total Hadir</td>
            <td>: {{ $attendances->count() }} Siswa</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">NISN</th>
                <th width="40%">Nama Siswa</th>
                <th width="20%">Waktu Pemindaian</th>
                <th width="15%" style="text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $index => $attendance)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $attendance->student->nisn ?? '-' }}</td>
                <td>{{ $attendance->student->user->name ?? 'User Terhapus' }}</td>
                <td>{{ \Carbon\Carbon::parse($attendance->timestamp)->format('H:i:s') }}</td>
                <td style="text-align: center;">
                    <span class="status-badge bg-hadir">Hadir</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 20px;">Belum ada data kehadiran pada sesi ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Mengetahui,</p>
        <div class="signature">{{ $schedule->teacher->name ?? 'Guru Mata Pelajaran' }}</div>
    </div>

</body>
</html>