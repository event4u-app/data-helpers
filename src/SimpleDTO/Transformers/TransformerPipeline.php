<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Transformers;

/**
 * Pipeline for chaining multiple transformers.
 *
 * Allows you to apply multiple transformations in sequence.
 *
 * Example:
 *   $pipeline = new TransformerPipeline();
 *   $pipeline->pipe(new TrimStringsTransformer());
 *   $pipeline->pipe(new LowercaseEmailTransformer());
 *   $data = $pipeline->process($data);
 */
class TransformerPipeline
{
    /** @var array<TransformerInterface> */
    private array $transformers = [];

    /** Add a transformer to the pipeline. */
    public function pipe(TransformerInterface $transformer): self
    {
        $this->transformers[] = $transformer;

        return $this;
    }

    /**
     * Process data through all transformers.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function process(array $data): array
    {
        foreach ($this->transformers as $transformer) {
            $data = $transformer->transform($data);
        }

        return $data;
    }

    /**
     * Get all transformers in the pipeline.
     *
     * @return array<TransformerInterface>
     */
    public function getTransformers(): array
    {
        return $this->transformers;
    }

    /** Clear all transformers from the pipeline. */
    public function clear(): self
    {
        $this->transformers = [];

        return $this;
    }
}
