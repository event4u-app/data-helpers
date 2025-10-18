<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Casts;

use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;

/**
 * Cast attribute to/from JSON.
 *
 * Decodes JSON strings to arrays/objects on get,
 * encodes arrays/objects to JSON strings on set.
 *
 * Example:
 *   protected function casts(): array {
 *       return ['metadata' => JsonCast::class];
 *   }
 */
class JsonCast implements CastsAttributes
{
    public function __construct(
        private readonly bool $assoc = true,
        private readonly int $flags = 0,
    ) {}

    /** @return array<array-key, mixed>|object|null */
    public function get(mixed $value, array $attributes): array|object|null
    {
        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, $this->assoc, 512, $this->flags);
            if (JSON_ERROR_NONE === json_last_error() && (is_array($decoded) || is_object($decoded))) {
                return $decoded;
            }
        }

        if (is_array($value) || is_object($value)) {
            return $value;
        }

        return null;
    }

    public function set(mixed $value, array $attributes): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            // Validate it's valid JSON
            json_decode($value);
            if (JSON_ERROR_NONE === json_last_error()) {
                return $value;
            }
        }

        if (is_array($value) || is_object($value)) {
            $encoded = json_encode($value, $this->flags);

            return false !== $encoded ? $encoded : null;
        }

        return null;
    }
}

