<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline;

use event4u\DataHelpers\DataMapper\Context\HookContext;

/**
 * Represents a single step in the DataMapper pipeline that can transform values.
 *
 * This interface is intentionally small so that custom steps and filters can
 * implement it without depending on higher-level infrastructure.
 */
interface PipelineStepInterface
{
    /**
     * Transform a value during the mapping process.
     *
     * Implementations should not mutate the provided context but may use it for
     * read-only metadata (source/target paths, mode, etc.).
     */
    public function transform(mixed $value, HookContext $context): mixed;
}
