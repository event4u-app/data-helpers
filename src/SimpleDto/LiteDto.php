<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto;

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\LiteDto\Support\LiteEngine;
use event4u\DataHelpers\Validation\ValidationResult;
use JsonSerializable;

/**
 * Lightweight, high-performance Data Transfer Object.
 *
 * LiteDto is designed for maximum performance (~0.3μs per operation)
 * with minimal overhead. It provides essential features:
 * - Property mapping with #[MapFrom] and #[MapTo]
 * - Serialization control with #[Hidden]
 * - Empty value handling with #[ConvertEmptyToNull]
 * - Optional Converter support with #[ConverterMode]
 * - Nested DTOs and Collections
 *
 * Performance:
 * - Standard mode: ~0.3μs (array only)
 * - ConverterMode: ~2-3μs (JSON, XML, CSV, etc.)
 *
 * Example usage:
 *   class UserDto extends LiteDto {
 *       public function __construct(
 *           #[MapFrom('user_name')]
 *           public readonly string $name,
 *           #[Hidden]
 *           public readonly string $password,
 *       ) {}
 *   }
 *
 *   $user = UserDto::from(['user_name' => 'John', 'password' => 'secret']);
 *   echo $user->name; // 'John'
 *   $array = $user->toArray(); // ['name' => 'John'] (password hidden)
 *
 * With ConverterMode:
 *   #[ConverterMode]
 *   class ApiDto extends LiteDto {
 *       public function __construct(
 *           public readonly string $name,
 *       ) {}
 *   }
 *
 *   $dto = ApiDto::from('{"name": "John"}'); // JSON
 *   $dto = ApiDto::from('<root><name>John</name></root>'); // XML
 */
abstract class LiteDto implements JsonSerializable
{
    /**
     * Create DTO from data.
     *
     * Standard mode: Only accepts arrays
     * ConverterMode: Accepts JSON, XML, CSV, etc.
     *
     * @param array<string, mixed>|string|object $data
     */
    public static function from(mixed $data): static
    {
        /** @var static */
        return LiteEngine::createFromData(static::class, $data);
    }

    /**
     * Validate data and create DTO if validation passes.
     *
     * @param array<string, mixed>|string|object $data
     * @param array<string> $groups Validation groups to apply (empty = all rules)
     * @return ValidationResult Contains validation status, errors, and validated data
     */
    public static function validate(mixed $data, array $groups = []): ValidationResult
    {
        return LiteEngine::validate(static::class, $data, $groups);
    }

    /**
     * Validate data and create DTO, throwing exception if validation fails.
     *
     * @param array<string, mixed>|string|object $data
     * @param array<string> $groups Validation groups to apply (empty = all rules)
     * @throws ValidationException
     */
    public static function validateAndCreate(mixed $data, array $groups = []): static
    {
        $result = static::validate($data, $groups);

        if ($result->isFailed()) {
            throw new ValidationException(
                'Validation failed: ' . implode(', ', $result->allErrors())
            );
        }

        /** @var static */
        return LiteEngine::createFromData(static::class, $result->validated());
    }

    /**
     * Validate this DTO instance.
     *
     * @return ValidationResult Contains validation status and errors
     */
    public function validateInstance(): ValidationResult
    {
        return LiteEngine::validateInstance($this);
    }

    /**
     * Convert DTO to array.
     *
     * Respects #[MapTo], #[Hidden], and conditional attributes.
     *
     * @param array<string, mixed> $context Optional context for conditional properties
     * @return array<string, mixed>
     */
    public function toArray(array $context = []): array
    {
        return LiteEngine::toArray($this, $context);
    }

    /**
     * Convert DTO to JSON.
     *
     * @param array<string, mixed> $context Optional context for conditional properties
     * @param int $options JSON encoding options
     */
    public function toJson(array $context = [], int $options = 0): string
    {
        return json_encode(LiteEngine::toJsonArray($this, $context), JSON_THROW_ON_ERROR | $options);
    }

    /**
     * JsonSerializable implementation.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return LiteEngine::toJsonArray($this);
    }

    /**
     * Get value from Dto using dot notation.
     *
     * Supports:
     * - Simple paths: 'name', 'email'
     * - Nested paths: 'address.city', 'user.profile.bio'
     * - Wildcards: 'emails.*.address', 'users.*.orders.*.total'
     * - Array indices: 'items.0.name', 'users.1.email'
     *
     * @param string $path Dot-notation path to the property
     * @param mixed $default Default value if path doesn't exist
     * @return mixed The value at the path, or default if not found
     */
    public function get(string $path, mixed $default = null): mixed
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->get($path, $default);
    }

    /**
     * Set value in Dto using dot notation (returns new instance).
     *
     * Since LiteDtos are immutable, this method returns a new instance
     * with the updated value.
     *
     * Supports:
     * - Simple paths: 'name', 'email'
     * - Nested paths: 'address.city', 'user.profile.bio'
     * - Array indices: 'items.0.name', 'users.1.email'
     *
     * @param string $path Dot-notation path to the property
     * @param mixed $value Value to set
     * @return static New Dto instance with the updated value
     */
    public function set(string $path, mixed $value): static
    {
        $data = $this->toArrayRecursive();
        DataMutator::make($data)->set($path, $value);

        // Ensure we have an array with string keys
        if (!is_array($data)) {
            return static::from([]);
        }

        /** @var array<string, mixed> $data */
        return static::from($data);
    }

    /**
     * Convert Dto to array recursively, including nested Dtos.
     *
     * @return array<string, mixed>
     */
    private function toArrayRecursive(): array
    {
        $data = $this->toArray();
        $result = $this->convertToArrayRecursive($data);

        // Ensure we return an array with string keys
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /** Recursively convert nested Dtos to arrays. */
    private function convertToArrayRecursive(mixed $data): mixed
    {
        if (is_array($data)) {
            /** @var array<string, mixed> $result */
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->convertToArrayRecursive($value);
            }
            return $result;
        }

        if ($data instanceof self) {
            return $this->convertToArrayRecursive($data->toArray());
        }

        return $data;
    }

    // =========================================================================
    // Lifecycle Hooks
    // =========================================================================
    //
    // These methods can be overridden in your DTO classes to hook into
    // the lifecycle of DTO creation, validation, and serialization.
    //
    // All hooks are optional and have no performance impact when not overridden.
    //
    // Example:
    //   class UserDto extends LiteDto {
    //       protected function beforeCreate(array &$data): void {
    //           $data['email'] = strtolower($data['email'] ?? '');
    //       }
    //   }

    /**
     * Called before DTO creation, allows modifying input data.
     *
     * This hook is called before property mapping and casting.
     * You can modify the input data array by reference.
     *
     * @param array<string, mixed> $data Input data (modifiable by reference)
     */
    protected function beforeCreate(array &$data): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after DTO creation.
     *
     * This hook is called after the DTO instance has been created
     * and all properties have been set.
     */
    protected function afterCreate(): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called before property mapping.
     *
     * This hook is called before #[MapFrom] attributes are processed.
     * You can modify the input data array by reference.
     *
     * @param array<string, mixed> $data Input data (modifiable by reference)
     */
    protected function beforeMapping(array &$data): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after property mapping.
     *
     * This hook is called after #[MapFrom] attributes have been processed.
     */
    protected function afterMapping(): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called before casting a property value.
     *
     * This hook is called before type casting, nested DTOs, and custom casters.
     * You can modify the value by reference.
     *
     * @param string $property Property name
     * @param mixed $value Property value (modifiable by reference)
     */
    protected function beforeCasting(string $property, mixed &$value): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after casting a property value.
     *
     * This hook is called after type casting, nested DTOs, and custom casters.
     *
     * @param string $property Property name
     * @param mixed $value Property value (after casting)
     */
    protected function afterCasting(string $property, mixed $value): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called before validation.
     *
     * This hook is called before validation rules are applied.
     * You can modify the input data array by reference.
     *
     * @param array<string, mixed> $data Input data (modifiable by reference)
     */
    protected function beforeValidation(array &$data): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after validation.
     *
     * This hook is called after validation rules have been applied.
     * You can inspect the validation result.
     *
     * @param ValidationResult $result Validation result
     */
    protected function afterValidation(ValidationResult $result): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called before serialization (toArray/toJson).
     *
     * This hook is called before the DTO is converted to an array.
     * You can modify the output data array by reference.
     *
     * @param array<string, mixed> $data Output data (modifiable by reference)
     */
    protected function beforeSerialization(array &$data): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after serialization (toArray/toJson).
     *
     * This hook is called after the DTO has been converted to an array.
     * You can modify and return the output data.
     *
     * @param array<string, mixed> $data Output data
     * @return array<string, mixed> Modified output data
     */
    protected function afterSerialization(array $data): array
    {
        // Override in subclass to add custom logic
        return $data;
    }
}
