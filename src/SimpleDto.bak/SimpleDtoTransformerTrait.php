<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto\Transformers\TransformerInterface;
use event4u\DataHelpers\SimpleDto\Transformers\TransformerPipeline;

/**
 * Trait for transformer support.
 *
 * This trait provides methods to transform data before or after Dto operations.
 * Transformers can be used to normalize data, add computed fields, or apply business logic.
 *
 * Example:
 *   $user = UserDto::fromArray($data)
 *       ->transformWith(new TrimStringsTransformer())
 *       ->transformWith(new LowercaseEmailTransformer());
 *
 *   // Or use a pipeline
 *   $pipeline = new TransformerPipeline();
 *   $pipeline->add(new TrimStringsTransformer());
 *   $pipeline->add(new LowercaseEmailTransformer());
 *   $user = UserDto::fromArray($data)->transformWith($pipeline);
 */
trait SimpleDtoTransformerTrait
{
    /**
     * Transform the Dto data using a transformer.
     *
     * This creates a new Dto instance with transformed data.
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
     * Transform data before creating a Dto.
     *
     * This is a static method that transforms data before Dto creation.
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
     * Define transformers to apply before Dto creation.
     *
     * Override this method to define default transformers for your Dto.
     *
     * @return array<TransformerInterface>
     */
    protected function beforeTransformers(): array
    {
        return [];
    }

    /**
     * Define transformers to apply after Dto serialization.
     *
     * Override this method to define default transformers for your Dto.
     *
     * @return array<TransformerInterface>
     */
    protected function afterTransformers(): array
    {
        return [];
    }
}
