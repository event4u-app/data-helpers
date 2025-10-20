<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO\Transformers\TransformerInterface;
use event4u\DataHelpers\SimpleDTO\Transformers\TransformerPipeline;

/**
 * Trait for transformer support.
 *
 * This trait provides methods to transform data before or after DTO operations.
 * Transformers can be used to normalize data, add computed fields, or apply business logic.
 *
 * Example:
 *   $user = UserDTO::fromArray($data)
 *       ->transformWith(new TrimStringsTransformer())
 *       ->transformWith(new LowercaseEmailTransformer());
 *
 *   // Or use a pipeline
 *   $pipeline = new TransformerPipeline();
 *   $pipeline->pipe(new TrimStringsTransformer());
 *   $pipeline->pipe(new LowercaseEmailTransformer());
 *   $user = UserDTO::fromArray($data)->transformWith($pipeline);
 */
trait SimpleDTOTransformerTrait
{
    /**
     * Transform the DTO data using a transformer.
     *
     * This creates a new DTO instance with transformed data.
     */
    public function transformWith(TransformerInterface|TransformerPipeline $transformer): static
    {
        $data = $this->toArray();

        if ($transformer instanceof TransformerPipeline) {
            $data = $transformer->process($data);
        } else {
            $data = $transformer->transform($data);
        }

        return static::fromArray($data);
    }

    /**
     * Transform data before creating a DTO.
     *
     * This is a static method that transforms data before DTO creation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArrayWithTransformer(
        array $data,
        TransformerInterface|TransformerPipeline $transformer
    ): static {
        if ($transformer instanceof TransformerPipeline) {
            $data = $transformer->process($data);
        } else {
            $data = $transformer->transform($data);
        }

        return static::fromArray($data);
    }

    /**
     * Define transformers to apply before DTO creation.
     *
     * Override this method to define default transformers for your DTO.
     *
     * @return array<TransformerInterface>
     */
    protected function beforeTransformers(): array
    {
        return [];
    }

    /**
     * Define transformers to apply after DTO serialization.
     *
     * Override this method to define default transformers for your DTO.
     *
     * @return array<TransformerInterface>
     */
    protected function afterTransformers(): array
    {
        return [];
    }
}

