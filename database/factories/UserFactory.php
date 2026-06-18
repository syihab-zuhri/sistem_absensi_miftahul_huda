<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Configure the factory to assign a role after creation.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            // Pastikan role sudah ada (untuk testing)
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);

            // Assign default role jika user belum punya role sama sekali
            if (! $user->roles()->exists()) {
                $user->assignRole('siswa');
            }
        });
    }
}
