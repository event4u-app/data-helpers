<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use ReflectionClass;
use Throwable;

/**
 * Trait for integrating DataMapper functionality into SimpleDtos.
 *
 * This trait provides functionality to use DataMapper templates and filters
 * directly within Dtos, with the following mapping priority:
 * 1. Template (highest priority)
 * 2. Attributes (#[MapFrom], #[MapTo])
 * 3. Automapping (fallback)
 *
 * Features:
 * - Define templates in the Dto class (protected function mapperTemplate())
 * - Define filters in the Dto class (protected function mapperFilters())
 * - Define pipeline in the Dto class (protected function mapperPipeline())
 * - Pass templates, filters, and pipeline dynamically to from() and fromArray()
 * - Automatic integration with fromArray()
 *
 * Example usage:
 *
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     // Define template in Dto
 *     protected function mapperTemplate(): ?array
 *     {
 *         return [
 *             'id' => '{{ user.id }}',
 *             'name' => '{{ user.full_name | trim | ucfirst }}',
 *             'email' => '{{ user.email | lower }}',
 *         ];
 *     }
 *
 *     // Define filters in Dto
 *     protected function mapperFilters(): ?array
 *     {
 *         return [
 *             'name' => new TrimFilter(),
 *             'email' => new LowercaseFilter(),
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
 * $dto = UserDto::from($apiResponse);
 * $dto = UserDto::from($apiResponse, ['name' => '{{ user.name }}']);
 * $dto = UserDto::fromArray($data); // Also uses template if defined
 * ```
 */
trait SimpleDtoMapperTrait
{
    /**
     * Cache for template configurations per Dto class.
     *
     * @var array<string, array<string, mixed>|null>
     */
    private static array $templateCache = [];

    /**
     * Cache for filter configurations per Dto class.
     *
     * @var array<string, array<string, FilterInterface|array<int, FilterInterface>>|null>
     */
    private static array $filterCache = [];

    /**
     * Cache for pipeline configurations per Dto class.
     *
     * @var array<string, array<int, FilterInterface>|null>
     */
    private static array $pipelineCache = [];

    /**
     * Define the DataMapper template for this Dto.
     *
     * Override this method to define a template that will be used
     * when creating Dtos from source data.
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
     * Define the DataMapper property filters for this Dto.
     *
     * Override this method to define property-specific filters that will be applied
     * when creating Dtos from source data.
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
     * Define the DataMapper pipeline filters for this Dto.
     *
     * Override this method to define pipeline filters that will be applied
     * to all values when creating Dtos from source data.
     *
     * Pipeline filters are applied globally using pipeline().
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
     * Get the cached template for this Dto class.
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
            // The method is from the trait if it's declared in SimpleDtoMapperTrait
            $declaringClass = $method->getDeclaringClass()->getName();
            if (SimpleDtoMapperTrait::class === $declaringClass) {
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
     * Get the cached property filters for this Dto class.
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
            $declaringClass = $method->getDeclaringClass()->getName();
            if (SimpleDtoMapperTrait::class === $declaringClass) {
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
     * Get the cached pipeline filters for this Dto class.
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
            $declaringClass = $method->getDeclaringClass()->getName();
            if (SimpleDtoMapperTrait::class === $declaringClass) {
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
     * Create DTO from array with automatic template support.
     *
     * This method automatically applies mapperTemplate() if defined in the DTO.
     * If you need more control (filters, pipeline), use from() with parameters instead.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $template Optional template override
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional property filters
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function fromArray(
        array $data,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        // Delegate to from() which handles DTO configuration loading and merging
        return static::from($data, $template, $filters, $pipeline);
    }
}
