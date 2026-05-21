<?php

namespace Database\Seeders;

// use SimpleSoftwareIO\QrCode\Facades\QrCode;
// use Illuminate\Support\Facades\Storage;
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
        \App\Models\Student::updateOrCreate(
            ['nisn' => '1234567890'],
            [
                'user_id' => $siswaUser->id,
                'class_id' => '10-A',
                'qr_code_path' => 'qrcodes/1234567890.svg' // Pastikan ekstensinya sesuai dengan yang digenerate (png/svg)
            ]
        );
        
        $this->command->info('Role berhasil dibuat dan Akun Admin telah ditambahkan!');
    }
}