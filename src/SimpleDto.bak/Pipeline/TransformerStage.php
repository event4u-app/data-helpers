<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Pipeline;

use event4u\DataHelpers\SimpleDto\Transformers\TransformerInterface;

/**
 * Pipeline stage that applies a transformer.
 */
class TransformerStage implements PipelineStageInterface
{
    public function __construct(
        private readonly TransformerInterface $transformer,
        private readonly string $name = 'transformer'
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function process(array $data): array
    {
        return $this->transformer->transform($data);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
