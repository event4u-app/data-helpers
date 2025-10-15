<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Removes duplicate values from an array.
 *
 * Example:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipe([Unique::class])->map()->getTarget();
 *   Template: {{ value | unique }}
 */
final class Unique implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_array($value) ? array_unique($value) : $value;
    }

    public function getHook(): string
    {
        return DataMapperHook::BeforeTransform->value;
    }

    public function getFilter(): ?string
    {
        return null;
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['unique'];
    }
}

