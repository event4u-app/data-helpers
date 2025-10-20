<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Transformers;

/**
 * Transformer that trims all string values.
 *
 * Removes whitespace from the beginning and end of all string values.
 */
class TrimStringsTransformer implements TransformerInterface
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function transform(array $data): array
    {
        return $this->trimRecursive($data);
    }

    /**
     * Trim strings recursively.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function trimRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            } elseif (is_array($value)) {
                /** @var array<string, mixed> $value */
                $data[$key] = $this->trimRecursive($value);
            }
        }

        return $data;
    }
}

