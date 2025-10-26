<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Converters;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use SimpleXMLElement;

/**
 * XML converter for bidirectional conversion between arrays and XML strings.
 *
 * Example:
 *   $converter = new XmlConverter();
 *   $array = $converter->toArray('<root><name>John</name><age>30</age></root>');
 *   $xml = $converter->fromArray(['name' => 'John', 'age' => 30]);
 */
class XmlConverter implements ConverterInterface
{
    public function __construct(
        private readonly string $rootElement = 'root',
        private readonly string $version = '1.0',
        private readonly string $encoding = 'UTF-8',
        private readonly bool $formatOutput = true,
    ) {}

    /**
     * Convert XML string to array.
     *
     * @param string $data XML string
     * @return array<string, mixed>
     * @throws InvalidArgumentException If XML is invalid
     */
    public function toArray(string $data): array
    {
        if (empty($data)) {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($data);
        libxml_clear_errors();

        if (false === $xml) {
            throw new InvalidArgumentException('Invalid XML');
        }

        $result = $this->xmlToArray($xml);

        // Ensure we always return an array
        if (is_string($result)) {
            return ['_value' => $result];
        }

        return $result;
    }

    /**
     * Convert SimpleXMLElement to array recursively.
     *
     * @return array<string, mixed>|string
     */
    private function xmlToArray(SimpleXMLElement $xml): array|string
    {
        $array = [];

        // Get attributes
        foreach ($xml->attributes() as $key => $value) {
            $array['@' . $key] = (string)$value;
        }

        // Get children
        $children = $xml->children();

        if (0 === count($children)) {
            // No children, return text content directly
            $text = trim((string)$xml);
            if ('' !== $text) {
                // If there are attributes, store text as _value
                if ([] !== $array) {
                    $array['_value'] = $text;
                    return $array;
                }
                // Otherwise, return the text directly as a simple value
                // This makes XML like <name>John</name> convert to ['name' => 'John']
                // instead of ['name' => ['_value' => 'John']]
                return $text;
            }

            return $array;
        }

        // Process children
        foreach ($children as $name => $child) {
            $childArray = $this->xmlToArray($child);

            // Handle multiple children with same name
            if (isset($array[$name])) {
                if (!is_array($array[$name]) || !isset($array[$name][0])) {
                    $array[$name] = [$array[$name]];
                }
                $array[$name][] = $childArray;
            } else {
                $array[$name] = $childArray;
            }
        }

        return $array;
    }

    /**
     * Convert array to XML string.
     *
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data): string
    {
        $dom = new DOMDocument($this->version, $this->encoding);
        $dom->formatOutput = $this->formatOutput;

        $root = $dom->createElement($this->rootElement);
        $dom->appendChild($root);

        $this->arrayToXml($data, $root, $dom);

        $xml = $dom->saveXML();

        if (false === $xml) {
            throw new InvalidArgumentException('Failed to generate XML');
        }

        return $xml;
    }

    /**
     * Convert array to XML elements recursively.
     *
     * @param array<string, mixed> $data
     */
    private function arrayToXml(array $data, DOMElement $parent, DOMDocument $dom): void
    {
        foreach ($data as $key => $value) {
            // Handle numeric keys
            if (is_numeric($key)) {
                $key = 'item';
            }

            // Sanitize key name
            $key = $this->sanitizeTagName($key);

            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $child = $dom->createElement($key);
                $parent->appendChild($child);
                $this->arrayToXml($value, $child, $dom);
            } elseif (is_bool($value)) {
                $child = $dom->createElement($key, $value ? 'true' : 'false');
                $parent->appendChild($child);
            } elseif (null === $value) {
                $child = $dom->createElement($key);
                $child->setAttribute('nil', 'true');
                $parent->appendChild($child);
            } else {
                $child = $dom->createElement($key, htmlspecialchars((string)$value, ENT_XML1));
                $parent->appendChild($child);
            }
        }
    }

    /** Sanitize tag name to be valid XML. */
    private function sanitizeTagName(string $name): string
    {
        // Replace invalid characters with underscore
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $name);
        if (null === $sanitized) {
            $sanitized = $name;
        }

        // Ensure it starts with a letter or underscore
        if (!preg_match('/^[a-zA-Z_]/', $sanitized)) {
            return '_' . $sanitized;
        }

        return $sanitized;
    }

    public function getContentType(): string
    {
        return 'application/xml';
    }

    public function getFileExtension(): string
    {
        return 'xml';
    }
}
