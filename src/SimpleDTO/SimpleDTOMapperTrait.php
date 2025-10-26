<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use Exception;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use Symfony\Component\Yaml\Yaml;
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
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters for pipeline()
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
                            $source = $data[0] ?? []; // Take first row for single DTO
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

        // Step 6: Get casts without creating an instance
        $casts = static::getCasts();

        // Step 7: Apply casts if defined
        if ([] !== $casts) {
            $data = static::applyCasts($data, $casts);
        }

        // Step 8: Auto-cast string values to proper types (useful for CSV)
        /** @var array<string, mixed> $data */
        $data = static::autoCastValues($data);

        // Step 9: Auto-validate if enabled (before wrapping!)
        if (static::shouldAutoValidate()) {
            $validateAttr = static::getValidateRequestAttribute();
            if ($validateAttr?->auto) {
                $data = static::validateOrFail($data);
            }
        }

        // Step 10: Wrap lazy properties (first!)
        $data = static::wrapLazyProperties($data);

        // Step 11: Wrap optional properties (second, can wrap Lazy)
        $data = static::wrapOptionalProperties($data);

        // Step 12: Construct DTO instance
        /** @phpstan-ignore new.static */
        return new static(...$data);
    }

    /**
     * Auto-cast values to proper types based on constructor parameter types.
     *
     * This ensures that DTOs always receive the correct types, regardless of the source
     * (CSV, XML, HTTP requests, etc.). Type casting is safe and only happens when the
     * value can be safely converted.
     *
     * Casting rules:
     * - int: Casts numeric strings, booleans (true=1, false=0), and numeric values
     * - float: Casts numeric strings and numeric values
     * - bool: Casts boolean-like strings ("true", "1", "yes", "on" → true, etc.)
     * - string: Casts scalar values to strings
     * - array: Attempts to decode JSON strings
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected static function autoCastValues(array $data): array
    {
        try {
            $reflection = new ReflectionClass(static::class);
            $constructor = $reflection->getConstructor();

            if (!$constructor) {
                return $data;
            }

            foreach ($constructor->getParameters() as $reflectionParameter) {
                $name = $reflectionParameter->getName();

                if (!array_key_exists($name, $data)) {
                    continue;
                }

                $value = $data[$name];

                // Get parameter type
                $type = $reflectionParameter->getType();

                if (!$type instanceof ReflectionNamedType) {
                    continue;
                }

                $typeName = $type->getName();

                // Cast based on type
                $data[$name] = match ($typeName) {
                    'int' => static::castToInt($value),
                    'float' => static::castToFloat($value),
                    'bool' => static::castToBool($value),
                    'string' => static::castToString($value),
                    'array' => static::castToArray($value),
                    default => $value,
                };
            }
        } catch (ReflectionException) {
            // If reflection fails, return data as-is
        }

        return $data;
    }

    /**
     * Safely cast a value to int.
     *
     * Only casts if the value is numeric or boolean.
     * Strings that are not numeric are NOT casted.
     */
    protected static function castToInt(mixed $value): mixed
    {
        // Already an int
        if (is_int($value)) {
            return $value;
        }

        // Boolean: true=1, false=0
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        // Numeric string or float
        if (is_numeric($value)) {
            return (int)$value;
        }

        // Non-numeric string: don't cast
        return $value;
    }

    /**
     * Safely cast a value to float.
     *
     * Only casts if the value is numeric or boolean.
     */
    protected static function castToFloat(mixed $value): mixed
    {
        // Already a float
        if (is_float($value)) {
            return $value;
        }

        // Boolean: true=1.0, false=0.0
        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        // Numeric string or int
        if (is_numeric($value)) {
            return (float)$value;
        }

        // Non-numeric: don't cast
        return $value;
    }

    /**
     * Safely cast a value to bool.
     *
     * Recognizes common boolean representations in strings.
     */
    protected static function castToBool(mixed $value): mixed
    {
        // Already a bool
        if (is_bool($value)) {
            return $value;
        }

        // Int: 0=false, anything else=true
        if (is_int($value)) {
            return 0 !== $value;
        }

        // String: recognize common boolean representations
        if (is_string($value)) {
            $lower = strtolower(trim($value));

            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($lower, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }
        }

        // Don't cast other types
        return $value;
    }

    /**
     * Safely cast a value to string.
     *
     * Only casts scalar values (int, float, bool, string).
     */
    protected static function castToString(mixed $value): mixed
    {
        // Already a string
        if (is_string($value)) {
            return $value;
        }

        // Scalar values can be safely cast to string
        if (is_scalar($value)) {
            return (string)$value;
        }

        // Don't cast arrays or objects
        return $value;
    }

    /**
     * Safely cast a value to array.
     *
     * Attempts to decode JSON strings, otherwise returns as-is.
     */
    protected static function castToArray(mixed $value): mixed
    {
        // Already an array
        if (is_array($value)) {
            return $value;
        }

        // Try to decode JSON string
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (null !== $decoded && is_array($decoded)) {
                return $decoded;
            }
        }

        // Don't cast other types
        return $value;
    }
}
