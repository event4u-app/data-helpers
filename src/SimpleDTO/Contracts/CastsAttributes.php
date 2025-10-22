<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Contracts;

/**
 * Interface for custom attribute casters.
 *
 * This interface is compatible with Laravel's CastsAttributes interface,
 * allowing you to reuse Laravel casts in SimpleDTOs.
 *
 * Example:
 *   class JsonCast implements CastsAttributes {
 *       public function get(mixed $value, array $attributes): array {
 *           return json_decode($value, true);
 *       }
 *
 *       public function set(mixed $value, array $attributes): string {
 *           return json_encode($value);
 *       }
 *   }
 */
interface CastsAttributes
{
    /**
     * Transform the attribute from the underlying value.
     *
     * @param mixed $value The raw value from the data source
     * @param array<string, mixed> $attributes All attributes being set
     * @return mixed The transformed value
     */
    public function get(mixed $value, array $attributes): mixed;

    /**
     * Transform the attribute to its underlying representation for storage.
     *
     * @param mixed $value The value being set
     * @param array<string, mixed> $attributes All attributes being set
     * @return mixed The value to store
     */
    public function set(mixed $value, array $attributes): mixed;
}
