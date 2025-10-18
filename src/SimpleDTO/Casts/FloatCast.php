<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Casts;

use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;

/**
 * Cast attribute to float.
 *
 * Supports:
 * - Strings (numeric)
 * - Integers
 * - Floats
 *
 * Example:
 *   protected function casts(): array {
 *       return ['price' => FloatCast::class];
 *   }
 */
class FloatCast implements CastsAttributes
{
    public function get(mixed $value, array $attributes): ?float
    {
        if (null === $value) {
            return null;
        }

        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float)$value;
        }

        return null;
    }

    public function set(mixed $value, array $attributes): ?float
    {
        if (null === $value) {
            return null;
        }

        return is_numeric($value) ? (float)$value : null;
    }
}

