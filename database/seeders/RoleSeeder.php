<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Membuat Role jika belum ada
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleGuru = Role::firstOrCreate(['name' => 'guru']);
        $roleSiswa = Role::firstOrCreate(['name' => 'siswa']);

        // Membuat akun Admin Default
        $admin = User::firstOrCreate(
            ['email' => 'admin@sekolah.com'], // Patokan agar tidak ganda
            [
                'name' => 'Administrator',
                'password' => Hash::make('password123'), // Password default
            ]
        );

        // Memberikan role admin kepada user yang baru dibuat
        $admin->assignRole($roleAdmin);


        // Membuat Akun Siswa Simulasi
        $siswaUser = User::firstOrCreate(
            ['email' => 'siswa@sekolah.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('siswa123'),
            ]
        );
        $siswaUser->assignRole($roleSiswa);

        // Gunakan updateOrCreate berdasarkan 'nisn' agar tidak terjadi duplikat
        $student = \App\Models\Student::updateOrCreate(
            ['nisn' => '1234567890'],
            [
                'user_id' => $siswaUser->id,
                'class_id' => '10-A',
            ]
        );

        // Buat QR Code secara nyata untuk mencegah broken image
        if (!Storage::disk('public')->exists('qrcodes')) {
            Storage::disk('public')->makeDirectory('qrcodes');
        }

        $qr = QrCode::format('svg')->size(300)->margin(2)->generate($student->nisn);
        $path = 'qrcodes/' . $student->nisn . '.svg';
        Storage::disk('public')->put($path, $qr);
        $student->update(['qr_code_path' => $path]);

        $this->command->info('Role berhasil dibuat dan Akun Admin telah ditambahkan!');
    }
}