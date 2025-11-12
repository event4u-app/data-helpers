<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use ArrayAccess;
use BadMethodCallException;
use event4u\DataHelpers\DataAccessor;
use JsonSerializable;
use ReflectionClass;
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

    /** Convert DTO to string (JSON representation). */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Check if property or path exists.
     * Supports dot notation for nested values: 'address.city', 'user.profile.name'
     *
     * @param string $path Property name or dot-notation path
     */
    public function has(string $path): bool
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->has($path);
    }

    /**
     * Check if property exists (ArrayAccess).
     * Supports dot notation for nested values: 'address.city', 'user.profile.name'
     * Uses the existing has() method internally.
     *
     * @param string $offset Property name or dot-notation path
     */
    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset)) {
            return false;
        }

        return $this->has($offset);
    }

    /**
     * Get property value (ArrayAccess).
     * Supports dot notation for nested values: 'address.city', 'user.profile.name'
     * Uses the existing get() method internally.
     *
     * @param string $offset Property name or dot-notation path
     * @return mixed Property value
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!is_string($offset)) {
            return null;
        }

        return $this->get($offset);
    }

    /**
     * Set property value (ArrayAccess).
     * Supports dot notation for nested values: 'address.city', 'user.profile.name'
     * Uses set() method internally.
     *
     * @param string $offset Property name or dot-notation path
     * @param mixed $value Property value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            throw new BadMethodCallException('Offset must be a string.');
        }

        $this->set($offset, $value);
    }

    /**
     * Unset property (ArrayAccess).
     * Supports dot notation for nested values: 'address.city', 'user.profile.name'
     * Uses unset() method internally.
     *
     * @param string $offset Property name or dot-notation path
     */
    public function offsetUnset(mixed $offset): void
    {
        if (!is_string($offset)) {
            throw new BadMethodCallException('Offset must be a string.');
        }

        $this->unset($offset);
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
        $reflection = new ReflectionClass($this);
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
