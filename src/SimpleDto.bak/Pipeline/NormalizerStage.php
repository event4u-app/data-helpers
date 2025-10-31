<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Pipeline;

use event4u\DataHelpers\SimpleDto\Normalizers\NormalizerInterface;

/**
 * Pipeline stage that applies a normalizer.
 */
class NormalizerStage implements PipelineStageInterface
{
    public function __construct(
        private readonly NormalizerInterface $normalizer,
        private readonly string $name = 'normalizer'
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function process(array $data): array
    {
        return $this->normalizer->normalize($data);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
