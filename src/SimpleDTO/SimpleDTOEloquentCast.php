<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;

/**
 * Eloquent custom cast for SimpleDTOs.
 *
 * This cast allows you to use SimpleDTOs as Eloquent model attributes.
 * The DTO is automatically serialized to JSON when saving to the database
 * and deserialized back to a DTO instance when retrieving from the database.
 *
 * @example
 * ```php
 * class User extends Model
 * {
 *     protected $casts = [
 *         'address' => AddressDTO::class,
 *         'settings' => UserSettingsDTO::class,
 *     ];
 * }
 *
 * // Usage
 * $user = User::find(1);
 * $user->address->street; // Access DTO properties
 * $user->address = AddressDTO::fromArray(['street' => 'Main St', 'city' => 'Berlin']);
 * $user->save(); // Automatically serialized to JSON
 * ```
 *
 * @template TDto of SimpleDTO
 *
 * @implements CastsAttributes<TDto, array<string, mixed>>
 */
class SimpleDTOEloquentCast implements CastsAttributes
{
    /** @param class-string<TDto> $dtoClass */
    public function __construct(
        private readonly string $dtoClass,
    ) {
        // Check if class exists and extends SimpleDTO
        // Use is_a() instead of is_subclass_of() to support anonymous classes
        if (!class_exists($this->dtoClass) || !is_a($this->dtoClass, SimpleDTO::class, true)) {
            throw new InvalidArgumentException(
                sprintf('Class %s must extend ', $this->dtoClass) . SimpleDTO::class
            );
        }
    }

    /**
     * Transform the attribute from the underlying model values.
     *
     * @param array<string, mixed> $attributes
     *
     * @return TDto|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?SimpleDTO
    {
        if (null === $value) {
            return null;
        }

        // If value is already a DTO instance, return it
        if ($value instanceof $this->dtoClass) {
            return $value;
        }

        // If value is a string (JSON), decode it
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new InvalidArgumentException(
                    sprintf('Invalid JSON for attribute %s: ', $key) . json_last_error_msg()
                );
            }

            $value = $decoded;
        }

        // If value is not an array, we can't create a DTO
        if (!is_array($value)) {
            throw new InvalidArgumentException(
                'Cannot create DTO from non-array value for attribute ' . $key
            );
        }

        // Create DTO from array
        /** @var array<string, mixed> $value */
        return $this->dtoClass::fromArray($value);
    }

    /**
     * Transform the attribute to its underlying model values.
     *
     * @param TDto|null $value
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (null === $value) {
            return null;
        }

        // If value is not a DTO, try to create one
        if (!$value instanceof $this->dtoClass) {
            if (is_array($value)) {
                $value = $this->dtoClass::fromArray($value);
            } else {
                throw new InvalidArgumentException(
                    sprintf('Value for attribute %s must be an instance of %s or an array', $key, $this->dtoClass)
                );
            }
        }

        // Serialize DTO to JSON
        $json = json_encode($value->toArray());
        if (false === $json) {
            throw new RuntimeException('Failed to encode DTO to JSON: ' . json_last_error_msg());
        }
        return $json;
    }

    /**
     * Get the serialized representation of the value.
     *
     * @param TDto|null $value
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>|null
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof $this->dtoClass) {
            throw new InvalidArgumentException(
                sprintf('Value for attribute %s must be an instance of %s', $key, $this->dtoClass)
            );
        }

        return $value->toArray();
    }
}

