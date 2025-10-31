<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\Converters\CsvConverter;
use event4u\DataHelpers\Converters\JsonConverter;
use event4u\DataHelpers\Converters\XmlConverter;
use event4u\DataHelpers\Converters\YamlConverter;

/**
 * Trait for importing Dtos from various formats.
 *
 * This trait provides methods to create Dtos from different formats
 * like JSON, XML, YAML, and CSV using the unified converter system.
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
     * Create Dto from JSON string.
     *
     * @param string $json JSON string
     * @param array<string, mixed>|null $template Optional template for mapping
     * @param array<string, \event4u\DataHelpers\Filters\FilterInterface|array<int, \event4u\DataHelpers\Filters\FilterInterface>>|null $filters Optional property filters
     * @param array<int, \event4u\DataHelpers\Filters\FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function fromJson(
        string $json,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        $converter = new JsonConverter();
        $array = $converter->toArray($json);

        /** @var static */
        return static::from($array, $template, $filters, $pipeline);
    }

    /**
     * Create Dto from XML string.
     *
     * @param string $xml XML string
     * @param array<string, mixed>|null $template Optional template for mapping
     * @param array<string, \event4u\DataHelpers\Filters\FilterInterface|array<int, \event4u\DataHelpers\Filters\FilterInterface>>|null $filters Optional property filters
     * @param array<int, \event4u\DataHelpers\Filters\FilterInterface>|null $pipeline Optional pipeline filters
     * @param string $rootElement Root element name (default: 'root')
     */
    public static function fromXml(
        string $xml,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null,
        string $rootElement = 'root'
    ): static {
        $converter = new XmlConverter($rootElement);
        $array = $converter->toArray($xml);

        /** @var static */
        return static::from($array, $template, $filters, $pipeline);
    }

    /**
     * Create Dto from YAML string.
     *
     * @param string $yaml YAML string
     * @param array<string, mixed>|null $template Optional template for mapping
     * @param array<string, \event4u\DataHelpers\Filters\FilterInterface|array<int, \event4u\DataHelpers\Filters\FilterInterface>>|null $filters Optional property filters
     * @param array<int, \event4u\DataHelpers\Filters\FilterInterface>|null $pipeline Optional pipeline filters
     */
    public static function fromYaml(
        string $yaml,
        ?array $template = null,
        ?array $filters = null,
        ?array $pipeline = null
    ): static {
        $converter = new YamlConverter();
        $array = $converter->toArray($yaml);

        /** @var static */
        return static::from($array, $template, $filters, $pipeline);
    }

    /**
     * Create Dto from CSV string.
     *
     * @param string $csv CSV string
     * @param array<string, mixed>|null $template Optional template for mapping
     * @param array<string, \event4u\DataHelpers\Filters\FilterInterface|array<int, \event4u\DataHelpers\Filters\FilterInterface>>|null $filters Optional property filters
     * @param array<int, \event4u\DataHelpers\Filters\FilterInterface>|null $pipeline Optional pipeline filters
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
        $converter = new CsvConverter($includeHeaders, $delimiter);
        $array = $converter->toArray($csv);

        // CSV converter returns array of rows - take first row for single DTO
        if (is_array($array) && isset($array[0]) && is_array($array[0])) {
            $array = $array[0];
        }

        /** @var static */
        return static::from($array, $template, $filters, $pipeline);
    }
}
