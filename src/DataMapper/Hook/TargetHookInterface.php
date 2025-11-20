<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Hook;

use event4u\DataHelpers\DataMapper\Context\HookContext;

/**
 * Hook that can mutate the target after a value has been written.
 *
 * Typical usage: afterWrite hooks that adjust the target object or array
 * based on the written value and mapping context.
 */
interface TargetHookInterface extends MapperHookInterface
{
    public function __invoke(mixed $target, HookContext $context, mixed $writtenValue): mixed;
}
