<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;
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
     * @param array<string, mixed>|object|null $data Input data (request, array, object)
     */
    public function __construct(array|object|null $data = null)
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
     * Return an array of transformer classes that will be applied in order.
     * Transformers are applied before the data is mapped to the template.
     *
     * Example:
     * ```php
     * protected function pipes(): array
     * {
     *     return [
     *         TrimStrings::class,
     *         LowercaseEmails::class,
     *         SkipEmptyValues::class,
     *     ];
     * }
     * ```
     *
     * @return array<int, class-string<TransformerInterface>|TransformerInterface> Array of transformer class names or instances
     */
    protected function pipes(): array
    {
        return [];
    }

    /**
     * Fill the model with data and apply template mapping.
     *
     * @param array<string, mixed>|object $data Input data
     */
    public function fill(array|object $data): static
    {
        // Convert to array if needed
        if (is_object($data)) {
            $data = $this->objectToArray($data);
        }

        // Store original data
        $this->originalData = $data;

        // Apply template mapping with pipes
        $pipes = $this->pipes();
        $template = $this->template();

        // Separate static values from mappings
        [$mappings, $staticValues] = $this->separateStaticValues($template);

        // Apply DataMapper with or without pipes
        if (empty($mappings)) {
            // Only static values
            $this->mappedData = $staticValues;
        } elseif ([] === $pipes) {
            // No pipes - use DataMapper directly
            $result = DataMapper::map(
                ['request' => $data],
                $staticValues,
                $mappings,
                false
            );
            // DataMapper::map() returns the target (array) when target is array
            /** @var array<string, mixed> $result */
            $this->mappedData = $result;
        } else {
            // With pipes - use DataMapper::pipe()
            $result = DataMapper::pipe($pipes)->map(
                ['request' => $data],
                $staticValues,
                $mappings,
                false
            );
            // DataMapper::pipe()->map() returns the target (array) when target is array
            /** @var array<string, mixed> $result */
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
     * Convert object to array.
     *
     * @return array<string, mixed>
     */
    private function objectToArray(object $object): array
    {
        // Laravel Request
        if (method_exists($object, 'all')) {
            return $object->all();
        }

        // Symfony Request
        if (method_exists($object, 'request') && property_exists($object, 'request')) {
            return $object->request->all();
        }

        // Arrayable interface
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
     * @param array<string, mixed>|object $data Request data
     */
    public static function fromRequest(array|object $data): static
    {
        /** @phpstan-ignore-next-line */
        return (new static($data));
    }

    /** String representation - returns JSON. */
    public function __toString(): string
    {
        return json_encode($this->mappedData, JSON_THROW_ON_ERROR);
    }
}

