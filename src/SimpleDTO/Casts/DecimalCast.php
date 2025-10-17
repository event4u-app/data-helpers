<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Casts;

use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;

/**
 * Cast attribute to decimal string with fixed precision.
 *
 * This cast is useful for monetary values where you need exact precision.
 * Returns a string to avoid floating-point precision issues.
 *
 * Example:
 *   protected function casts(): array {
 *       return [
 *           'price' => DecimalCast::class . ':2',  // 2 decimal places
 *           'tax_rate' => 'decimal:4',             // 4 decimal places
 *       ];
 *   }
 */
class DecimalCast implements CastsAttributes
{
    private readonly int $precision;

    public function __construct(
        int|string $precision = 2,
    ) {
        $this->precision = is_string($precision) ? (int)$precision : $precision;
    }

    public function get(mixed $value, array $attributes): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_numeric($value)) {
            return number_format((float)$value, $this->precision, '.', '');
        }

        return null;
    }

    public function set(mixed $value, array $attributes): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_numeric($value)) {
            return number_format((float)$value, $this->precision, '.', '');
        }

        return null;
    }
}

