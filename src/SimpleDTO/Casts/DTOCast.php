<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Casts;

use event4u\DataHelpers\SimpleDTO\Contracts\CastsAttributes;

/**
 * Cast attribute to nested DTO.
 *
 * Automatically converts arrays to DTO instances.
 *
 * Example:
 *   protected function casts(): array {
 *       return [
 *           'address' => 'dto:' . AddressDTO::class,
 *       ];
 *   }
 */
class DTOCast implements CastsAttributes
{
    /** @param string $dtoClass The DTO class to cast to */
    public function __construct(
        private readonly string $dtoClass,
    ) {}

    /**
     * Cast the given value to a DTO instance.
     *
     * @param array<string, mixed> $attributes
     */
    public function get(mixed $value, array $attributes): mixed
    {
        if (null === $value) {
            return null;
        }

        // If already a DTO instance, return as-is
        if ($value instanceof $this->dtoClass) {
            return $value;
        }

        // If it's an array, convert to DTO
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

        // If it's a DTO, convert to array
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        return $value;
    }
}
