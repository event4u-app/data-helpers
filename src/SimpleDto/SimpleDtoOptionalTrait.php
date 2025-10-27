<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto\Attributes\Optional as OptionalAttribute;
use event4u\DataHelpers\Support\Lazy;
use event4u\DataHelpers\Support\Optional;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Trait for handling optional properties in SimpleDtos.
 *
 * Optional properties distinguish between:
 * - A value that is explicitly set to null
 * - A value that was not provided at all (missing)
 *
 * Supports two syntaxes:
 * 1. Attribute syntax: #[Optional] public readonly string $email;
 * 2. Union type syntax: public readonly Optional|string $email;
 */
trait SimpleDtoOptionalTrait
{
    /**
     * Get all optional properties for this Dto class.
     *
     * Returns a map of property names to their Optional attribute instances or true for union types.
     *
     * @return array<string, OptionalAttribute|true>
     */
    protected static function getOptionalProperties(): array
    {
        static $cache = [];

        $class = static::class;

        if (isset($cache[$class])) {
            return $cache[$class];
        }

        $optional = [];

        try {
            $reflection = new ReflectionClass($class);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                // Check for #[Optional] attribute
                $attributes = $property->getAttributes(OptionalAttribute::class);

                if (!empty($attributes)) {
                    $optionalAttr = $attributes[0]->newInstance();
                    $optional[$property->getName()] = $optionalAttr;
                    continue;
                }

                // Check for Optional union type
                $type = $property->getType();

                if ($type instanceof ReflectionUnionType) {
                    foreach ($type->getTypes() as $unionType) {
                        if ($unionType instanceof ReflectionNamedType && $unionType->getName() === Optional::class) {
                            $optional[$property->getName()] = true; // Mark as union type
                            break;
                        }
                    }
                }
            }
        } catch (ReflectionException) {
            // If reflection fails, return empty array
            return [];
        }

        $cache[$class] = $optional;

        return $optional;
    }

    /**
     * Check if a property is optional.
     *
     * @param string $propertyName The property name
     */
    protected static function isOptionalProperty(string $propertyName): bool
    {
        $optionalProperties = static::getOptionalProperties();

        return isset($optionalProperties[$propertyName]);
    }

    /**
     * Wrap optional properties in Optional wrapper.
     *
     * @param array<string, mixed> $data The data array
     *
     * @return array<string, mixed>
     */
    protected static function wrapOptionalProperties(array $data): array
    {
        $optionalProperties = static::getOptionalProperties();

        if ([] === $optionalProperties) {
            return $data;
        }

        $wrapped = [];

        foreach ($optionalProperties as $propertyName => $optionalAttr) {
            if (array_key_exists($propertyName, $data)) {
                // Value is present (even if null)
                $wrapped[$propertyName] = Optional::of($data[$propertyName]);
            } else {
                // Value is missing
                $default = $optionalAttr instanceof OptionalAttribute ? $optionalAttr->default : null;
                $wrapped[$propertyName] = null !== $default ? Optional::of($default) : Optional::empty();
            }

            // Remove from original data
            unset($data[$propertyName]);
        }

        // Merge wrapped optional properties with remaining data
        return array_merge($data, $wrapped);
    }

    /**
     * Unwrap optional properties for serialization.
     *
     * @param array<string, mixed> $data The data array
     * @param bool $includeEmpty Whether to include empty Optional values
     *
     * @return array<string, mixed>
     */
    protected static function unwrapOptionalProperties(array $data, bool $includeEmpty = true): array
    {
        $unwrapped = [];

        foreach ($data as $key => $value) {
            if ($value instanceof Optional) {
                if ($value->isPresent() || $includeEmpty) {
                    $unwrapped[$key] = $value->get();
                }
                // Skip empty Optional values if includeEmpty is false
            } else {
                $unwrapped[$key] = $value;
            }
        }

        return $unwrapped;
    }

    /**
     * Get only present optional values (for partial updates).
     *
     * @return array<string, mixed>
     */
    public function partial(): array
    {
        $data = get_object_vars($this);

        // Remove internal properties
        unset(
            $data['onlyProperties'],
            $data['exceptProperties'],
            $data['visibilityContext'],
            $data['computedCache'],
            $data['includedComputed'],
            $data['includedLazy'],
            $data['includeAllLazy'],
            $data['wrapKey'],
            $data['objectVarsCache'],
            $data['castedProperties'],
            $data['conditionalContext'],
            $data['additionalData'],
            $data['sortingEnabled'],
            $data['sortDirection'],
            $data['nestedSort'],
            $data['sortCallback']
        );

        $partial = [];

        foreach ($data as $key => $value) {
            if ($value instanceof Optional) {
                // Only include present values
                if ($value->isPresent()) {
                    $unwrapped = $value->get();

                    // Unwrap Lazy if needed
                    if ($unwrapped instanceof Lazy) {
                        $unwrapped = $unwrapped->get();
                    }

                    $partial[$key] = $unwrapped;
                }
            } else {
                // Unwrap Lazy if needed
                if ($value instanceof Lazy) {
                    $value = $value->get();
                }

                // Include non-optional values
                $partial[$key] = $value;
            }
        }

        return $partial;
    }

    /**
     * Get all optional property names.
     *
     * @return array<string>
     */
    protected static function getOptionalPropertyNames(): array
    {
        return array_keys(static::getOptionalProperties());
    }
}
