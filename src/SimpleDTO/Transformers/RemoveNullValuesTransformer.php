<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Transformers;

/**
 * Transformer that removes null values from data.
 *
 * Useful for cleaning up data before serialization.
 */
class RemoveNullValuesTransformer implements TransformerInterface
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function transform(array $data): array
    {
        return $this->removeNullsRecursive($data);
    }

    /**
     * Remove null values recursively.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function removeNullsRecursive(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (null === $value) {
                continue;
            }

            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $result[$key] = $this->removeNullsRecursive($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
