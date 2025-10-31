<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Casts;

use event4u\DataHelpers\SimpleDto\Contracts\CastsAttributes;

/**
 * Cast attribute to string.
 *
 * Supports:
 * - Integers
 * - Floats
 * - Booleans (true → '1', false → '0')
 * - Strings
 * - Objects with __toString()
 *
 * Example:
 *   protected function casts(): array {
 *       return ['description' => StringCast::class];
 *   }
 */
class StringCast implements CastsAttributes
{
    public function get(mixed $value, array $attributes): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string)$value;
        }

        return null;
    }

    public function set(mixed $value, array $attributes): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        return null;
    }
}
