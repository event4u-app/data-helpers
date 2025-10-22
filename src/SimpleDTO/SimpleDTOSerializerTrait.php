<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO\Config\SerializerOptions;
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
     * @param SerializerOptions|null $options Serializer options (uses default if null)
     */
    public function toXml(?SerializerOptions $options = null): string
    {
        $options ??= SerializerOptions::xml();
        $serializer = new XmlSerializer(
            $options->rootElement,
            $options->xmlVersion,
            $options->encoding
        );

        return $serializer->serialize($this->toArray());
    }

    /**
     * Serialize to YAML.
     *
     * @param SerializerOptions|null $options Serializer options (uses default if null)
     */
    public function toYaml(?SerializerOptions $options = null): string
    {
        $options ??= SerializerOptions::yaml();
        $serializer = new YamlSerializer($options->indent);

        return $serializer->serialize($this->toArray());
    }

    /**
     * Serialize to CSV.
     *
     * @param SerializerOptions|null $options Serializer options (uses default if null)
     */
    public function toCsv(?SerializerOptions $options = null): string
    {
        $options ??= SerializerOptions::csv();
        $serializer = new CsvSerializer(
            $options->includeHeaders,
            $options->delimiter,
            $options->enclosure,
            $options->escape
        );

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
     * @param SerializerOptions|null $options Format-specific options (uses default if null)
     *
     * @return string The serialized data
     */
    public function serializeTo(SerializationFormat $format, ?SerializerOptions $options = null): string
    {
        $options ??= SerializerOptions::default();

        return match ($format) {
            SerializationFormat::Json => json_encode(
                $this->toArray(),
                $options->prettyPrint ? JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR : JSON_THROW_ON_ERROR
            ),
            SerializationFormat::Xml => $this->toXml($options),
            SerializationFormat::Yaml => $this->toYaml($options),
            SerializationFormat::Csv => $this->toCsv($options),
        };
    }
}
