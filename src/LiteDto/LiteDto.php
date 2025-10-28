<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto;

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
}
