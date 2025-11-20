<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Hook;

use event4u\DataHelpers\DataMapper\Context\HookContext;

/**
 * Hook that transforms a value during the mapping process.
 *
 * Typical usages: beforeTransform, afterTransform, beforeWrite.
 */
interface ValueHookInterface extends MapperHookInterface
{
    public function __invoke(mixed $value, HookContext $context): mixed;
}
