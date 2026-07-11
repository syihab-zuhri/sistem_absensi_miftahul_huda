<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Siswa - {{ $student->nisn }}</title>
    <style>
        @page {
            margin: 40px 60px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            background: #ffffff;
            color: #000000;
            width: 100%;
            height: 100%;
        }

        .card {
            width: 100%;
            min-height: 460px;
            border: 3px solid #000000;
            border-radius: 14px;
            padding: 40px 30px;
            text-align: center;
            vertical-align: middle;
        }

        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 30px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .header p {
            font-size: 16px;
            color: #555555;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .divider {
            width: 120px;
            height: 3px;
            background: #000000;
            margin: 0 auto 30px auto;
        }

        .student-info {
            margin-bottom: 30px;
        }

        .student-info .name {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .student-info .nisn {
            font-size: 24px;
            color: #333333;
            letter-spacing: 2px;
        }

        .qr-box {
            display: inline-block;
            padding: 12px;
            border: 2px solid #dddddd;
            border-radius: 10px;
            background: #ffffff;
        }

        .qr-box img {
            width: 200px;
            height: 200px;
            display: block;
        }

        .qr-empty {
            width: 200px;
            height: 200px;
            border: 2px dashed #ff0000;
            color: #ff0000;
            font-size: 16px;
            font-weight: bold;
            display: table-cell;
            text-align: center;
            vertical-align: middle;
        }

        .footer {
            margin-top: 25px;
            font-size: 12px;
            color: #888888;
        }

        .footer .print-date {
            margin-top: 4px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>KARTU QR SISWA</h1>
            <p>Sistem Absensi</p>
        </div>

        <div class="divider"></div>

        <div class="student-info">
            <div class="name">{{ $student->user->name ?? 'Siswa Terhapus' }}</div>
            <div class="nisn">{{ $student->nisn }}</div>
        </div>

        <div class="qr-box">
            @if($qrBase64)
                <img src="{{ $qrBase64 }}" alt="QR Code {{ $student->nisn }}">
            @else
                <div class="qr-empty">QR TIDAK TERSEDIA</div>
            @endif
        </div>

        <div class="footer">
            <p>Gunakan QR Code ini untuk melakukan presensi harian.</p>
            <div class="print-date">Dicetak pada: {{ $printDate }}</div>
        </div>
    </div>
</body>
</html>