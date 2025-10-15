<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Removes null values from being written to target.
 *
 * Returns '__skip__' to prevent writing null values.
 *
 * Example:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipe([RemoveNullValues::class])->map()->getTarget();
 */
final class RemoveNullValues implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return $value ?? '__skip__';
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

