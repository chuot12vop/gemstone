<?php

namespace App\Support;

use Closure;

class PhoneValidation
{
    public static function digitCount(string $phone): int
    {
        return strlen(preg_replace('/\D/', '', $phone) ?? '');
    }

    public static function hasMinDigits(string $phone, int $min = 9): bool
    {
        return self::digitCount($phone) >= $min;
    }

    /**
     * @return list<string|Closure>
     */
    public static function rules(int $minDigits = 9, int $maxLength = 40): array
    {
        return [
            'required',
            'string',
            'max:'.$maxLength,
            function (string $attribute, mixed $value, Closure $fail) use ($minDigits): void {
                if (! self::hasMinDigits((string) $value, $minDigits)) {
                    $fail(__('validation.min_digits', [
                        'attribute' => str_replace('_', ' ', $attribute),
                        'min' => $minDigits,
                    ]));
                }
            },
        ];
    }
}
