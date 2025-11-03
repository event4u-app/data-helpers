<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Casts;

use event4u\DataHelpers\SimpleDto\Contracts\CastsAttributes;

/**
 * Cast attribute to array.
 *
 * Supports:
 * - JSON strings
 * - Arrays
 * - Objects (converted to array)
 *
 * Example:
 *   protected function casts(): array {
 *       return ['options' => ArrayCast::class];
 *   }
 */
class ArrayCast implements CastsAttributes
{
    /** @return array<array-key, mixed>|null */
    public function get(mixed $value, array $attributes): ?array
    {
        if (null === $value) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
                return $decoded;
            }
            // Invalid JSON - return null to trigger TypeError
            return null;
        }

        if (is_object($value)) {
            return (array)$value;
        }

        // Other types (int, float, bool, etc.) - return null to trigger TypeError
        return null;
    }

    public function set(mixed $value, array $attributes): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_array($value)) {
            $encoded = json_encode($value);

            return false !== $encoded ? $encoded : null;
        }

        return is_string($value) ? $value : null;
    }
}
