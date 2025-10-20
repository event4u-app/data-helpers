<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO;

use event4u\DataHelpers\SimpleDTO\Pipeline\DTOPipeline;

/**
 * Trait for pipeline support.
 *
 * This trait provides methods to process data through a pipeline.
 *
 * Example:
 *   $pipeline = new DTOPipeline();
 *   $pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int'])));
 *   $pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));
 *   $user = UserDTO::fromArrayWithPipeline($data, $pipeline);
 */
trait SimpleDTOPipelineTrait
{
    /**
     * Create a DTO from array with pipeline processing.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArrayWithPipeline(
        array $data,
        DTOPipeline $pipeline
    ): static {
        $data = $pipeline->process($data);

        return static::fromArray($data);
    }

    /**
     * Process the DTO data through a pipeline.
     *
     * This creates a new DTO instance with processed data.
     */
    public function processWith(DTOPipeline $pipeline): static
    {
        $data = $this->toArray();
        $data = $pipeline->process($data);

        return static::fromArray($data);
    }
}

