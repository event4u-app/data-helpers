<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Converts all string values to uppercase.
 *
 * Example:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipe([UppercaseStrings::class])->map()->getTarget();
 *   Template: {{ value | upper }} or {{ value | uppercase }}
 */
final class UppercaseStrings implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_string($value) ? strtoupper($value) : $value;
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
        return ['upper', 'uppercase'];
    }
}
