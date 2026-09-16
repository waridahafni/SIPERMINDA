<?php

namespace App\Rules;

use App\Support\NomorTeleponIndonesia;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

final class NomorHpIndonesia implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Nomor HP harus berupa teks.');

            return;
        }

        try {
            NomorTeleponIndonesia::kanonis($value);
        } catch (InvalidArgumentException) {
            $fail('Nomor HP Indonesia tidak valid. Gunakan format 08..., 628..., atau +628....');
        }
    }
}
