<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Normalizers;

/**
 * Normalizer that converts keys to camelCase.
 *
 * Useful for normalizing data from snake_case APIs to camelCase DTOs.
 */
class CamelCaseNormalizer implements NormalizerInterface
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
     * Convert keys to camelCase recursively.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function convertKeysRecursive(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $newKey = is_string($key) ? $this->toCamelCase($key) : $key;

            if (is_array($value)) {
                $result[$newKey] = $this->convertKeysRecursive($value);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Convert a string to camelCase.
     */
    private function toCamelCase(string $string): string
    {
        // Convert snake_case to camelCase
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);

        // Make first letter lowercase
        return lcfirst($string);
    }
}

