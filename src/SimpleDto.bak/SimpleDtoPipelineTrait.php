<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto;

use event4u\DataHelpers\SimpleDto\Pipeline\DtoPipeline;

/**
 * Trait for pipeline support.
 *
 * This trait provides methods to process data through a pipeline.
 *
 * Example:
 *   $pipeline = new DtoPipeline();
 *   $pipeline->addStage(new NormalizerStage(new TypeNormalizer(['age' => 'int'])));
 *   $pipeline->addStage(new TransformerStage(new TrimStringsTransformer()));
 *   $user = UserDto::fromArrayWithPipeline($data, $pipeline);
 */
trait SimpleDtoPipelineTrait
{
    /**
     * Create a Dto from array with pipeline processing.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArrayWithPipeline(
        array $data,
        DtoPipeline $pipeline
    ): static {
        $data = $pipeline->process($data);

        return static::fromArray($data);
    }

    /**
     * Process the Dto data through a pipeline.
     *
     * This creates a new Dto instance with processed data.
     */
    public function processWith(DtoPipeline $pipeline): static
    {
        $data = $this->toArray();
        $data = $pipeline->process($data);

        return static::fromArray($data);
    }
}
