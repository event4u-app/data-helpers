<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO\Attributes\Hidden;
use event4u\DataHelpers\SimpleDTO\Attributes\HiddenFromArray;
use event4u\DataHelpers\SimpleDTO\Attributes\HiddenFromJson;
use event4u\DataHelpers\SimpleDTO\Attributes\Visible;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Trait for handling property visibility in SimpleDTOs.
 *
 * Provides functionality for:
 * - Hidden properties (#[Hidden])
 * - Conditional visibility (#[HiddenFromArray], #[HiddenFromJson])
 * - Context-based visibility (#[Visible])
 * - Partial serialization (only(), except())
 */
trait SimpleDTOVisibilityTrait
{
    /** @var array<string, array<string>> Cache for hidden properties per class */
    private static array $hiddenPropertiesCache = [];

    /** @var array<string>|null Properties to include (only) */
    private ?array $onlyProperties = null;

    /** @var array<string>|null Properties to exclude (except) */
    private ?array $exceptProperties = null;

    /** @var mixed Context for visibility checks */
    private mixed $visibilityContext = null;

    /**
     * Get properties that should be hidden from toArray().
     *
     * @return array<string>
     */
    private function getHiddenFromArrayProperties(): array
    {
        $cacheKey = static::class . ':array';

        if (isset(self::$hiddenPropertiesCache[$cacheKey])) {
            return self::$hiddenPropertiesCache[$cacheKey];
        }

        $hidden = [];
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute) {
                $attributeName = $attribute->getName();

                if (Hidden::class === $attributeName || HiddenFromArray::class === $attributeName) {
                    $hidden[] = $property->getName();
                    break;
                }
            }
        }

        self::$hiddenPropertiesCache[$cacheKey] = $hidden;

        return $hidden;
    }

    /**
     * Get properties that should be hidden from JSON serialization.
     *
     * @return array<string>
     */
    private function getHiddenFromJsonProperties(): array
    {
        $cacheKey = static::class . ':json';

        if (isset(self::$hiddenPropertiesCache[$cacheKey])) {
            return self::$hiddenPropertiesCache[$cacheKey];
        }

        $hidden = [];
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute) {
                $attributeName = $attribute->getName();

                if (Hidden::class === $attributeName || HiddenFromJson::class === $attributeName) {
                    $hidden[] = $property->getName();
                    break;
                }
            }
        }

        self::$hiddenPropertiesCache[$cacheKey] = $hidden;

        return $hidden;
    }

    /**
     * Get properties with #[Visible] attribute and their conditions.
     *
     * @return array<string, Visible>
     */
    private function getVisibleProperties(): array
    {
        $cacheKey = static::class . ':visible';

        if (isset(self::$hiddenPropertiesCache[$cacheKey])) {
            return self::$hiddenPropertiesCache[$cacheKey];
        }

        $visible = [];
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes(Visible::class);

            if (!empty($attributes)) {
                $visible[$property->getName()] = $attributes[0]->newInstance();
            }
        }

        self::$hiddenPropertiesCache[$cacheKey] = $visible;

        return $visible;
    }

    /**
     * Get the context for visibility checks.
     *
     * Priority:
     * 1. Manually set context (via withVisibilityContext())
     * 2. Context from contextProvider
     * 3. null
     *
     * @param Visible $visibleAttr
     *
     * @return mixed
     */
    private function getVisibilityContextForAttribute(Visible $visibleAttr): mixed
    {
        // Manual context has highest priority (allows override)
        if (null !== $this->visibilityContext) {
            return $this->visibilityContext;
        }

        // If contextProvider is specified, use it to fetch context
        if (null !== $visibleAttr->contextProvider) {
            if (class_exists($visibleAttr->contextProvider)) {
                try {
                    $reflection = new ReflectionClass($visibleAttr->contextProvider);

                    if ($reflection->hasMethod('getContext')) {
                        $method = $reflection->getMethod('getContext');

                        if ($method->isStatic()) {
                            return $method->invoke(null);
                        }
                    }
                } catch (ReflectionException $e) {
                    // Fall through to null
                }
            }
        }

        // No context available
        return null;
    }

    /**
     * Check if a property should be visible based on #[Visible] attribute.
     *
     * @param string $propertyName
     * @param Visible $visibleAttr
     *
     * @return bool
     */
    private function isPropertyVisible(string $propertyName, Visible $visibleAttr): bool
    {
        // Get context (from provider or manual)
        $context = $this->getVisibilityContextForAttribute($visibleAttr);

        // Check callback
        if (null !== $visibleAttr->callback) {
            // Static callback: [Class::class, 'method']
            if (is_array($visibleAttr->callback)) {
                if (count($visibleAttr->callback) === 2) {
                    [$class, $method] = $visibleAttr->callback;

                    if (is_string($class) && is_string($method)) {
                        if (class_exists($class) && method_exists($class, $method)) {
                            try {
                                $reflection = new ReflectionMethod($class, $method);

                                if ($reflection->isStatic()) {
                                    return (bool)$reflection->invoke(null, $this, $context);
                                }
                            } catch (ReflectionException $e) {
                                return false;
                            }
                        }
                    }
                }

                return false;
            }

            // Instance method callback: 'methodName'
            if (is_string($visibleAttr->callback)) {
                if (method_exists($this, $visibleAttr->callback)) {
                    try {
                        // Use Reflection to call private/protected methods
                        $reflection = new ReflectionMethod($this, $visibleAttr->callback);
                        $reflection->setAccessible(true);

                        return (bool)$reflection->invoke($this, $context);
                    } catch (ReflectionException $e) {
                        return false;
                    }
                }

                return false;
            }
        }

        // Check Laravel Gate
        if (null !== $visibleAttr->gate) {
            if (class_exists('Illuminate\Support\Facades\Gate')) {
                return \Illuminate\Support\Facades\Gate::allows($visibleAttr->gate, $this);
            }

            return false;
        }

        // Check Symfony Voter
        if (null !== $visibleAttr->voter) {
            if (interface_exists('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface')) {
                // Try to get authorization checker from container
                // This is a simplified check - in real Symfony apps, inject the service
                return false; // Default to hidden if no checker available
            }

            return false;
        }

        // No condition specified, default to visible
        return true;
    }

    /**
     * Filter properties based on visibility rules.
     *
     * @param array<string, mixed> $data
     * @param array<string> $hiddenProperties
     *
     * @return array<string, mixed>
     */
    private function filterVisibleProperties(array $data, array $hiddenProperties): array
    {
        // Apply hidden properties filter
        foreach ($hiddenProperties as $property) {
            unset($data[$property]);
        }

        // Apply #[Visible] attribute filter
        $visibleProperties = $this->getVisibleProperties();
        foreach ($visibleProperties as $propertyName => $visibleAttr) {
            if (isset($data[$propertyName]) && !$this->isPropertyVisible($propertyName, $visibleAttr)) {
                unset($data[$propertyName]);
            }
        }

        // Apply only() filter
        if (null !== $this->onlyProperties) {
            $data = array_intersect_key($data, array_flip($this->onlyProperties));
        }

        // Apply except() filter
        if (null !== $this->exceptProperties) {
            foreach ($this->exceptProperties as $property) {
                unset($data[$property]);
            }
        }

        return $data;
    }

    /**
     * Set the visibility context for conditional visibility checks.
     *
     * The context is passed to #[Visible] callback methods.
     *
     * @param mixed $context Context object (e.g., current user, request, etc.)
     *
     * @return static
     */
    public function withVisibilityContext(mixed $context): static
    {
        $clone = clone $this;
        $clone->visibilityContext = $context;

        return $clone;
    }

    /**
     * Include only specified properties in output.
     *
     * @param array<string> $properties
     *
     * @return static
     */
    public function only(array $properties): static
    {
        $clone = clone $this;
        $clone->onlyProperties = $properties;
        // Preserve visibility context
        $clone->visibilityContext = $this->visibilityContext;

        return $clone;
    }

    /**
     * Exclude specified properties from output.
     *
     * @param array<string> $properties
     *
     * @return static
     */
    public function except(array $properties): static
    {
        $clone = clone $this;
        $clone->exceptProperties = $properties;
        // Preserve visibility context
        $clone->visibilityContext = $this->visibilityContext;

        return $clone;
    }

    /**
     * Apply visibility filters to toArray() output.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function applyArrayVisibilityFilters(array $data): array
    {
        $hiddenProperties = $this->getHiddenFromArrayProperties();

        return $this->filterVisibleProperties($data, $hiddenProperties);
    }

    /**
     * Apply visibility filters to JSON serialization output.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function applyJsonVisibilityFilters(array $data): array
    {
        $hiddenProperties = $this->getHiddenFromJsonProperties();

        return $this->filterVisibleProperties($data, $hiddenProperties);
    }
}

