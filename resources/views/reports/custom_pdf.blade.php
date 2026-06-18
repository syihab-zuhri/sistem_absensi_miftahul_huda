<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi ({{ $dateRange }})</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; color: #666; font-size: 12px; }
        .info-table { width: 100%; margin-bottom: 20px; font-size: 11px; }
        .info-table td { padding: 3px; }
        .info-table .label { font-weight: bold; width: 100px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #999; padding: 6px; text-align: left; }
        .data-table th { background-color: #f4f4f4; font-weight: bold; text-transform: uppercase; font-size: 10px; text-align: center; }
        .status-badge { font-weight: bold; text-transform: uppercase; }
        .footer { margin-top: 40px; text-align: right; font-size: 11px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Rekapitulasi Kehadiran Siswa</h2>
        <p>Sistem Absensi Berbasis QR Code</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Rentang Waktu</td>
            <td>: <strong>{{ $dateRange }}</strong></td>
            <td class="label">Waktu Cetak</td>
            <td>: {{ $printDate }}</td>
        </tr>
        <tr>
            <td class="label">Mata Pelajaran</td>
            <td>: {{ $subjectName }}</td>
            <td class="label">Kelas Filter</td>
            <td>: {{ $className }}</td>
        </tr>
        <tr>
            <td class="label">Total Kehadiran</td>
            <td>: {{ $attendances->count() }} Data</td>
            <td class="label">Dicetak Oleh</td>
            <td>: Administrator / Guru</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Waktu Absen</th>
                <th width="15%">NISN</th>
                <th width="25%">Nama Siswa</th>
                <th width="10%">Kelas</th>
                <th width="20%">Mata Pelajaran</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $index => $attendance)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td style="text-align: center;">{{ \Carbon\Carbon::parse($attendance->timestamp)->format('d/m/Y H:i') }}</td>
                <td style="text-align: center;">{{ $attendance->student->nisn ?? '-' }}</td>
                <td>{{ $attendance->student->user->name ?? 'User Terhapus' }}</td>
                <td style="text-align: center;">{{ $attendance->schedule->class_id ?? '-' }}</td>
                <td>{{ $attendance->schedule->subject->name ?? 'Mapel Terhapus' }}</td>
                <td style="text-align: center;" class="status-badge">{{ $attendance->status }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">Tidak ditemukan catatan kehadiran yang sesuai dengan filter.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Dokumen ini dicetak secara otomatis oleh sistem.</p>
    </div>

</body>
</html>