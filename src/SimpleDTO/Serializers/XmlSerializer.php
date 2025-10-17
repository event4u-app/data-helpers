<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Serializers;

use DOMDocument;
use DOMElement;

/**
 * XML serializer for DTOs.
 *
 * Converts DTO data to XML format.
 *
 * Example:
 *   $user = UserDTO::fromArray(['name' => 'John', 'age' => 30]);
 *   $xml = $user->toXml();
 *   // Result: <?xml version="1.0"?><root><name>John</name><age>30</age></root>
 */
class XmlSerializer implements SerializerInterface
{
    public function __construct(
        private readonly string $rootElement = 'root',
        private readonly string $version = '1.0',
        private readonly string $encoding = 'UTF-8',
    ) {}

    /**
     * Serialize data to XML.
     *
     * @param array<string, mixed> $data
     */
    public function serialize(array $data): string
    {
        $dom = new DOMDocument($this->version, $this->encoding);
        $dom->formatOutput = true;

        $root = $dom->createElement($this->rootElement);
        $dom->appendChild($root);

        $this->arrayToXml($data, $root, $dom);

        return $dom->saveXML() ?: '';
    }

    /**
     * Convert array to XML elements.
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
                $child = $dom->createElement($key, htmlspecialchars((string) $value, ENT_XML1));
                $parent->appendChild($child);
            }
        }
    }

    /**
     * Sanitize tag name to be valid XML.
     */
    private function sanitizeTagName(string $name): string
    {
        // Replace invalid characters with underscore
        $name = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $name);

        // Ensure it starts with a letter or underscore
        if (!preg_match('/^[a-zA-Z_]/', $name)) {
            $name = '_' . $name;
        }

        return $name;
    }

    public function getContentType(): string
    {
        return 'application/xml';
    }
}

