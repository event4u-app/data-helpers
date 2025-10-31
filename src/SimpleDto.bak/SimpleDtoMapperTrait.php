<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\SimpleDto\Attributes\NoCasts;
use event4u\DataHelpers\SimpleDto\Support\ConstructorMetadata;
use Exception;
use InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\Yaml\Yaml;
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
 * - Define templates in the Dto class (protected function template())
 * - Define filters in the Dto class (protected function filters())
 * - Pass templates and filters dynamically
 * - Automatic integration with fromArray()
 * - New fromSource() method for direct DataMapper usage
 *
 * Example usage:
 *
 * ```php
 * class UserDto extends SimpleDto
 * {
 *     // Define template in Dto
 *     protected function template(): array
 *     {
 *         return [
 *             'id' => '{{ user.id }}',
 *             'name' => '{{ user.full_name | trim | ucfirst }}',
 *             'email' => '{{ user.email | lower }}',
 *         ];
 *     }
 *
 *     // Define filters in Dto
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
 * $dto = UserDto::fromSource($apiResponse);
 * $dto = UserDto::fromSource($apiResponse, ['name' => '{{ user.name }}']);
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
     * Cache for hasNoCastsAttribute check.
     *
     * @var array<class-string, bool>
     */
    private static array $noCastsCache = [];

    /**
     * Cache for hasLazyProperties check.
     *
     * @var array<class-string, bool>
     */
    private static array $hasLazyCache = [];

    /**
     * Cache for hasOptionalProperties check.
     *
     * @var array<class-string, bool>
     */
    private static array $hasOptionalCache = [];

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
     * Create a Dto instance from source data.
     *
     * This is the main factory method that combines all mapping strategies:
     * 1. Template (from parameter or Dto definition) - HIGHEST PRIORITY
     * 2. Attributes (#[MapFrom], #[MapTo])
     * 3. Automapping (fallback)
     *
     * Processing order:
     * 1. Convert source to array if needed
     * 2. Apply DataMapper template, filters, and pipeline (if defined)
     * 3. Apply property mapping (#[MapFrom] attributes)
     * 4. Get and apply casts (includes #[AutoCast] automatic casts, nested DTOs, explicit casts)
     * 5. Auto-validate if enabled
     * 6. Wrap lazy and optional properties
     * 7. Construct Dto instance
     *
     * @param mixed $source Source data (array, object, JSON, XML, file path, etc.)
     * @param array<string, mixed>|null $template Optional template override
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional property filters (property => filter) for setFilters()
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters for pipeline()
     */
    public static function fromSource(
        mixed $source,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        // Step 1: Get template (parameter > Dto definition)
        $template ??= static::getTemplateConfig();

        // Step 2: Get property filters (parameter > Dto definition)
        $filters ??= static::getFilterConfig();

        // Step 3: Get pipeline filters from Dto definition
        $dtoPipeline = static::getPipelineConfig();

        // Step 4: Merge pipeline: Dto definition + parameter
        $finalPipeline = [];
        if (null !== $dtoPipeline && [] !== $dtoPipeline) {
            $finalPipeline = $dtoPipeline;
        }
        if (null !== $pipeline && [] !== $pipeline) {
            // Note: array_merge is correct here for numeric arrays (appends items)
            // + operator would not work correctly for numeric keys
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
                $mapper = $mapper->pipeline($finalPipeline);
            }

            // Map and get result
            $source = $mapper->map()->getTarget();
        }

        // Step 4: Convert source to array if needed (after DataMapper)
        if (is_string($source)) {
            // Try to detect format and parse accordingly
            $trimmed = trim($source);

            // Try XML first (starts with < or <?xml)
            if (str_starts_with($trimmed, '<')) {
                $parsed = @simplexml_load_string($source);
                if (false !== $parsed) {
                    $json = json_encode($parsed);
                    if (false !== $json) {
                        $source = json_decode($json, true);
                    }
                }
            }

            // Try JSON (starts with { or [)
            elseif (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                $decoded = json_decode($source, true);
                if (null !== $decoded) {
                    $source = $decoded;
                }
            }
            // Try YAML if it looks like YAML (contains : or -)
            elseif (str_contains($source, ':') || str_starts_with($trimmed, '-')) {
                // Prefer ext-yaml if available (faster)
                if (function_exists('yaml_parse')) {
                    $parsed = yaml_parse($source);
                    if (false !== $parsed) {
                        $source = $parsed;
                    }
                }
                // Fallback to symfony/yaml if available
                elseif (class_exists(Yaml::class)) {
                    try {
                        $parsed = Yaml::parse($source);
                        if (null !== $parsed) {
                            $source = $parsed;
                        }
                    } catch (Exception) {
                        // If YAML parsing fails, continue with other formats
                    }
                }
            }
            // Try CSV if it contains commas or newlines
            elseif (str_contains($source, ',') || str_contains($source, "\n")) {
                $lines = str_getcsv($source, "\n", '"', '\\');
                if (count($lines) > 1) {
                    $firstLine = array_shift($lines);
                    if (null !== $firstLine) {
                        $headers = str_getcsv($firstLine, ',', '"', '\\');
                        // Filter out null values from headers
                        $headers = array_filter($headers, fn($h): bool => null !== $h);
                        $data = [];
                        foreach ($lines as $line) {
                            if (null !== $line) {
                                $values = str_getcsv($line, ',', '"', '\\');
                                if (count($values) === count($headers)) {
                                    /** @phpstan-ignore-next-line argument.type */
                                    $combined = array_combine($headers, $values);
                                    /** @phpstan-ignore-next-line function.alreadyNarrowedType */
                                    if (is_array($combined)) {
                                        $data[] = $combined;
                                    }
                                }
                            }
                        }
                        if ([] !== $data) {
                            /** @phpstan-ignore-next-line nullCoalesce.offset */
                            $source = $data[0] ?? []; // Take first row for single Dto
                        }
                    }
                }
            }

            // If still string, try json_decode as fallback
            if (is_string($source)) {
                $source = json_decode($source, true) ?? [];
            }
        } elseif (is_object($source)) {
            $source = (array)$source;
        }

        // Step 5: Apply property mapping (#[MapFrom] attributes)
        // Skip if template was applied (template has highest priority)
        if ($templateApplied) {
            $data = $source;
        } else {
            if (!is_array($source)) {
                throw new InvalidArgumentException('Source data must be an array');
            }
            /** @var array<string, mixed> $source */
            $data = static::applyMapping($source);
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException('Data must be an array after mapping');
        }

        /** @var array<string, mixed> $data */

        // Step 6: Get casts without creating an instance (LAZY)
        // Only get casts if we actually need them
        // This includes:
        // - Automatic casts from #[AutoCast] attribute (lowest priority)
        // - Auto-detected nested DTOs (medium priority)
        // - Casts from attributes like #[DataCollectionOf] (high priority)
        // - Casts from casts() method (highest priority)

        // Performance Optimization: Check if we need casts at all
        // Skip getCasts() if #[NoCasts] is present
        if (!static::hasNoCastsAttribute()) {
            $casts = static::getCasts();

            // Step 7: Apply casts if defined (LAZY)
            if ([] !== $casts) {
                $data = static::applyCasts($data, $casts);
            }
        }

        // Step 8: Auto-validate if enabled (LAZY - before wrapping!)
        // Only validate if auto-validation is enabled
        if (static::shouldAutoValidate()) {
            $validateAttr = static::getValidateRequestAttribute();
            if ($validateAttr?->auto) {
                $data = static::validateOrFail($data);
            }
        }

        // Step 9: Wrap lazy properties (LAZY - first!)
        // Only wrap if lazy properties exist
        if (static::hasLazyProperties()) {
            $data = static::wrapLazyProperties($data);
        }

        // Step 10: Wrap optional properties (LAZY - second, can wrap Lazy)
        // Only wrap if optional properties exist
        if (static::hasOptionalProperties()) {
            $data = static::wrapOptionalProperties($data);
        }

        // Step 11: Construct Dto instance
        /** @phpstan-ignore new.static */
        return new static(...$data);
    }

    /** Check if class has #[NoCasts] attribute (cached). */
    protected static function hasNoCastsAttribute(): bool
    {
        $class = static::class;

        if (!isset(self::$noCastsCache[$class])) {
            $metadata = ConstructorMetadata::get($class);
            self::$noCastsCache[$class] = isset($metadata['classAttributes'][NoCasts::class]);
        }

        return self::$noCastsCache[$class];
    }

    /** Check if class has lazy properties (cached). */
    protected static function hasLazyProperties(): bool
    {
        $class = static::class;

        if (!isset(self::$hasLazyCache[$class])) {
            $lazyProps = static::getLazyProperties();
            self::$hasLazyCache[$class] = [] !== $lazyProps;
        }

        return self::$hasLazyCache[$class];
    }

    /** Check if class has optional properties (cached). */
    protected static function hasOptionalProperties(): bool
    {
        $class = static::class;

        if (!isset(self::$hasOptionalCache[$class])) {
            $optionalProps = static::getOptionalProperties();
            self::$hasOptionalCache[$class] = [] !== $optionalProps;
        }

        return self::$hasOptionalCache[$class];
    }
}
