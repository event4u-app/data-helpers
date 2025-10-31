<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Casts;

use event4u\DataHelpers\SimpleDto\Contracts\CastsAttributes;

/**
 * Cast attribute to float.
 *
 * Supports:
 * - Strings (numeric)
 * - Integers
 * - Floats
 * - Booleans (true=1.0, false=0.0)
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

        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
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

        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        return is_numeric($value) ? (float)$value : null;
    }
}
