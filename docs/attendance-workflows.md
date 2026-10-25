# Sesi presensi, koreksi, dan izin/sakit

Pengembangan ini menambahkan tiga prioritas: pertemuan bertanggal, jejak perubahan, dan pengajuan izin/sakit. Implementasi tetap menggunakan Laravel 12, Blade, Tailwind 3, serta PHP 8.2–8.4.

## Aturan produk

- Jadwal mingguan menjadi templat. Sesi menyimpan tanggal, jam, kelas, dan pengajar pertemuan; satu jadwal memiliki paling banyak satu sesi pada tanggal yang sama.
- Admin atau guru pemilik jadwal membuat sesi. Admin dapat menunjuk guru pengganti. Pengajar sesi atau admin membuka, menutup, dan melihat sesi tersebut.
- Sesi mempunyai status `planned`, `open`, `closed`, atau `cancelled`. Perubahan waktu hanya berlaku sebelum sesi dibuka. Pembatalan memerlukan alasan dan hanya berlaku sebelum pencatatan presensi.
- Daftar peserta dibekukan ketika sesi dibuka. Perubahan kelas siswa sesudahnya tidak mengubah peserta atau riwayat sesi.
- Pencatatan biasa hanya selama jam sesi pada tahun/semester aktif. Membuka sesi dilakukan pada hari pertemuan dan tidak melewati waktu selesai. Menutup sesi membutuhkan seluruh peserta tercatat; pengisian alpa untuk peserta yang belum tercatat harus dipilih secara eksplisit. Permohonan yang masih menunggu keputusan menghalangi pengisian alpa otomatis.
- Koreksi setelah jam pertemuan memerlukan status sebelumnya dan alasan minimal 10 karakter. Koreksi dilakukan pengajar sesi atau admin. Status sebelumnya yang sudah berubah ditolak agar data dari halaman lama tidak menimpa keputusan terbaru.
- Setiap pencatatan/perubahan status dan tindakan siklus sesi menyimpan pelaku, waktu, alasan, dan nilai sebelumnya/sesudahnya. Catatan lama tetap dibaca melalui laporan yang ada. Impor riwayat tidak mengarang identitas pencatat lama.
- Siswa aktif mengajukan `izin`/`sakit` untuk sesi kelasnya, dengan alasan minimal 10 karakter dan lampiran opsional PDF/JPEG/PNG maksimal 5 MB. Sesi yang dibatalkan tidak menerima permohonan. Lampiran berada pada penyimpanan privat dan hanya dapat diunduh pemilik permohonan atau pengajar sesi/admin.
- Hanya satu permohonan `pending`/`approved` per siswa/sesi. Permohonan ditolak/dibatalkan tetap tersimpan dan siswa boleh mengajukan kembali. Siswa hanya membatalkan permohonan yang masih menunggu.
- Jika siswa berpindah kelas atau tidak aktif sebelum sesi dibuka, persetujuan mendatang dibatalkan dengan alasan dan riwayat; presensi tidak dibuat untuk siswa yang bukan peserta.
- Pengajar sesi/admin memutuskan permohonan dengan catatan keputusan. Persetujuan mengubah presensi menjadi jenis permohonan secara transaksional dan tercatat dalam audit. Persetujuan sesi mendatang diterapkan ketika sesi dibuka. Kehadiran `hadir` yang sudah tercatat tidak ditimpa oleh persetujuan izin/sakit.
- Laporan guru mencakup sesi yang diajarnya, termasuk ketika menjadi pengganti; data guru lain tidak ditampilkan.
- Jadwal, siswa, pengguna, dan presensi yang terhubung riwayat baru dilindungi dari penghapusan melalui aplikasi.

## Batas pengembangan

Dashboard BK, portal orang tua, notifikasi eksternal, presensi gerbang, mode offline, migrasi ID kelas, dan peningkatan PHP server merupakan tahap berikutnya. Kalender pertemuan diwakili daftar sesi bertanggal, perubahan waktu, dan pembatalan dengan alasan. Server publik tidak menjadi sasaran deployment otomatis.
