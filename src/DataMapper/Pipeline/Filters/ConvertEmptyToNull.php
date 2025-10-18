<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Converts empty strings to null.
 *
 * Useful for database operations where empty strings should be stored as NULL.
 *
 * Examples:
 *   Pipeline: DataMapper::source($source)->target($target)->template($mapping)->pipe([new ConvertEmptyToNull()])->map()->getTarget();
 *   Template: {{ value | empty_to_null }}
 */
final class ConvertEmptyToNull implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return '' === $value ? null : $value;
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
        return ['empty_to_null'];
    }
}

