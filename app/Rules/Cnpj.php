<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida CNPJ pelos digitos verificadores, nao apenas pelo formato.
 * Aceita com ou sem mascara.
 */
class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits)) {
            $fail('O CNPJ informado não é válido.');

            return;
        }

        foreach ([12, 13] as $position) {
            if ((int) $digits[$position] !== self::checkDigit($digits, $position)) {
                $fail('O CNPJ informado não é válido.');

                return;
            }
        }
    }

    private static function checkDigit(string $digits, int $position): int
    {
        $weight = $position === 12 ? 5 : 6;
        $sum = 0;

        for ($i = 0; $i < $position; $i++) {
            $sum += ((int) $digits[$i]) * $weight;
            $weight = $weight === 2 ? 9 : $weight - 1;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    /**
     * Normaliza para o formato 00.000.000/0000-00 usado no banco.
     */
    public static function format(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) !== 14) {
            return $value ?: null;
        }

        return vsprintf('%s%s.%s%s%s.%s%s%s/%s%s%s%s-%s%s', str_split($digits));
    }
}
