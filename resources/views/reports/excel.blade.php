<table>
    <tr>
        <td colspan="7" style="text-align: center; font-weight: bold; font-size: 14px;">LAPORAN REKAPITULASI ABSENSI SISWA</td>
    </tr>
    <tr>
        <td colspan="7" style="text-align: center;">Periode: {{ $dateRange }}</td>
    </tr>
    <tr>
        <td colspan="7" style="text-align: center;">Kelas: {{ $className }} | Mata Pelajaran: {{ $subjectName }}</td>
    </tr>
    <tr>
        <td colspan="7"></td>
    </tr>
    <tr>
        <th style="background-color: #d9d9d9; font-weight: bold; text-align: center; border: 1px solid #000;">No</th>
        <th style="background-color: #d9d9d9; font-weight: bold; text-align: center; border: 1px solid #000;">Waktu Pemindaian</th>
        <th style="background-color: #d9d9d9; font-weight: bold; text-align: center; border: 1px solid #000;">NISN</th>
        <th style="background-color: #d9d9d9; font-weight: bold; text-align: center; border: 1px solid #000;">Nama Siswa</th>
        <th style="background-color: #d9d9d9; font-weight: bold; text-align: center; border: 1px solid #000;">Kelas</th>
        <th style="background-color: #d9d9d9; font-weight: bold; text-align: center; border: 1px solid #000;">Mata Pelajaran</th>
        <th style="background-color: #d9d9d9; font-weight: bold; text-align: center; border: 1px solid #000;">Status</th>
    </tr>
    @foreach($attendances as $index => $attendance)
    <tr>
        <td style="text-align: center; border: 1px solid #000;">{{ $index + 1 }}</td>
        <td style="text-align: center; border: 1px solid #000;">{{ \Carbon\Carbon::parse($attendance->timestamp)->format('d-m-Y H:i:s') }}</td>
        <td style="text-align: center; border: 1px solid #000;">{{ $attendance->student->nisn ?? '-' }}</td>
        <td style="border: 1px solid #000;">{{ $attendance->student->user->name ?? 'Siswa Terhapus' }}</td>
        <td style="text-align: center; border: 1px solid #000;">{{ $attendance->schedule->class_id ?? '-' }}</td>
        <td style="text-align: center; border: 1px solid #000;">{{ $attendance->schedule->subject->name ?? 'Mapel Terhapus' }}</td>
        <td style="text-align: center; border: 1px solid #000;">{{ ucfirst($attendance->status) }}</td>
    </tr>
    @endforeach
    <tr>
        <td colspan="7"></td>
    </tr>
    <tr>
        <td colspan="5"></td>
        <td colspan="2" style="text-align: center;">Dicetak pada: {{ $printDate }}</td>
    </tr>
    <tr>
        <td colspan="5"></td>
        <td colspan="2" style="text-align: center;">Absensi dicetak oleh / tertanda,</td>
    </tr>
    <tr>
        <td colspan="7"></td>
    </tr>
    <tr>
        <td colspan="7"></td>
    </tr>
    <tr>
        <td colspan="7"></td>
    </tr>
    <tr>
        <td colspan="5"></td>
        <td colspan="2" style="text-align: center; font-weight: bold;">( {{ $printerName }} )</td>
    </tr>
</table>
