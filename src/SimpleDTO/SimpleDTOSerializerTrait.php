<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO\Enums\SerializationFormat;
use event4u\DataHelpers\SimpleDTO\Serializers\CsvSerializer;
use event4u\DataHelpers\SimpleDTO\Serializers\SerializerInterface;
use event4u\DataHelpers\SimpleDTO\Serializers\XmlSerializer;
use event4u\DataHelpers\SimpleDTO\Serializers\YamlSerializer;

/**
 * Trait for custom serialization formats.
 *
 * This trait provides methods to serialize DTOs to different formats
 * like XML, YAML, and CSV.
 *
 * Example:
 *   $user = UserDTO::fromArray(['name' => 'John', 'age' => 30]);
 *
 *   // XML
 *   $xml = $user->toXml();
 *
 *   // YAML
 *   $yaml = $user->toYaml();
 *
 *   // CSV
 *   $csv = $user->toCsv();
 *
 *   // Custom serializer
 *   $custom = $user->serializeWith(new MyCustomSerializer());
 */
trait SimpleDTOSerializerTrait
{
    /**
     * Serialize to XML.
     *
     * @param string $rootElement The root element name (default: 'root')
     */
    public function toXml(string $rootElement = 'root'): string
    {
        $serializer = new XmlSerializer($rootElement);

        return $serializer->serialize($this->toArray());
    }

    /**
     * Serialize to YAML.
     *
     * @param int $indent The indentation level (default: 2)
     */
    public function toYaml(int $indent = 2): string
    {
        $serializer = new YamlSerializer($indent);

        return $serializer->serialize($this->toArray());
    }

    /**
     * Serialize to CSV.
     *
     * @param bool $includeHeaders Whether to include headers (default: true)
     * @param string $delimiter The delimiter character (default: ',')
     */
    public function toCsv(bool $includeHeaders = true, string $delimiter = ','): string
    {
        $serializer = new CsvSerializer($includeHeaders, $delimiter);

        return $serializer->serialize($this->toArray());
    }

    /** Serialize with a custom serializer. */
    public function serializeWith(SerializerInterface $serializer): string
    {
        return $serializer->serialize($this->toArray());
    }

    /**
     * Serialize to a specific format using enum.
     *
     * @param SerializationFormat $format The serialization format
     * @param array<string, mixed> $options Format-specific options
     *
     * @return string The serialized data
     */
    public function serializeTo(SerializationFormat $format, array $options = []): string
    {
        return match ($format) {
            SerializationFormat::Json => json_encode($this->toArray(), JSON_THROW_ON_ERROR),
            SerializationFormat::Xml => $this->toXml($options['rootElement'] ?? 'root'),
            SerializationFormat::Yaml => $this->toYaml($options['indent'] ?? 2),
            SerializationFormat::Csv => $this->toCsv($options['includeHeaders'] ?? true, $options['delimiter'] ?? ','),
        };
    }
}
