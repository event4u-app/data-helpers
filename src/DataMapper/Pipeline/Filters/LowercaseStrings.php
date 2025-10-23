<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Converts all string values to lowercase.
 *
 * Example:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipeline([LowercaseStrings::class])->map()->getTarget();
 *   Template: {{ value | lower }} or {{ value | lowercase }}
 */
final class LowercaseStrings implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        return is_string($value) ? strtolower($value) : $value;
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
        return ['lower', 'lowercase'];
    }
}
