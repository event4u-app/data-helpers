<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Hook;

use event4u\DataHelpers\DataMapper\Context\HookContext;

/**
 * Hook that receives only a HookContext.
 *
 * Typical usages are non-value hooks like beforeAll/afterAll/beforeEntry/afterEntry
 * where no value or target is transformed.
 */
interface ContextHookInterface extends MapperHookInterface
{
    public function __invoke(HookContext $context): mixed;
}
