<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

final class SkipEmptyValues implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if ('' === $value || (is_array($value) && [] === $value)) {
            return '__skip__';
        }

        return $value;
    }

    public function getHook(): string
    {
        return DataMapperHook::BeforeWrite->value;
    }

    public function getFilter(): ?string
    {
        return null;
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return [];
    }
}
