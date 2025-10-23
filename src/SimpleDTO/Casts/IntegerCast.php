<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Casts;

use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;

/**
 * Cast attribute to integer.
 *
 * Supports:
 * - Strings (numeric)
 * - Floats (rounded)
 * - Integers
 *
 * Example:
 *   protected function casts(): array {
 *       return ['quantity' => IntegerCast::class];
 *   }
 */
class IntegerCast implements CastsAttributes
{
    public function get(mixed $value, array $attributes): ?int
    {
        if (null === $value) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value;
        }

        return null;
    }

    public function set(mixed $value, array $attributes): ?int
    {
        if (null === $value) {
            return null;
        }

        return is_numeric($value) ? (int)$value : null;
    }
}
