<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas Pencatatan Presensi
    |--------------------------------------------------------------------------
    |
    | Presensi dapat disimpan tanpa alasan sampai sekian menit setelah jam
    | pertemuan selesai. Sesudahnya pencatatan tetap tersedia sebagai
    | pencatatan susulan yang wajib disertai alasan dan tercatat di riwayat.
    |
    */

    'grace_minutes' => (int) env('ATTENDANCE_GRACE_MINUTES', 60),

];
