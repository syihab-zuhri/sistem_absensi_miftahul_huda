<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Rules\AvailableLoginEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateSchoolAdmin extends Command
{
    protected $signature = 'school:create-admin';

    protected $description = 'Membuat administrator tanpa akun atau sandi bawaan';

    public function handle(): int
    {
        $data = ['name' => $this->ask('Nama administrator'), 'email' => mb_strtolower(trim((string) $this->ask('Email administrator'))), 'password' => $this->secret('Sandi awal (minimal 12 karakter)')];
        $validator = Validator::make($data, ['name' => 'required|string|max:255', 'email' => ['required', 'email', 'max:255', new AvailableLoginEmail], 'password' => 'required|string|min:12|max:255']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        DB::transaction(function () use ($data) {
            Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            User::create(array_merge($data, ['must_change_password' => true]))->assignRole('admin');
        });
        $this->info('Administrator dibuat. Sandi awal wajib diganti setelah login.');

        return self::SUCCESS;
    }
}
