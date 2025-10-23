<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Base class for models with automatic data mapping and template-based transformation.
 *
 * Similar to Laravel's Model Binding, this class allows automatic instantiation from request data
 * with template-based field mapping and transformation.
 *
 * Example usage:
 *
 * ```php
 * class UserRegistrationModel extends MappedDataModel
 * {
 *     protected function template(): array
 *     {
 *         return [
 *             'email' => '@request.email',
 *             'first_name' => '@request.first_name',
 *             'last_name' => '@request.last_name',
 *             'age' => '@request.age',
 *             'is_active' => true,
 *         ];
 *     }
 *
 *     public function getEmail(): string
 *     {
 *         return strtolower(trim($this->email ?? ''));
 *     }
 * }
 *
 * // In Laravel Controller:
 * public function register(UserRegistrationModel $model)
 * {
 *     // $model is automatically instantiated and mapped
 *     $email = $model->getEmail(); // Transformed value
 *     $original = $model->getOriginal('email'); // Original value
 *     $all = $model->toArray(); // Only mapped values
 * }
 * ```
 *
 * Features:
 * - Automatic mapping from request data (Laravel/Symfony)
 * - Template-based field mapping
 * - Access to both original and mapped values
 * - Default serialization (toArray, JSON) uses only mapped values
 * - Custom getters for transformation
 * - Immutable by default (can be overridden)
 */
abstract class MappedDataModel implements JsonSerializable, Stringable
{
    /** @var array<string, mixed> Original input data before mapping */
    private array $originalData = [];
    /** @var array<string, mixed> Mapped/transformed data after template application */
    private array $mappedData = [];
    /** @var bool Whether the model has been mapped */
    private bool $isMapped = false;

    /**
     * Create a new mapped model instance.
     *
     * @param array<string, mixed>|object|string|null $data Input data (request, array, object, JSON string, or XML string)
     */
    public function __construct(array|object|string|null $data = null)
    {
        if (null !== $data) {
            $this->fill($data);
        }
    }

    /**
     * Define the mapping template.
     *
     * This method must be implemented by child classes to define how input data
     * should be transformed into the final model structure.
     *
     * Template supports nested mapping:
     * - Simple mapping: ['email' => 'request.email']
     * - Nested mapping: ['user' => ['name' => 'request.name']]
     * - Static values: ['is_active' => true]
     *
     * @return array<string, mixed> Mapping template
     */
    abstract protected function template(): array;

    /**
     * Define the data transformation pipeline.
     *
     * Return an array of transformer instances that will be applied in order.
     * Transformers are applied before the data is mapped to the template.
     *
     * Example:
     * ```php
     * protected function pipes(): array
     * {
     *     return [
     *         new TrimStrings(),
     *         new LowercaseEmails(),
     *         new SkipEmptyValues(),
     *         new DefaultValue('Unknown'),
     *     ];
     * }
     * ```
     *
     * @return array<int, FilterInterface> Array of filter instances
     */
    protected function pipes(): array
    {
        return [];
    }

    /**
     * Fill the model with data and apply template mapping.
     *
     * @param array<string, mixed>|object|string $data Input data (array, object, JSON string, or XML string)
     */
    public function fill(array|object|string $data): static
    {
        // Convert string (JSON/XML) to array if needed
        if (is_string($data)) {
            $data = $this->stringToArray($data);
        }

        // Convert to array if needed
        if (is_object($data)) {
            $data = $this->objectToArray($data);
        }

        // Store original data
        $this->originalData = $data;

        // Apply template mapping
        $template = $this->template();
        [$mappings, $staticValues] = $this->separateStaticValues($template);

        // Apply DataMapper
        if (empty($mappings)) {
            // Only static values - no mapping needed
            $this->mappedData = $staticValues;
        } else {
            // Map with or without pipes
            $pipes = $this->pipes();
            $source = ['request' => $data];

            /** @var array<string, mixed> $result */
            $result = DataMapper::source($source)->target($staticValues)->template($mappings)->skipNull(
                false
            )->pipeline(
                $pipes
            )->map()->getTarget();

            $this->mappedData = $result;
        }

        $this->isMapped = true;

        return $this;
    }

    /**
     * Separate static values from path mappings.
     *
     * @param array<string, mixed> $template
     * @return array{0: array<string, string>, 1: array<string, mixed>}
     */
    private function separateStaticValues(array $template): array
    {
        $mappings = [];
        $staticValues = [];

        foreach ($template as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_array($value)) {
                // Nested mapping - recursively separate
                /** @var array<string, mixed> $value */
                [$nestedMappings, $nestedStatic] = $this->separateStaticValues($value);

                if (!empty($nestedMappings)) {
                    foreach ($nestedMappings as $nestedKey => $nestedValue) {
                        $mappings[sprintf('%s.%s', $key, $nestedKey)] = $nestedValue;
                    }
                }

                if (!empty($nestedStatic)) {
                    $staticValues[$key] = $nestedStatic;
                }
            } elseif (is_string($value) && (str_contains($value, '.') || str_contains($value, '*'))) {
                // Path mapping (contains dot or wildcard)
                $mappings[$key] = $value;
            } else {
                // Static value (bool, int, string without dot, null)
                $staticValues[$key] = $value;
            }
        }

        return [$mappings, $staticValues];
    }

    /**
     * Get a mapped value by key.
     *
     * @param string $key Field name
     * @param mixed $default Default value if not found
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->mappedData[$key] ?? $default;
    }

    /**
     * Get an original (unmapped) value by key.
     *
     * Useful for validation or debugging to see what was actually sent.
     *
     * @param string $key Field name
     * @param mixed $default Default value if not found
     */
    public function getOriginal(string $key, mixed $default = null): mixed
    {
        return $this->originalData[$key] ?? $default;
    }

    /**
     * Check if a mapped field exists.
     *
     * @param string $key Field name
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->mappedData);
    }

    /**
     * Check if an original field exists.
     *
     * @param string $key Field name
     */
    public function hasOriginal(string $key): bool
    {
        return array_key_exists($key, $this->originalData);
    }

    /**
     * Get all mapped data as array.
     *
     * This is the default serialization - only returns transformed values.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->mappedData;
    }

    /**
     * Get all original (unmapped) data as array.
     *
     * @return array<string, mixed>
     */
    public function getOriginalData(): array
    {
        return $this->originalData;
    }

    /**
     * Get the template definition.
     *
     * @return array<string, mixed>
     */
    public function getTemplate(): array
    {
        return $this->template();
    }

    /** Check if the model has been mapped. */
    public function isMapped(): bool
    {
        return $this->isMapped;
    }

    /**
     * Magic getter for mapped values.
     *
     * @param string $key Field name
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Magic isset for mapped values.
     *
     * @param string $key Field name
     */
    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    /**
     * JSON serialization - returns only mapped values.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->mappedData;
    }

    /**
     * Convert string (JSON/XML) to array.
     *
     * @param string $string JSON or XML string
     * @return array<string, mixed>
     * @throws InvalidArgumentException If string is not valid JSON or XML
     */
    private function stringToArray(string $string): array
    {
        // Try JSON first
        $trimmed = trim($string);
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($string, true);
            if (JSON_ERROR_NONE === json_last_error()) {
                /** @var array<string, mixed> */
                return $decoded;
            }
        }

        // Try XML
        if (str_starts_with($trimmed, '<?xml') || str_starts_with($trimmed, '<')) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($string);
            libxml_clear_errors();

            if (false !== $xml) {
                $jsonString = json_encode($xml);
                if (false === $jsonString) {
                    throw new InvalidArgumentException('Failed to encode XML to JSON');
                }

                $result = json_decode($jsonString, true);
                if (!is_array($result)) {
                    return [];
                }

                /** @var array<string, mixed> */
                return $result;
            }
        }

        throw new InvalidArgumentException('Input string is neither valid JSON nor valid XML');
    }

    /**
     * Convert object to array.
     *
     * @return array<string, mixed>
     */
    private function objectToArray(object $object): array
    {
        // Laravel/Symfony Request (both have all() method)
        if (method_exists($object, 'all')) {
            return $object->all();
        }

        // Arrayable interface (Laravel)
        if (method_exists($object, 'toArray')) {
            /** @var array<string, mixed> */
            return $object->toArray();
        }

        // JsonSerializable
        if ($object instanceof JsonSerializable) {
            /** @var array<string, mixed> */
            return (array)$object->jsonSerialize();
        }

        // Fallback: public properties
        /** @var array<string, mixed> */
        return get_object_vars($object);
    }

    /**
     * Create instance from request data (for framework binding).
     *
     * This static method is used by Laravel/Symfony for automatic dependency injection.
     *
     * @param array<string, mixed>|object|string $data Request data (array, object, JSON string, or XML string)
     */
    public static function fromRequest(array|object|string $data): static
    {
        return new static($data); // @phpstan-ignore-line new.static
    }

    /** String representation - returns JSON. */
    public function __toString(): string
    {
        return json_encode($this->mappedData, JSON_THROW_ON_ERROR);
    }
}
