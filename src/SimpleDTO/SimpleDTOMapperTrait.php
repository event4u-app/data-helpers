<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use ReflectionClass;
use Throwable;

/**
 * Trait for integrating DataMapper functionality into SimpleDTOs.
 *
 * This trait provides functionality to use DataMapper templates and filters
 * directly within DTOs, with the following mapping priority:
 * 1. Template (highest priority)
 * 2. Attributes (#[MapFrom], #[MapTo])
 * 3. Automapping (fallback)
 *
 * Features:
 * - Define templates in the DTO class (protected function template())
 * - Define filters in the DTO class (protected function filters())
 * - Pass templates and filters dynamically
 * - Automatic integration with fromArray()
 * - New fromSource() method for direct DataMapper usage
 *
 * Example usage:
 *
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     // Define template in DTO
 *     protected function template(): array
 *     {
 *         return [
 *             'id' => '{{ user.id }}',
 *             'name' => '{{ user.full_name | trim | ucfirst }}',
 *             'email' => '{{ user.email | lower }}',
 *         ];
 *     }
 *
 *     // Define filters in DTO
 *     protected function filters(): array
 *     {
 *         return [
 *             new TrimStrings(),
 *             new LowercaseEmails(),
 *         ];
 *     }
 *
 *     public function __construct(
 *         #[MapFrom('user_id')]  // Attributes still work as fallback
 *         public readonly int $id,
 *         public readonly string $name,
 *         public readonly string $email,
 *     ) {}
 * }
 *
 * // Usage:
 * $dto = UserDTO::fromSource($apiResponse);
 * $dto = UserDTO::fromSource($apiResponse, ['name' => '{{ user.name }}']);
 * $dto = UserDTO::fromArray($data); // Also uses template if defined
 * ```
 */
trait SimpleDTOMapperTrait
{
    /**
     * Cache for template configurations per DTO class.
     *
     * @var array<string, array<string, mixed>|null>
     */
    private static array $templateCache = [];

    /**
     * Cache for filter configurations per DTO class.
     *
     * @var array<string, array<string, FilterInterface|array<int, FilterInterface>>|null>
     */
    private static array $filterCache = [];

    /**
     * Cache for pipeline configurations per DTO class.
     *
     * @var array<string, array<int, FilterInterface>|null>
     */
    private static array $pipelineCache = [];

    /**
     * Define the DataMapper template for this DTO.
     *
     * Override this method to define a template that will be used
     * when creating DTOs from source data.
     *
     * Template expressions use {{ }} syntax with dot-notation and filters:
     * - '{{ user.id }}' - Simple mapping
     * - '{{ user.name | trim | ucfirst }}' - With filters
     * - '{{ user.email | lower }}' - Lowercase email
     *
     * @return array<string, mixed>|null Template array or null if no template
     */
    protected function mapperTemplate(): ?array
    {
        return null;
    }

    /**
     * Define the DataMapper property filters for this DTO.
     *
     * Override this method to define property-specific filters that will be applied
     * when creating DTOs from source data.
     *
     * Property filters are applied to specific properties using setFilters().
     *
     * Example:
     *   return [
     *       'name' => new TrimFilter(),
     *       'email' => new LowercaseFilter(),
     *   ];
     *
     * @return array<string, FilterInterface|array<int, FilterInterface>>|null Associative array of property => filter(s)
     */
    protected function mapperFilters(): ?array
    {
        return null;
    }

    /**
     * Define the DataMapper pipeline filters for this DTO.
     *
     * Override this method to define pipeline filters that will be applied
     * to all values when creating DTOs from source data.
     *
     * Pipeline filters are applied globally using pipe().
     *
     * Example:
     *   return [
     *       new TrimStrings(),
     *       new LowercaseEmails(),
     *   ];
     *
     * @return array<int, FilterInterface>|null Array of filter instances or null
     */
    protected function mapperPipeline(): ?array
    {
        return null;
    }

    /**
     * Get the cached template for this DTO class.
     *
     * @return array<string, mixed>|null
     */
    protected static function getTemplateConfig(): ?array
    {
        $class = static::class;

        if (array_key_exists($class, self::$templateCache)) {
            return self::$templateCache[$class];
        }

        // Check if mapperTemplate() method is overridden
        $reflection = new ReflectionClass($class);

        try {
            $method = $reflection->getMethod('mapperTemplate');

            // Check if method is overridden (not from trait)
            if ($method->getDeclaringClass()->getName() === self::class) {
                // Method is not overridden, return null
                self::$templateCache[$class] = null;

                return null;
            }

            // Method is overridden, call it
            $instance = $reflection->newInstanceWithoutConstructor();
            $template = $instance->mapperTemplate();
            self::$templateCache[$class] = $template;

            return $template;
        } catch (Throwable) {
            self::$templateCache[$class] = null;

            return null;
        }
    }

    /**
     * Get the cached property filters for this DTO class.
     *
     * @return array<string, FilterInterface|array<int, FilterInterface>>|null
     */
    protected static function getFilterConfig(): ?array
    {
        $class = static::class;

        if (array_key_exists($class, self::$filterCache)) {
            return self::$filterCache[$class];
        }

        // Check if mapperFilters() method is overridden
        $reflection = new ReflectionClass($class);

        try {
            $method = $reflection->getMethod('mapperFilters');

            // Check if method is overridden (not from trait)
            if ($method->getDeclaringClass()->getName() === self::class) {
                // Method is not overridden, return null
                self::$filterCache[$class] = null;

                return null;
            }

            // Method is overridden, call it
            $instance = $reflection->newInstanceWithoutConstructor();
            $filters = $instance->mapperFilters();
            self::$filterCache[$class] = $filters;

            return $filters;
        } catch (Throwable) {
            self::$filterCache[$class] = null;

            return null;
        }
    }

    /**
     * Get the cached pipeline filters for this DTO class.
     *
     * @return array<int, FilterInterface>|null
     */
    protected static function getPipelineConfig(): ?array
    {
        $class = static::class;

        if (array_key_exists($class, self::$pipelineCache)) {
            return self::$pipelineCache[$class];
        }

        // Check if mapperPipeline() method is overridden
        $reflection = new ReflectionClass($class);

        try {
            $method = $reflection->getMethod('mapperPipeline');

            // Check if method is overridden (not from trait)
            if ($method->getDeclaringClass()->getName() === self::class) {
                // Method is not overridden, return null
                self::$pipelineCache[$class] = null;

                return null;
            }

            // Method is overridden, call it
            $instance = $reflection->newInstanceWithoutConstructor();
            $pipeline = $instance->mapperPipeline();
            self::$pipelineCache[$class] = $pipeline;

            return $pipeline;
        } catch (Throwable) {
            self::$pipelineCache[$class] = null;

            return null;
        }
    }

    /**
     * Create a DTO instance from source data.
     *
     * This is the main factory method that combines all mapping strategies:
     * 1. Template (from parameter or DTO definition) - HIGHEST PRIORITY
     * 2. Attributes (#[MapFrom], #[MapTo])
     * 3. Automapping (fallback)
     *
     * Processing order:
     * 1. Convert source to array if needed
     * 2. Apply DataMapper template, filters, and pipeline (if defined)
     * 3. Apply property mapping (#[MapFrom] attributes)
     * 4. Apply casts (casts() method)
     * 5. Auto-validate if enabled
     * 6. Wrap lazy and optional properties
     * 7. Construct DTO instance
     *
     * @param mixed $source Source data (array, object, JSON, XML, file path, etc.)
     * @param array<string, mixed>|null $template Optional template override
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional property filters (property => filter) for setFilters()
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters for pipe()
     */
    public static function fromSource(
        mixed $source,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        // Step 1: Get template (parameter > DTO definition)
        $template ??= static::getTemplateConfig();

        // Step 2: Get property filters (parameter > DTO definition)
        $filters ??= static::getFilterConfig();

        // Step 3: Get pipeline filters from DTO definition
        $dtoPipeline = static::getPipelineConfig();

        // Step 4: Merge pipeline: DTO definition + parameter
        $finalPipeline = [];
        if (null !== $dtoPipeline && [] !== $dtoPipeline) {
            $finalPipeline = $dtoPipeline;
        }
        if (null !== $pipeline && [] !== $pipeline) {
            $finalPipeline = array_merge($finalPipeline, $pipeline);
        }

        // Step 5: Apply DataMapper template, filters, and pipeline (if defined)
        $templateApplied = false;
        if (null !== $template || (null !== $filters && [] !== $filters) || [] !== $finalPipeline) {
            $mapper = DataMapper::source($source);

            // Apply template if defined
            if (null !== $template) {
                $mapper = $mapper->template($template);
                $templateApplied = true;
            }

            // Apply property filters if defined (setFilters)
            if (null !== $filters && [] !== $filters) {
                $mapper = $mapper->setFilters($filters);
            }

            // Apply pipeline filters if defined (pipe)
            if ([] !== $finalPipeline) {
                $mapper = $mapper->pipe($finalPipeline);
            }

            // Map and get result
            $source = $mapper->map()->getTarget();
        }

        // Step 4: Convert source to array if needed (after DataMapper)
        if (is_string($source)) {
            $source = json_decode($source, true) ?? [];
        } elseif (is_object($source)) {
            $source = (array)$source;
        }

        // Step 5: Apply property mapping (#[MapFrom] attributes)
        // Skip if template was applied (template has highest priority)
        if ($templateApplied) {
            $data = $source;
        } else {
            $data = static::applyMapping($source);
        }

        // Step 6: Get casts without creating an instance
        $casts = static::getCasts();

        // Step 7: Apply casts if defined
        if ([] !== $casts) {
            $data = static::applyCasts($data, $casts);
        }

        // Step 8: Auto-validate if enabled (before wrapping!)
        if (static::shouldAutoValidate()) {
            $validateAttr = static::getValidateRequestAttribute();
            if ($validateAttr?->auto) {
                $data = static::validateOrFail($data);
            }
        }

        // Step 9: Wrap lazy properties (first!)
        $data = static::wrapLazyProperties($data);

        // Step 10: Wrap optional properties (second, can wrap Lazy)
        $data = static::wrapOptionalProperties($data);

        // Step 11: Construct DTO instance
        /** @phpstan-ignore new.static */
        return new static(...$data);
    }
}

