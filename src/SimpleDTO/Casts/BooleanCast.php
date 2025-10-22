<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Casts;

use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;

/**
 * Cast attribute to boolean.
 *
 * Supports:
 * - Integers (0, 1)
 * - Strings ('0', '1', 'true', 'false', 'yes', 'no', 'on', 'off')
 * - Booleans
 *
 * Example:
 *   protected function casts(): array {
 *       return ['is_active' => BooleanCast::class];
 *   }
 */
class BooleanCast implements CastsAttributes
{
    public function get(mixed $value, array $attributes): ?bool
    {
        if (null === $value) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return 1 === $value;
        }

        if (is_string($value)) {
            $lower = strtolower($value);

            return in_array($lower, ['1', 'true', 'yes', 'on'], true);
        }

        return (bool)$value;
    }

    public function set(mixed $value, array $attributes): ?int
    {
        if (null === $value) {
            return null;
        }

        return $value ? 1 : 0;
    }
}
