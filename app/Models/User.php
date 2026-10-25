<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable; // <-- Tambahkan HasRoles di sini

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email', // Kita biarkan email untuk bawaan Breeze, meski nanti bisa disesuaikan pakai username/NISN
        'password',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->student?->personal_email ?: $this->email;
    }

    public function routeNotificationForMail($notification): string
    {
        return $this->getEmailForPasswordReset();
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }
}
