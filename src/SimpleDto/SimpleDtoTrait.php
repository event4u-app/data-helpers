<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\SimpleDto\Support\SimpleEngine;
use event4u\DataHelpers\Validation\ValidationResult;
use RuntimeException;

/**
 * Core trait for SimpleDto functionality.
 *
 * This trait provides the complete SimpleDto implementation including:
 * - Core methods (from, toArray, toJson, validate, etc.)
 * - Lifecycle hooks (beforeCreate, afterCreate, etc.)
 * - Additional features via traits (diff, with, sorting, etc.)
 * - Framework integrations (Doctrine, Eloquent)
 *
 * Usage:
 *   class MyDto extends SimpleDto {
 *       use SimpleDtoTrait;
 *   }
 *
 * Or use the trait directly without extending SimpleDto:
 *   class MyDto {
 *       use SimpleDtoTrait;
 *   }
 */
trait SimpleDtoTrait
{
    // Import all feature traits
    use SimpleDtoCastsTrait;
    use SimpleDtoMappingTrait;
    use SimpleDtoValidationTrait;
    use SimpleDtoRequestValidationTrait;
    use SimpleDtoMapperTrait;
    use SimpleDtoImporterTrait;
    use SimpleDtoTransformerTrait;
    use SimpleDtoNormalizerTrait;
    use SimpleDtoPipelineTrait;
    use SimpleDtoOptionalTrait;
    use SimpleDtoWrappingTrait;
    use SimpleDtoSerializerTrait;
    use SimpleDtoWithTrait;
    use SimpleDtoSortingTrait;
    use SimpleDtoDiffTrait;
    use SimpleDtoVisibilityTrait;
    use SimpleDtoLazyTrait;
    use SimpleDtoComputedTrait;
    use SimpleDtoConditionalTrait;
    use SimpleDtoLazyCastTrait;
    use SimpleDtoPerformanceTrait;
    use SimpleDtoBenchmarkTrait;
    // Note: SimpleDtoDoctrineTrait and SimpleDtoEloquentTrait are NOT imported by default
    // to maintain framework independence. Import them explicitly if needed.

    /**
     * Create DTO from data.
     *
     * Standard mode: Only accepts arrays
     * ConverterMode: Accepts JSON, XML, CSV, etc.
     *
     * Automatically applies mapperTemplate(), mapperFilters(), and mapperPipeline()
     * if defined in the DTO class (via SimpleDtoMapperTrait). Parameters override DTO configuration.
     * Pipeline filters are merged (DTO pipeline + parameter pipeline).
     *
     * @param array<string, mixed>|string|object $data
     * @param array<string, mixed>|null $template Optional template override
     * @param array<string, \event4u\DataHelpers\Filters\FilterInterface|array<int, \event4u\DataHelpers\Filters\FilterInterface>>|null $filters Optional filters (property => filter)
     * @param array<int, \event4u\DataHelpers\Filters\FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function from(
        mixed $data,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        // Check if DTO uses SimpleDtoMapperTrait (has getTemplateConfig method)
        // and load DTO configuration if available
        $usesMapperTrait = method_exists(static::class, 'getTemplateConfig');

        if ($usesMapperTrait) {
            // Merge with parameters (parameters have priority for template/filters, merged for pipeline)
            if (null === $template) {
                $template = static::getTemplateConfig();
            }
            if (null === $filters) {
                $filters = static::getFilterConfig();
            }

            // Merge pipelines: DTO pipeline + parameter pipeline
            $dtoPipeline = static::getPipelineConfig();
            if (null !== $dtoPipeline && [] !== $dtoPipeline) {
                if (null !== $pipeline && [] !== $pipeline) {
                    // Note: array_merge is correct here for numeric arrays (appends items)
                    $pipeline = array_merge($dtoPipeline, $pipeline);
                } else {
                    $pipeline = $dtoPipeline;
                }
            }
        }

        /** @var static */
        return SimpleEngine::createFromData(static::class, $data, $template, $filters, $pipeline);
    }

    /**
     * Convert DTO to array.
     *
     * Respects #[MapTo], #[Hidden], and conditional attributes.
     *
     * @param array<string, mixed> $context Optional context for conditional properties
     * @return array<string, mixed>
     */
    public function toArray(array $context = []): array
    {
        $data = SimpleEngine::toArray($this, $context);

        // Apply visibility filtering if SimpleDtoVisibilityTrait is used
        if (method_exists($this, 'filterVisibleProperties')) {
            $hiddenProperties = method_exists($this, 'getHiddenFromArrayProperties')
                ? $this->getHiddenFromArrayProperties()
                : [];
            $data = $this->filterVisibleProperties($data, $hiddenProperties);
        }

        // Apply wrapping if SimpleDtoWrappingTrait is used
        if (method_exists($this, 'applyWrapping')) {
            $data = $this->applyWrapping($data);
        }

        // Apply sorting if SimpleDtoSortingTrait is used
        if (method_exists($this, 'applySorting')) {
            return $this->applySorting($data);
        }

        return $data;
    }

    /**
     * Convert DTO to JSON.
     *
     * @param int $options JSON encoding options
     */
    public function toJson(int $options = 0): string
    {
        $data = SimpleEngine::toJsonArray($this);

        // Apply visibility filtering if SimpleDtoVisibilityTrait is used
        if (method_exists($this, 'filterVisibleProperties')) {
            $hiddenProperties = method_exists($this, 'getHiddenFromJsonProperties')
                ? $this->getHiddenFromJsonProperties()
                : [];
            $data = $this->filterVisibleProperties($data, $hiddenProperties);
        }

        // Apply wrapping if SimpleDtoWrappingTrait is used
        if (method_exists($this, 'applyWrapping')) {
            $data = $this->applyWrapping($data);
        }

        return json_encode($data, JSON_THROW_ON_ERROR | $options);
    }

    /**
     * JsonSerializable implementation.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = SimpleEngine::toJsonArray($this);

        // Apply visibility filtering if SimpleDtoVisibilityTrait is used
        if (method_exists($this, 'filterVisibleProperties')) {
            $hiddenProperties = method_exists($this, 'getHiddenFromJsonProperties')
                ? $this->getHiddenFromJsonProperties()
                : [];
            $data = $this->filterVisibleProperties($data, $hiddenProperties);
        }

        // Apply wrapping if SimpleDtoWrappingTrait is used
        if (method_exists($this, 'applyWrapping')) {
            $data = $this->applyWrapping($data);
        }

        // Apply sorting if SimpleDtoSortingTrait is used
        if (method_exists($this, 'applySorting')) {
            return $this->applySorting($data);
        }

        return $data;
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
     * Since SimpleDtos are immutable, this method returns a new instance
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

        if ($data instanceof SimpleDto) {
            return $this->convertToArrayRecursive($data->toArray());
        }

        return $data;
    }

    // =========================================================================
    // Lifecycle Hooks
    // =========================================================================
    //
    // These methods can be overridden in your DTO classes to hook into
    // the lifecycle of DTO creation, validation, and serialization.
    //
    // All hooks are optional and have no performance impact when not overridden.
    //
    // Example:
    //   class UserDto extends SimpleDto {
    //       protected function beforeCreate(array &$data): void {
    //           $data['email'] = strtolower($data['email'] ?? '');
    //       }
    //   }

    /**
     * Called before DTO creation, allows modifying input data.
     *
     * This hook is called before property mapping and casting.
     * You can modify the input data array by reference.
     *
     * @param array<string, mixed> $data Input data (modifiable by reference)
     */
    protected function beforeCreate(array &$data): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after DTO creation.
     *
     * This hook is called after the DTO instance has been created
     * and all properties have been set.
     */
    protected function afterCreate(): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called before property mapping.
     *
     * This hook is called before #[MapFrom] attributes are processed.
     * You can modify the input data array by reference.
     *
     * @param array<string, mixed> $data Input data (modifiable by reference)
     */
    protected function beforeMapping(array &$data): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after property mapping.
     *
     * This hook is called after #[MapFrom] attributes have been processed.
     */
    protected function afterMapping(): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called before casting a property value.
     *
     * This hook is called before type casting, nested DTOs, and custom casters.
     * You can modify the value by reference.
     *
     * @param string $property Property name
     * @param mixed $value Property value (modifiable by reference)
     */
    protected function beforeCasting(string $property, mixed &$value): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after casting a property value.
     *
     * This hook is called after type casting, nested DTOs, and custom casters.
     *
     * @param string $property Property name
     * @param mixed $value Property value (after casting)
     */
    protected function afterCasting(string $property, mixed $value): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called before validation.
     *
     * This hook is called before validation rules are applied.
     * You can modify the input data array by reference.
     *
     * @param array<string, mixed> $data Input data (modifiable by reference)
     */
    protected function beforeValidation(array &$data): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after validation.
     *
     * This hook is called after validation rules have been applied.
     * You can inspect the validation result.
     *
     * @param ValidationResult $result Validation result
     */
    protected function afterValidation(ValidationResult $result): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called before serialization (toArray/toJson).
     *
     * This hook is called before the DTO is converted to an array.
     * You can modify the output data array by reference.
     *
     * @param array<string, mixed> $data Output data (modifiable by reference)
     */
    protected function beforeSerialization(array &$data): void
    {
        // Override in subclass to add custom logic
    }

    /**
     * Called after serialization (toArray/toJson).
     *
     * This hook is called after the DTO has been converted to an array.
     * You can modify and return the output data.
     *
     * @param array<string, mixed> $data Output data
     * @return array<string, mixed> Modified output data
     */
    protected function afterSerialization(array $data): array
    {
        // Override in subclass to add custom logic
        return $data;
    }

    // =========================================================================
    // Magic Methods for Mutability Control
    // =========================================================================

    /**
     * Magic method to set property values.
     *
     * By default, SimpleDto uses readonly properties for immutability.
     * This method allows setting properties if:
     * - The class has #[NotImmutable] attribute (all properties mutable)
     * - The specific property has #[NotImmutable] attribute
     *
     * @param string $name Property name
     * @param mixed $value Property value
     * @throws RuntimeException If property is not mutable
     */
    public function __set(string $name, mixed $value): void
    {
        if (SimpleEngine::isPropertyMutable(static::class, $name)) {
            $this->$name = $value;
        } else {
            throw new RuntimeException(
                sprintf(
                    'Cannot modify property "%s" on immutable DTO "%s". ' .
                    'Use #[NotImmutable] attribute on the class or property to allow modifications.',
                    $name,
                    static::class
                )
            );
        }
    }

    /**
     * Get mapping configuration for this DTO.
     *
     * Returns an array mapping property names to their source keys.
     *
     * @return array<string, string|array<int, string>>
     */
    public static function getMappingConfig(): array
    {
        return SimpleEngine::getMappingConfig(static::class);
    }

    /**
     * Clear the mapping cache for this DTO.
     *
     * Useful for testing or when mapping configuration changes dynamically.
     */
    public static function clearMappingCache(): void
    {
        SimpleEngine::clearMappingCache(static::class);
    }

    /**
     * Check if a computed property has a cached value.
     *
     * @param string $name The name of the computed property
     * @return bool True if the computed property has a cached value
     */
    public function hasComputedCache(string $name): bool
    {
        return SimpleEngine::hasComputedCache($this, $name);
    }

    /**
     * Clear the computed property cache.
     *
     * This is useful when you want to force recomputation of computed properties.
     *
     * @param string|null $property Specific property to clear, or null to clear all
     */
    public function clearComputedCache(?string $property = null): static
    {
        SimpleEngine::clearComputedCache($this, $property);

        return $this;
    }

    /**
     * Include lazy computed properties in the next toArray() or toJson() call.
     *
     * @param array<int, string> $names The names of the lazy computed properties to include
     * @return static A new instance with the lazy computed properties included
     */
    public function includeComputed(array $names): static
    {
        return SimpleEngine::includeComputed($this, $names);
    }

    /**
     * Include lazy properties in the next toArray() or toJson() call.
     *
     * This is an alias for includeComputed() for backward compatibility.
     *
     * @param array<int, string> $names The names of the lazy properties to include
     * @return static A new instance with the lazy properties included
     */
    public function include(array $names): static
    {
        return SimpleEngine::includeComputed($this, $names);
    }

    /**
     * Include all lazy properties in the next toArray() or toJson() call.
     *
     * @return static A new instance with all lazy properties included
     */
    public function includeAll(): static
    {
        return SimpleEngine::includeAllLazy($this);
    }

    /**
     * Create a type-safe collection of Dtos.
     *
     * @param array<int|string, mixed> $items
     * @return DataCollection<static> The collection of Dtos
     * @phpstan-return DataCollection<static>
     */
    public static function collection(array $items = []): DataCollection
    {
        /** @var DataCollection<static> $dataCollection */
        $dataCollection = DataCollection::forDto(static::class, $items);

        return $dataCollection;
    }
}
