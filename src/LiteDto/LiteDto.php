<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto;

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\LiteDto\Support\LiteEngine;
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
     * Convert DTO to array.
     *
     * Respects #[MapTo] and #[Hidden] attributes.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return LiteEngine::toArray($this);
    }

    /** Convert DTO to JSON. */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | $options);
    }

    /**
     * JsonSerializable implementation.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
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
}
