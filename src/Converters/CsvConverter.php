<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Converters;

use InvalidArgumentException;

/**
 * CSV converter for bidirectional conversion between arrays and CSV strings.
 *
 * Works best with flat data structures or collections of flat arrays.
 *
 * Example:
 *   $converter = new CsvConverter();
 *   $array = $converter->toArray("name,age\nJohn,30");
 *   $csv = $converter->fromArray(['name' => 'John', 'age' => 30]);
 */
class CsvConverter implements ConverterInterface
{
    public function __construct(
        private readonly bool $includeHeaders = true,
        private readonly string $delimiter = ',',
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\',
    ) {}

    /**
     * Convert CSV string to array.
     *
     * @param string $data CSV string
     * @return array<string, mixed>
     * @throws InvalidArgumentException If CSV is invalid
     */
    public function toArray(string $data): array
    {
        if (empty($data)) {
            return [];
        }

        $lines = explode("\n", trim($data));
        if ([] === $lines) {
            return [];
        }

        $headers = null;
        $result = [];

        foreach ($lines as $index => $line) {
            if (empty(trim($line))) {
                continue;
            }

            $row = str_getcsv($line, $this->delimiter, $this->enclosure, $this->escape);

            if (0 === $index && $this->includeHeaders) {
                $headers = $row;
                continue;
            }

            if (null !== $headers) {
                // Associative array with headers
                $assoc = [];
                foreach ($row as $i => $value) {
                    $key = $headers[$i] ?? $i;
                    $assoc[$key] = $this->parseValue($value ?? '');
                }
                $result[] = $assoc;
            } else {
                // Numeric array without headers
                $mapped = [];
                foreach ($row as $value) {
                    $mapped[] = $this->parseValue($value ?? '');
                }
                $result[] = $mapped;
            }
        }

        // If only one row, return it directly (not wrapped in array)
        if (1 === count($result)) {
            // @phpstan-ignore-next-line
            return $result[0];
        }

        // @phpstan-ignore-next-line
        return $result;
    }

    /** Parse a CSV value to its PHP type. */
    private function parseValue(string $value): mixed
    {
        $value = trim($value);

        // Empty string
        if ('' === $value) {
            return null;
        }

        // Boolean
        if ('true' === strtolower($value)) {
            return true;
        }
        if ('false' === strtolower($value)) {
            return false;
        }

        // Number
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        return $value;
    }

    /**
     * Convert array to CSV string.
     *
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data): string
    {
        // Check if data is a collection (array of arrays)
        if ($this->isCollection($data)) {
            /** @var array<int, array<string, mixed>> $data */
            return $this->serializeCollection($data);
        }

        // Single row
        return $this->serializeSingleRow($data);
    }

    /**
     * Check if data is a collection.
     *
     * @param array<string, mixed> $data
     */
    private function isCollection(array $data): bool
    {
        if ([] === $data) {
            return false;
        }

        // Check if first element is an array
        $first = reset($data);

        return is_array($first);
    }

    /**
     * Serialize a collection of rows.
     *
     * @param array<int, array<string, mixed>> $data
     */
    private function serializeCollection(array $data): string
    {
        if ([] === $data) {
            return '';
        }

        $output = '';

        // Flatten nested arrays
        $flatData = array_map($this->flattenArray(...), $data);

        // Get headers from first row
        $headers = array_keys($flatData[0]);

        // Add headers
        if ($this->includeHeaders) {
            $output .= $this->formatRow($headers) . PHP_EOL;
        }

        // Add rows
        foreach ($flatData as $row) {
            $output .= $this->formatRow(array_values($row)) . PHP_EOL;
        }

        return rtrim($output, PHP_EOL);
    }

    /**
     * Serialize a single row.
     *
     * @param array<string, mixed> $data
     */
    private function serializeSingleRow(array $data): string
    {
        $flat = $this->flattenArray($data);
        $output = '';

        // Add headers
        if ($this->includeHeaders) {
            $output .= $this->formatRow(array_keys($flat)) . PHP_EOL;
        }

        // Add values
        $output .= $this->formatRow(array_values($flat));

        return $output;
    }

    /**
     * Flatten nested arrays.
     *
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = '' === $prefix ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Format a row for CSV output.
     *
     * @param array<int, mixed> $row
     */
    private function formatRow(array $row): string
    {
        $formatted = [];

        foreach ($row as $value) {
            $formatted[] = $this->formatValue($value);
        }

        return implode($this->delimiter, $formatted);
    }

    /** Format a value for CSV output. */
    private function formatValue(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string)$value;

        // Check if value needs enclosure
        if ($this->needsEnclosure($value)) {
            // Escape enclosure characters
            $value = str_replace($this->enclosure, $this->escape . $this->enclosure, $value);

            return $this->enclosure . $value . $this->enclosure;
        }

        return $value;
    }

    /** Check if a value needs enclosure. */
    private function needsEnclosure(string $value): bool
    {
        return str_contains($value, $this->delimiter)
            || str_contains($value, $this->enclosure)
            || str_contains($value, "\n")
            || str_contains($value, "\r");
    }

    public function getContentType(): string
    {
        return 'text/csv';
    }

    public function getFileExtension(): string
    {
        return 'csv';
    }
}
