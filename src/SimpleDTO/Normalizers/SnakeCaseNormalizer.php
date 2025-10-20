<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Normalizers;

/**
 * Normalizer that converts keys to snake_case.
 *
 * Useful for normalizing data from camelCase APIs to snake_case DTOs.
 */
class SnakeCaseNormalizer implements NormalizerInterface
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function normalize(array $data): array
    {
        return $this->convertKeysRecursive($data);
    }

    /**
     * Convert keys to snake_case recursively.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function convertKeysRecursive(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $newKey = is_string($key) ? $this->toSnakeCase($key) : $key;

            if (is_array($value)) {
                $result[$newKey] = $this->convertKeysRecursive($value);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /** Convert a string to snake_case. */
    private function toSnakeCase(string $string): string
    {
        // Insert underscore before uppercase letters
        $string = preg_replace('/([a-z])([A-Z])/', '$1_$2', $string);

        // Convert to lowercase
        return strtolower((string)$string);
    }
}

