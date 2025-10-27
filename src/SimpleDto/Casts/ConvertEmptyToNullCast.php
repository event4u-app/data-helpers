<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Casts;

use event4u\DataHelpers\SimpleDto\Contracts\CastsAttributes;

/**
 * Cast that converts empty values to null.
 *
 * This cast is useful when APIs return empty strings or empty arrays for optional fields.
 *
 * By default, it converts:
 * - Empty string ("") to null
 * - Empty array ([]) to null
 * - null to null
 *
 * Optional conversions (disabled by default):
 * - Integer zero (0) - enable with convertZero: true
 * - String zero ("0") - enable with convertStringZero: true
 *
 * Note: Boolean false is NEVER converted to null.
 *
 * Example:
 * ```php
 * class ProfileDto extends SimpleDto
 * {
 *     protected function casts(): array
 *     {
 *         return [
 *             'bio' => ConvertEmptyToNullCast::class,
 *             'count' => new ConvertEmptyToNullCast(convertZero: true),
 *         ];
 *     }
 *
 *     public function __construct(
 *         public readonly ?string $bio = null,
 *         public readonly ?int $count = null,
 *     ) {}
 * }
 *
 * $profile = ProfileDto::fromArray([
 *     'bio' => '',
 *     'count' => 0,
 * ]);
 *
 * echo $profile->bio;   // null
 * echo $profile->count; // null
 * ```
 */
class ConvertEmptyToNullCast implements CastsAttributes
{
    private readonly bool $convertZero;
    private readonly bool $convertStringZero;

    /**
     * @param string|bool ...$parameters Parameters can be:
     *   - Named: convertZero: true, convertStringZero: true
     *   - String format: "convertZero=1", "convertStringZero=1"
     */
    public function __construct(...$parameters)
    {
        // Handle named parameters
        if (isset($parameters['convertZero'])) {
            $this->convertZero = (bool) $parameters['convertZero'];
        } elseif (isset($parameters[0]) && is_string($parameters[0]) && str_contains($parameters[0], 'convertZero=')) {
            // Parse string format: "convertZero=1"
            $this->convertZero = true;
        } else {
            $this->convertZero = false;
        }

        if (isset($parameters['convertStringZero'])) {
            $this->convertStringZero = (bool) $parameters['convertStringZero'];
        } elseif (isset($parameters[1]) && is_string($parameters[1]) && str_contains($parameters[1], 'convertStringZero=')) {
            // Parse string format: "convertStringZero=1"
            $this->convertStringZero = true;
        } elseif (isset($parameters[0]) && is_string($parameters[0]) && str_contains($parameters[0], 'convertStringZero=')) {
            // Parse string format: "convertStringZero=1" as first parameter
            $this->convertStringZero = true;
        } else {
            $this->convertStringZero = false;
        }
    }

    /**
     * Transform the attribute from the underlying value.
     *
     * Converts empty values to null based on configuration.
     *
     * @param mixed $value The raw value from the data source
     * @param array<string, mixed> $attributes All attributes being set
     * @return mixed The transformed value (null if empty, original value otherwise)
     */
    public function get(mixed $value, array $attributes): mixed
    {
        // Don't convert boolean false to null
        if (is_bool($value)) {
            return $value;
        }

        // Handle null
        if ($value === null) {
            return null;
        }

        // Handle empty string
        if ($value === '') {
            return null;
        }

        // Handle empty array
        if (is_array($value) && count($value) === 0) {
            return null;
        }

        // Handle integer zero (optional)
        if ($this->convertZero && $value === 0) {
            return null;
        }

        // Handle string zero (optional)
        if ($this->convertStringZero && $value === '0') {
            return null;
        }

        return $value;
    }

    /**
     * Transform the attribute to its underlying representation for storage.
     *
     * For output, we keep null as null (no conversion back to empty string/array).
     *
     * @param mixed $value The value being set
     * @param array<string, mixed> $attributes All attributes being set
     * @return mixed The value to store
     */
    public function set(mixed $value, array $attributes): mixed
    {
        // For output, keep null as null
        return $value;
    }
}

