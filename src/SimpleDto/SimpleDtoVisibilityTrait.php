<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto\Attributes\Hidden;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromArray;
use event4u\DataHelpers\SimpleDto\Attributes\HiddenFromJson;
use event4u\DataHelpers\SimpleDto\Attributes\Visible;
use event4u\DataHelpers\SimpleDto\Support\ConstructorMetadata;
use event4u\DataHelpers\Support\CallbackHelper;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * Trait for handling property visibility in SimpleDtos.
 *
 * Provides functionality for:
 * - Hidden properties (#[Hidden])
 * - Conditional visibility (#[HiddenFromArray], #[HiddenFromJson])
 * - Context-based visibility (#[Visible])
 * - Partial serialization (only(), except())
 */
trait SimpleDtoVisibilityTrait
{
    /** @var array<string, array<string>|array<string, Visible>> Cache for hidden properties per class */
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
            $cached = self::$hiddenPropertiesCache[$cacheKey];
            // Check if this is a string array (hidden properties) or Visible array
            if (is_array($cached)) {
                $firstValue = reset($cached);
                if ($firstValue instanceof Visible) {
                    // This is a Visible array, not a string array
                    return [];
                }
            }
            /** @var array<int, string> */
            return $cached;
        }

        $hidden = [];

        // Use centralized metadata cache
        $metadata = ConstructorMetadata::get(static::class);

        foreach ($metadata['parameters'] as $param) {
            if (isset($param['attributes'][Hidden::class]) || isset($param['attributes'][HiddenFromArray::class])) {
                $hidden[] = $param['name'];
            }
        }

        /** @var array<string> $hidden */
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
            $cached = self::$hiddenPropertiesCache[$cacheKey];
            // Check if this is a string array (hidden properties) or Visible array
            if (is_array($cached)) {
                $firstValue = reset($cached);
                if ($firstValue instanceof Visible) {
                    // This is a Visible array, not a string array
                    return [];
                }
            }
            /** @var array<int, string> */
            return $cached;
        }

        $hidden = [];

        // Use centralized metadata cache
        $metadata = ConstructorMetadata::get(static::class);

        foreach ($metadata['parameters'] as $param) {
            if (isset($param['attributes'][Hidden::class]) || isset($param['attributes'][HiddenFromJson::class])) {
                $hidden[] = $param['name'];
            }
        }

        /** @var array<string> $hidden */
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
            $cached = self::$hiddenPropertiesCache[$cacheKey];
            if (is_array($cached) && isset($cached[0])) {
                // This is a string array, not a Visible array
                return [];
            }
            /** @var array<string, Visible> */
            return $cached;
        }

        $visible = [];

        // Use centralized metadata cache
        $metadata = ConstructorMetadata::get(static::class);

        foreach ($metadata['parameters'] as $param) {
            if (isset($param['attributes'][Visible::class])) {
                /** @var Visible $instance */
                $instance = $param['attributes'][Visible::class];
                $visible[$param['name']] = $instance;
            }
        }

        /** @var array<string, Visible> $visible */
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
     *
     */
    private function getVisibilityContextForAttribute(Visible $visibleAttr): mixed
    {
        // Manual context has highest priority (allows override)
        if (null !== $this->visibilityContext) {
            return $this->visibilityContext;
        }

        // If contextProvider is specified, use it to fetch context
        if (null !== $visibleAttr->contextProvider && class_exists($visibleAttr->contextProvider)) {
            try {
                $reflection = new ReflectionClass($visibleAttr->contextProvider);

                if ($reflection->hasMethod('getContext')) {
                    $method = $reflection->getMethod('getContext');

                    if ($method->isStatic()) {
                        return $method->invoke(null);
                    }
                }
            } catch (ReflectionException) {
                // Fall through to null
            }
        }

        // No context available
        return null;
    }

    /** Check if a property should be visible based on #[Visible] attribute. */
    private function isPropertyVisible(string $propertyName, Visible $visibleAttr): bool
    {
        // Get context (from provider or manual)
        $context = $this->getVisibilityContextForAttribute($visibleAttr);

        // Check callback
        if (null !== $visibleAttr->callback) {
            try {
                $result = CallbackHelper::execute($visibleAttr->callback, $this, $context);
                return (bool)$result;
            } catch (InvalidArgumentException) {
                return false;
            }
        }

        // Check Laravel Gate
        if (null !== $visibleAttr->gate) {
            if (class_exists('Illuminate\Support\Facades\Gate')) {
                return Gate::allows($visibleAttr->gate, $this);
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
            // Phase 6 Optimization #5: Avoid array_flip + array_intersect_key
            // Build new array directly (faster for small property sets)
            $filtered = [];
            foreach ($this->onlyProperties as $property) {
                if (isset($data[$property])) {
                    $filtered[$property] = $data[$property];
                }
            }
            $data = $filtered;
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
     */
    public function withVisibilityContext(mixed $context): static
    {
        // Phase 6 Optimization: Lazy cloning - avoid clone if context is null
        if (null === $context) {
            return $this;
        }

        $clone = clone $this;
        $clone->visibilityContext = $context;

        return $clone;
    }

    /**
     * Include only specified properties in output.
     *
     * @param array<string> $properties
     */
    public function only(array $properties): static
    {
        // Note: Cannot optimize empty array case - only([]) has semantic meaning (show nothing)
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
     */
    public function except(array $properties): static
    {
        // Phase 6 Optimization: Lazy cloning - avoid clone if empty array
        if ([] === $properties) {
            return $this; // No properties to exclude, return self
        }

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
