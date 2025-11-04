<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Transformers;

/**
 * Interface for data transformers.
 *
 * Transformers modify data before or after Dto creation/serialization.
 * They can be used to normalize data, add computed fields, or apply business logic.
 */
interface TransformerInterface
{
    /**
     * Transform the data.
     *
     * @param array<string, mixed> $data The data to transform
     * @return array<string, mixed> The transformed data
     */
    public function transform(array $data): array;
}
