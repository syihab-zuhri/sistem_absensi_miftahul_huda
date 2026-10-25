<?php

namespace App\Rules;

use App\Services\LoginIdentity;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AvailableLoginEmail implements ValidationRule
{
    public function __construct(private ?int $ignoreUserId = null, private ?int $ignoreStudentId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && ! LoginIdentity::emailAvailable($value, $this->ignoreUserId, $this->ignoreStudentId)) {
            $fail('Alamat email sudah digunakan untuk login akun lain.');
        }
    }
}
