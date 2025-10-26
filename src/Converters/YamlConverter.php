<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Converters;

use InvalidArgumentException;

/**
 * YAML converter for bidirectional conversion between arrays and YAML strings.
 *
 * This is a simple implementation that doesn't require the symfony/yaml package.
 * For production use with complex YAML, consider using symfony/yaml.
 *
 * Example:
 *   $converter = new YamlConverter();
 *   $array = $converter->toArray("name: John\nage: 30");
 *   $yaml = $converter->fromArray(['name' => 'John', 'age' => 30]);
 */
class YamlConverter implements ConverterInterface
{
    public function __construct(
        private readonly int $indent = 2,
    ) {}

    /**
     * Convert YAML string to array.
     *
     * @param string $data YAML string
     * @return array<string, mixed>
     * @throws InvalidArgumentException If YAML is invalid
     */
    public function toArray(string $data): array
    {
        if (empty($data)) {
            return [];
        }

        // Simple YAML parser (supports basic key-value pairs and nested structures)
        $lines = explode("\n", $data);
        $result = [];
        $stack = [&$result];
        $lastIndent = 0;

        foreach ($lines as $line) {
            // Skip empty lines and comments
            if (empty(trim($line)) || str_starts_with(trim($line), '#')) {
                continue;
            }

            // Calculate indentation
            $indent = strlen($line) - strlen(ltrim($line));
            $line = trim($line);

            // Handle list items
            if (str_starts_with($line, '- ')) {
                $value = $this->parseValue(substr($line, 2));
                $current = &$stack[count($stack) - 1];
                $current[] = $value;
                continue;
            }

            // Handle key-value pairs
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Adjust stack based on indentation
                if ($indent < $lastIndent) {
                    $diff = ($lastIndent - $indent) / $this->indent;
                    for ($i = 0; $i < $diff; $i++) {
                        array_pop($stack);
                    }
                }

                $current = &$stack[count($stack) - 1];

                if ('' === $value) {
                    // Nested structure
                    $current[$key] = [];
                    $stack[] = &$current[$key];
                } else {
                    // Simple value
                    $current[$key] = $this->parseValue($value);
                }

                $lastIndent = $indent;
            }
        }

        return $result;
    }

    /** Parse a YAML value to its PHP type. */
    private function parseValue(string $value): mixed
    {
        $value = trim($value);

        // Boolean
        if ('true' === $value || 'yes' === $value) {
            return true;
        }
        if ('false' === $value || 'no' === $value) {
            return false;
        }

        // Null
        if ('null' === $value || '~' === $value) {
            return null;
        }

        // Number
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        // String (remove quotes if present)
        if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Convert array to YAML string.
     *
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data): string
    {
        return $this->arrayToYaml($data, 0);
    }

    /**
     * Convert array to YAML string recursively.
     *
     * @param array<string, mixed> $data
     */
    private function arrayToYaml(array $data, int $level): string
    {
        $yaml = '';
        $indent = str_repeat(' ', $level * $this->indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                // Check if it's a sequential array
                if ($this->isSequentialArray($value)) {
                    $yaml .= $indent . $key . ':' . PHP_EOL;
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            /** @var array<string, mixed> $item */
                            $yaml .= $indent . str_repeat(' ', $this->indent) . '-' . PHP_EOL;
                            $yaml .= $this->arrayToYaml($item, $level + 2);
                        } else {
                            $yaml .= $indent . str_repeat(' ', $this->indent) . '- ' . $this->formatValue(
                                $item
                            ) . PHP_EOL;
                        }
                    }
                } else {
                    $yaml .= $indent . $key . ':' . PHP_EOL;
                    $yaml .= $this->arrayToYaml($value, $level + 1);
                }
            } else {
                $yaml .= $indent . $key . ': ' . $this->formatValue($value) . PHP_EOL;
            }
        }

        return $yaml;
    }

    /**
     * Check if array is sequential (0, 1, 2, ...).
     *
     * @param array<mixed> $array
     */
    private function isSequentialArray(array $array): bool
    {
        if ([] === $array) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    /** Format a value for YAML output. */
    private function formatValue(mixed $value): string
    {
        if (null === $value) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value)) {
            // Quote strings that contain special characters
            if ($this->needsQuoting($value)) {
                return '"' . addslashes($value) . '"';
            }

            return $value;
        }

        return (string)$value;
    }

    /** Check if a string needs quoting in YAML. */
    private function needsQuoting(string $value): bool
    {
        // Quote if contains special YAML characters
        return preg_match('/[:\[\]{},&*#?|\-<>=!%@`]/', $value) === 1
            || str_starts_with($value, ' ')
            || str_ends_with($value, ' ')
            || str_contains($value, "\n");
    }

    public function getContentType(): string
    {
        return 'application/x-yaml';
    }

    public function getFileExtension(): string
    {
        return 'yaml';
    }
}
