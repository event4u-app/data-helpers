<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\Converters\CsvConverter;
use event4u\DataHelpers\Converters\JsonConverter;
use event4u\DataHelpers\Converters\XmlConverter;
use event4u\DataHelpers\Converters\YamlConverter;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Support\FileLoader;
use InvalidArgumentException;

/**
 * Trait for importing Dtos from various formats.
 *
 * This trait provides methods to create Dtos from different formats
 * like JSON, XML, YAML and CSV using the unified converter system.
 *
 * Example:
 *   // From JSON
 *   $user = UserDto::fromJson('{"name":"John","age":30}');
 *
 *   // From XML
 *   $user = UserDto::fromXml('<root><name>John</name><age>30</age></root>');
 *
 *   // From YAML
 *   $user = UserDto::fromYaml("name: John\nage: 30");
 *
 *   // From CSV
 *   $user = UserDto::fromCsv("name,age\nJohn,30");
 */
trait SimpleDtoImporterTrait
{
    /**
     * Create Dto from JSON string or file.
     *
     * @param string $json JSON string or file path
     * @param array<string, mixed>|null $template Optional template for mapping
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional property filters
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function fromJson(
        string $json,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        // Check if $json is a file path
        if (file_exists($json)) {
            $array = FileLoader::loadAsArray($json);
        } else {
            // It's a JSON string
            $converter = new JsonConverter();
            $array = $converter->toArray($json);
        }

        /** @var static */
        return static::from($array, $template, $filters, $pipeline);
    }

    /**
     * Create Dto from XML string or file.
     *
     * @param string $xml XML string or file path
     * @param array<string, mixed>|null $template Optional template for mapping
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional property filters
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters
     * @param string $rootElement Root element name (default: 'root')
     */
    public static function fromXml(
        string $xml,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null,
        string $rootElement = 'root'
    ): static {
        // Check if $xml is a file path
        if (file_exists($xml)) {
            $array = FileLoader::loadAsArray($xml);
        } else {
            // It's an XML string
            $converter = new XmlConverter($rootElement);
            $array = $converter->toArray($xml);
        }

        /** @var static */
        return static::from($array, $template, $filters, $pipeline);
    }

    /**
     * Create Dto from YAML string or file.
     *
     * @param string $yaml YAML string or file path
     * @param array<string, mixed>|null $template Optional template for mapping
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional property filters
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function fromYaml(
        string $yaml,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        // Check if $yaml is a file path
        if (file_exists($yaml)) {
            $content = file_get_contents($yaml);
            if (false === $content) {
                throw new InvalidArgumentException('Failed to read YAML file: ' . $yaml);
            }
            $converter = new YamlConverter();
            $array = $converter->toArray($content);
        } else {
            // It's a YAML string
            $converter = new YamlConverter();
            $array = $converter->toArray($yaml);
        }

        /** @var static */
        return static::from($array, $template, $filters, $pipeline);
    }

    /**
     * Create Dto from CSV string or file.
     *
     * @param string $csv CSV string or file path
     * @param array<string, mixed>|null $template Optional template for mapping
     * @param array<string, FilterInterface|array<int, FilterInterface>>|null $filters Optional property filters
     * @param array<int, FilterInterface>|null $pipeline Optional pipeline filters
     * @param bool $includeHeaders Whether the CSV has headers (default: true)
     * @param string $delimiter Field delimiter (default: ',')
     */
    public static function fromCsv(
        string $csv,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null,
        bool $includeHeaders = true,
        string $delimiter = ','
    ): static {
        // Check if $csv is a file path
        if (file_exists($csv)) {
            $content = file_get_contents($csv);
            if (false === $content) {
                throw new InvalidArgumentException('Failed to read CSV file: ' . $csv);
            }
            $converter = new CsvConverter($includeHeaders, $delimiter);
            $array = $converter->toArray($content);
        } else {
            // It's a CSV string
            $converter = new CsvConverter($includeHeaders, $delimiter);
            $array = $converter->toArray($csv);
        }

        // CSV converter returns array of rows - take first row for single DTO
        if (isset($array[0]) && is_array($array[0])) {
            $array = $array[0];
        }

        /** @var static */
        return static::from($array, $template, $filters, $pipeline);
    }
}
