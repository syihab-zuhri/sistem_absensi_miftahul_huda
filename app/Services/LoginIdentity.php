<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;

class LoginIdentity
{
    public static function emailAvailable(string $email, ?int $ignoreUserId = null, ?int $ignoreStudentId = null): bool
    {
        $email = mb_strtolower(trim($email));

        return ! User::whereRaw('LOWER(email) = ?', [$email])->when($ignoreUserId, fn ($q) => $q->whereKeyNot($ignoreUserId))->exists()
            && ! Student::whereRaw('LOWER(personal_email) = ?', [$email])->when($ignoreStudentId, fn ($q) => $q->whereKeyNot($ignoreStudentId))->exists();
    }

    public static function resolve(string $identifier): ?User
    {
        $identifier = mb_strtolower(trim($identifier));
        if (! filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return Student::with('user')->where('nisn', $identifier)->first()?->user;
        }
        $user = User::whereRaw('LOWER(email) = ?', [$identifier])->first();
        $studentUser = Student::with('user')->whereRaw('LOWER(personal_email) = ?', [$identifier])->first()?->user;
        if ($user && $studentUser && $user->id !== $studentUser->id) {
            return null;
        }

        return $user ?? $studentUser;
    }
}
