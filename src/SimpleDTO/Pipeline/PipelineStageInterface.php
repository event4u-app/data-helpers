<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Pipeline;
use Exception;

/**
 * Interface for pipeline stages.
 *
 * A pipeline stage processes data and can modify it or throw exceptions.
 */
interface PipelineStageInterface
{
    /**
     * Process the data through this stage.
     *
     * @param array<string, mixed> $data The data to process
     * @return array<string, mixed> The processed data
     * @throws Exception If the stage fails
     */
    public function process(array $data): array;

    /** Get the name of this stage. */
    public function getName(): string;
}
