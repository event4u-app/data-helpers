<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline;

use event4u\DataHelpers\DataMapper\Context\HookContext;

interface TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed;
    public function getHook(): string;
    public function getFilter(): ?string;
}
