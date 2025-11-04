<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Casts;

use event4u\DataHelpers\SimpleDto\Contracts\CastsAttributes;

/**
 * Cast attribute to nested Dto.
 *
 * Automatically converts arrays to Dto instances.
 *
 * Example:
 *   protected function casts(): array {
 *       return [
 *           'address' => 'dto:' . AddressDto::class,
 *       ];
 *   }
 */
class DtoCast implements CastsAttributes
{
    /** @param string $dtoClass The Dto class to cast to */
    public function __construct(
        private readonly string $dtoClass,
    ) {}

    /**
     * Cast the given value to a Dto instance.
     *
     * @param array<string, mixed> $attributes
     */
    public function get(mixed $value, array $attributes): mixed
    {
        if (null === $value) {
            return null;
        }

        // If already a Dto instance, return as-is
        if ($value instanceof $this->dtoClass) {
            return $value;
        }

        // If it's an array, convert to Dto
        if (is_array($value)) {
            return $this->dtoClass::fromArray($value);
        }

        return $value;
    }

    /**
     * Cast the given value for storage (toArray).
     *
     * @param array<string, mixed> $attributes
     */
    public function set(mixed $value, array $attributes): mixed
    {
        if (null === $value) {
            return null;
        }

        // If it's a Dto, convert to array
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        return $value;
    }
}
