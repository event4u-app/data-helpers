<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Transformers;

/**
 * Transformer that converts all keys to lowercase.
 *
 * Useful for normalizing data from different sources.
 */
class LowercaseKeysTransformer implements TransformerInterface
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function transform(array $data): array
    {
        return $this->lowercaseKeysRecursive($data);
    }

    /**
     * Lowercase keys recursively.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function lowercaseKeysRecursive(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $newKey = is_string($key) ? strtolower($key) : $key;

            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $result[$newKey] = $this->lowercaseKeysRecursive($value);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
