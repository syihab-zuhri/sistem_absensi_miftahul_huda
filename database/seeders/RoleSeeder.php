<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['admin', 'guru', 'siswa'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        if (! AcademicYear::exists()) {
            $start = now()->month >= 7 ? now()->year : now()->year - 1;
            AcademicYear::create(['name' => $start.'/'.($start + 1), 'start_date' => $start.'-07-01', 'end_date' => ($start + 1).'-06-30', 'is_active' => true]);
        }
    }
}
