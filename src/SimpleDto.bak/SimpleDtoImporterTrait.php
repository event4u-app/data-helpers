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
     */
    public static function fromJson(string $json): static
    {
        $converter = new JsonConverter();
        $array = $converter->toArray($json);

        return static::fromArray($array);
    }

    /**
     * Create Dto from XML string.
     *
     * @param string $xml XML string
     * @param string $rootElement Root element name (default: 'root')
     */
    public static function fromXml(string $xml, string $rootElement = 'root'): static
    {
        $converter = new XmlConverter($rootElement);
        $array = $converter->toArray($xml);

        return static::fromArray($array);
    }

    /**
     * Create Dto from YAML string.
     *
     * @param string $yaml YAML string
     */
    public static function fromYaml(string $yaml): static
    {
        $converter = new YamlConverter();
        $array = $converter->toArray($yaml);

        return static::fromArray($array);
    }

    /**
     * Create Dto from CSV string.
     *
     * @param string $csv CSV string
     * @param bool $includeHeaders Whether the CSV has headers (default: true)
     * @param string $delimiter Field delimiter (default: ',')
     */
    public static function fromCsv(
        string $csv,
        bool $includeHeaders = true,
        string $delimiter = ','
    ): static {
        $converter = new CsvConverter($includeHeaders, $delimiter);
        $array = $converter->toArray($csv);

        return static::fromArray($array);
    }
}
