<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Normalizers;

/**
 * Interface for data normalizers.
 *
 * Normalizers ensure data is in the correct format and type.
 * They differ from transformers in that they focus on type safety and data consistency.
 */
interface NormalizerInterface
{
    /**
     * Normalize the data.
     *
     * @param array<string, mixed> $data The data to normalize
     * @return array<string, mixed> The normalized data
     */
    public function normalize(array $data): array;
}
