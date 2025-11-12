<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto;

use ArrayAccess;
use BackedEnum;
use BadMethodCallException;
use Closure;
use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataCollection;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\Exceptions\TypeMismatchException;
use event4u\DataHelpers\LiteDto\Support\LiteEngine;
use JsonSerializable;
use ReflectionClass;
use Stringable;
use UnitEnum;

/**
 * @internal
 *
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
/**
 * @implements ArrayAccess<string, mixed>
 */
abstract class LiteDto implements JsonSerializable, Stringable, ArrayAccess
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

    /** @var array{hash: string, data: array<string, mixed>, context: array<string, mixed>}|null */
    private ?array $toArrayCache = null;

    /**
     * Convert DTO to array.
     *
     * Respects #[MapTo] and #[Hidden] attributes.
     * Results are cached - if the DTO hasn't changed, the cached array is returned.
     *
     * @param array<string, mixed> $context Optional context for conditional properties
     * @return array<string, mixed>
     */
    public function toArray(array $context = []): array
    {
        // If cache exists, check if we can use it (fast path for readonly DTOs)
        if (null !== $this->toArrayCache && $this->toArrayCache['context'] === $context) {
            // Fast check: if context is identical (same array reference or empty), use cache
            // This covers 99% of cases where toArray() is called multiple times without changes
            return $this->toArrayCache['data'];
        }

        // Slow path: check if state has actually changed (for different contexts)
        if (null !== $this->toArrayCache && $this->toArrayCache['context'] !== $context) {
            $currentHash = $this->calculateToArrayHash($context);
            if ($this->toArrayCache['hash'] === $currentHash) {
                // Update cached context reference for next fast-path check
                $this->toArrayCache['context'] = $context;
                return $this->toArrayCache['data'];
            }
        }

        $data = LiteEngine::toArray($this, $context);

        // Cache the result (calculate hash after processing)
        $this->toArrayCache = [
            'hash' => $this->calculateToArrayHash($context),
            'data' => $data,
            'context' => $context,
        ];

        return $data;
    }

    /**
     * Calculate hash of current DTO state for toArray() caching.
     *
     * Uses xxHash (xxh3) for maximum performance (~10x faster than md5).
     * Prepares data for hashing by converting Enums and removing Closures.
     *
     * @param array<string, mixed> $context
     */
    private function calculateToArrayHash(array $context): string
    {
        // Get all public properties
        $properties = get_object_vars($this);

        // Remove cache property from hash calculation
        unset($properties['toArrayCache']);

        // Prepare data for hashing (convert Enums, remove Closures)
        $hashData = $this->prepareForHashing([
            'properties' => $properties,
            'context' => $context,
        ]);

        // Use json_encode for fast serialization
        $data = json_encode($hashData, JSON_THROW_ON_ERROR);

        // Use xxh3 if available (10x faster than md5), fallback to md5
        return function_exists('hash') && in_array('xxh3', hash_algos(), true)
            ? hash('xxh3', $data)
            : md5($data); // @phpstan-ignore disallowed.function (fallback for systems without hash())
    }

    /** Prepare data for hashing by converting Enums and removing Closures. */
    private function prepareForHashing(mixed $data): mixed
    {
        if ($data instanceof Closure) {
            return null;
        }

        if ($data instanceof BackedEnum) {
            return ['__enum__' => $data::class, 'value' => $data->value];
        }

        if ($data instanceof UnitEnum) {
            return ['__enum__' => $data::class, 'name' => $data->name];
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($value instanceof Closure) {
                    unset($data[$key]);
                } else {
                    $data[$key] = $this->prepareForHashing($value);
                }
            }
            return $data;
        }

        if (is_object($data)) {
            $properties = get_object_vars($data);
            $result = [];
            foreach ($properties as $key => $value) {
                if (!($value instanceof Closure)) {
                    $result[$key] = $this->prepareForHashing($value);
                }
            }
            return $result;
        }

        return $data;
    }

    /**
     * Convert DTO to array for JSON serialization.
     * Applies DateTime formatting with #[DateTimeFormat] attribute.
     *
     * @param array<string, mixed> $context Optional context for conditional properties
     * @return array<string, mixed>
     */
    public function toJsonArray(array $context = []): array
    {
        return LiteEngine::toJsonArray($this, $context);
    }

    /**
     * Convert DTO to JSON.
     *
     * @param array<string, mixed>|int $contextOrOptions Context array or JSON encoding options
     * @param int $options JSON encoding options (only used if first param is context)
     */
    public function toJson(array|int $contextOrOptions = 0, int $options = 0): string
    {
        // Handle backward compatibility: toJson(int $options)
        if (is_int($contextOrOptions)) {
            return json_encode($this->toJsonArray(), JSON_THROW_ON_ERROR | $contextOrOptions);
        }

        // New signature: toJson(array $context, int $options)
        return json_encode($this->toJsonArray($contextOrOptions), JSON_THROW_ON_ERROR | $options);
    }

    /**
     * JsonSerializable implementation.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toJsonArray();
    }

    /** Convert DTO to string (JSON representation). */
    public function __toString(): string
    {
        return $this->toJson();
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
     * Note: LiteDto is immutable, so this always throws an exception.
     * Use set() method instead to create a new instance.
     *
     * @param string $offset Property name or dot-notation path
     * @param mixed $value Property value
     * @throws BadMethodCallException Always throws - LiteDto is immutable
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException(
            'LiteDto is immutable. Use set() method to create a new instance: $newDto = $dto->set("' . $offset . '", $value);'
        );
    }

    /**
     * Unset property (ArrayAccess).
     * Supports dot notation for nested values: 'address.city', 'user.profile.name'
     * Note: LiteDto is immutable, so this always throws an exception.
     *
     * @param string $offset Property name or dot-notation path
     * @throws BadMethodCallException Always throws - LiteDto is immutable
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException(
            'LiteDto is immutable. Use set() method to set null: $newDto = $dto->set("' . $offset . '", null);'
        );
    }

    /**
     * Get all property keys of this DTO.
     *
     * Returns the property names (not the mapped output names) of the DTO.
     * By default, all properties are included (even hidden ones).
     *
     * @param bool $includeHiddenFromArray Include properties with #[Hidden] attribute (default: true)
     * @param bool $includeHiddenFromJson Include properties with #[Hidden] attribute (same as includeHiddenFromArray for LiteDto, default: true)
     * @return array<int, string> Array of property names
     */
    public function getKeys(bool $includeHiddenFromArray = true, bool $includeHiddenFromJson = true): array
    {
        return LiteEngine::getKeys(static::class, $includeHiddenFromArray, $includeHiddenFromJson);
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
     * @return mixed The value at the path or default if not found
     */
    public function get(string $path, mixed $default = null): mixed
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->get($path, $default);
    }

    /**
     * Get an integer value from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (must not contain wildcards)
     * @param int|null $default Default value if path not found
     * @return int|null The integer value or null
     * @throws TypeMismatchException If value is an array or cannot be converted to int
     */
    public function getInt(string $path, ?int $default = null): ?int
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getInt($path, $default);
    }

    /**
     * Get a string value from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (must not contain wildcards)
     * @param string|null $default Default value if path not found
     * @return string|null The string value or null
     * @throws TypeMismatchException If value is an array or cannot be converted to string
     */
    public function getString(string $path, ?string $default = null): ?string
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getString($path, $default);
    }

    /**
     * Get a boolean value from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (must not contain wildcards)
     * @param bool|null $default Default value if path not found
     * @return bool|null The boolean value or null
     * @throws TypeMismatchException If value is an array
     */
    public function getBool(string $path, ?bool $default = null): ?bool
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getBool($path, $default);
    }

    /**
     * Get a float value from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (must not contain wildcards)
     * @param float|null $default Default value if path not found
     * @return float|null The float value or null
     * @throws TypeMismatchException If value is an array or cannot be converted to float
     */
    public function getFloat(string $path, ?float $default = null): ?float
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getFloat($path, $default);
    }

    /**
     * Get an array value from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (must not contain wildcards)
     * @param array<int|string, mixed>|null $default Default value if path not found
     * @return array<int|string, mixed>|null The array value or null
     * @throws TypeMismatchException If value is not an array
     */
    public function getArray(string $path, ?array $default = null): ?array
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getArray($path, $default);
    }

    /**
     * Get a collection of integer values from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (should contain wildcards)
     * @return DataCollection<int> Collection of integer values
     * @throws TypeMismatchException If path doesn't contain wildcards or values cannot be converted to int
     */
    public function getIntCollection(string $path): DataCollection
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getIntCollection($path);
    }

    /**
     * Get a collection of string values from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (should contain wildcards)
     * @return DataCollection<string> Collection of string values
     * @throws TypeMismatchException If path doesn't contain wildcards or values cannot be converted to string
     */
    public function getStringCollection(string $path): DataCollection
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getStringCollection($path);
    }

    /**
     * Get a collection of boolean values from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (should contain wildcards)
     * @return DataCollection<bool> Collection of boolean values
     * @throws TypeMismatchException If path doesn't contain wildcards
     */
    public function getBoolCollection(string $path): DataCollection
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getBoolCollection($path);
    }

    /**
     * Get a collection of float values from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (should contain wildcards)
     * @return DataCollection<float> Collection of float values
     * @throws TypeMismatchException If path doesn't contain wildcards or values cannot be converted to float
     */
    public function getFloatCollection(string $path): DataCollection
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getFloatCollection($path);
    }

    /**
     * Get a collection of array values from the Dto using dot notation.
     *
     * @param string $path Dot-notation path (should contain wildcards)
     * @return DataCollection<array<int|string, mixed>> Collection of array values
     * @throws TypeMismatchException If path doesn't contain wildcards or values are not arrays
     */
    public function getArrayCollection(string $path): DataCollection
    {
        $data = $this->toArrayRecursive();
        $accessor = new DataAccessor($data);

        return $accessor->getArrayCollection($path);
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
     * Unset value in Dto using dot notation (sets to null, returns new instance).
     *
     * Since LiteDtos are immutable, this method returns a new instance
     * with the value set to null.
     *
     * Supports:
     * - Simple paths: 'name', 'email'
     * - Nested paths: 'address.city', 'user.profile.bio'
     * - Array indices: 'items.0.name', 'users.1.email'
     *
     * @param string $path Dot-notation path to the property
     * @return static New Dto instance with the value set to null
     */
    public function unset(string $path): static
    {
        return $this->set($path, null);
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
