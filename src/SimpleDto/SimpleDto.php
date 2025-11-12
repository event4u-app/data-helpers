<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use ArrayAccess;
use JsonSerializable;
use Stringable;

/**
 * @internal
 *
 * Lightweight, high-performance Data Transfer Object.
 *
 * SimpleDto is designed for maximum performance (~3.0μs per operation)
 * with minimal overhead. It provides essential features:
 * - Property mapping with #[MapFrom] and #[MapTo]
 * - Serialization control with #[Hidden]
 * - Empty value handling with #[ConvertEmptyToNull]
 * - Optional Converter support with #[ConverterMode]
 * - Nested DTOs and Collections
 * - Additional features: diff(), with(), sorted(), wrap(), etc.
 * - Framework integrations: Doctrine, Eloquent
 *
 * Performance:
 * - Standard mode: ~3.0μs per operation
 * - ConverterMode: ~2-3μs (JSON, XML, CSV, etc.)
 *
 * Example usage:
 *   class UserDto extends SimpleDto {
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
 *   class ApiDto extends SimpleDto {
 *       public function __construct(
 *           public readonly string $name,
 *       ) {}
 *   }
 *
 *   $dto = ApiDto::from('{"name": "John"}'); // JSON
 *   $dto = ApiDto::from('<root><name>John</name></root>'); // XML
 *
 * Additional features:
 *   $dto->diff(['name' => 'Jane']); // Compare with data
 *   $dto->with('extra', 'value'); // Add extra data
 *   $dto->sorted(); // Sort output keys
 *   $dto->wrap('data'); // Wrap in key
 */
/**
 * @implements ArrayAccess<string, mixed>
 */
abstract class SimpleDto implements DtoInterface, JsonSerializable, Stringable, ArrayAccess
{
    use SimpleDtoTrait;

    /**
     * Convert DTO to string (JSON representation).
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Check if property exists (ArrayAccess).
     *
     * @param string $offset Property name
     */
    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    /**
     * Get property value (ArrayAccess).
     *
     * @param string $offset Property name
     * @return mixed Property value
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!property_exists($this, $offset)) {
            return null;
        }

        return $this->{$offset}; // @phpstan-ignore-line
    }

    /**
     * Set property value (ArrayAccess).
     * Note: DTOs are immutable, so this throws an exception.
     *
     * @param string $offset Property name
     * @param mixed $value Property value
     * @throws \BadMethodCallException Always throws - DTOs are immutable
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('DTOs are immutable. Use with() method to create a new instance with modified values.');
    }

    /**
     * Unset property (ArrayAccess).
     * Note: DTOs are immutable, so this throws an exception.
     *
     * @param string $offset Property name
     * @throws \BadMethodCallException Always throws - DTOs are immutable
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('DTOs are immutable. Properties cannot be unset.');
    }

    /**
     * Serialize DTO for PHP serialization.
     * Required for readonly properties support.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * Unserialize DTO from PHP serialization.
     * Required for readonly properties support.
     *
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        // Reconstruct the DTO using reflection
        // This is necessary because readonly properties can only be set during construction
        $reflection = new \ReflectionClass($this);
        $constructor = $reflection->getConstructor();

        if (null === $constructor) {
            return;
        }

        $params = $constructor->getParameters();
        $args = [];

        foreach ($params as $param) {
            $name = $param->getName();
            $args[] = $data[$name] ?? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
        }

        // Call constructor with unserialized data
        $constructor->invoke($this, ...$args);
    }
}
