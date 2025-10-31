<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Casts;

use event4u\DataHelpers\SimpleDto\Contracts\CastsAttributes;

/**
 * Cast attribute to boolean.
 *
 * Supports:
 * - Integers (0=false, any non-zero=true)
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
            return 0 !== $value;
        }

        if (is_float($value)) {
            return 0.0 !== $value;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));

            // Only cast known boolean strings
            if (in_array($lower, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($lower, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }

            // Unknown string - return null to trigger TypeError
            return null;
        }

        // Other types - return null to trigger TypeError
        return null;
    }

    public function set(mixed $value, array $attributes): ?int
    {
        if (null === $value) {
            return null;
        }

        return $value ? 1 : 0;
    }
}
