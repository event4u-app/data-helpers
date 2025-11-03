<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Normalizers;

/**
 * Normalizer that applies default values to missing fields.
 *
 * This normalizer ensures that fields have default values if they are missing.
 */
class DefaultValuesNormalizer implements NormalizerInterface
{
    /** @param array<string, mixed> $defaults Map of field names to default values */
    public function __construct(
        private readonly array $defaults = []
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function normalize(array $data): array
    {
        foreach ($this->defaults as $field => $defaultValue) {
            if (!isset($data[$field])) {
                $data[$field] = $defaultValue;
            }
        }

        return $data;
    }
}
