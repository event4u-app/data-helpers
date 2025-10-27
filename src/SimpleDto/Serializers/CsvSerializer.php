<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Serializers;

/**
 * CSV serializer for Dtos.
 *
 * Converts Dto data to CSV format.
 * Works best with flat data structures or collections of Dtos.
 *
 * Example:
 *   $user = UserDto::fromArray(['name' => 'John', 'age' => 30]);
 *   $csv = $user->toCsv();
 *   // Result: name,age\nJohn,30
 */
class CsvSerializer implements SerializerInterface
{
    public function __construct(
        private readonly bool $includeHeaders = true,
        private readonly string $delimiter = ',',
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\',
    ) {}

    /**
     * Serialize data to CSV.
     *
     * @param array<string, mixed> $data
     */
    public function serialize(array $data): string
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
        $first = reset($data);

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
}
