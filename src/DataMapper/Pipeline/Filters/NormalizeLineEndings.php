<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Normalizes line endings to Unix style (\n).
 *
 * Converts Windows (\r\n) and Mac (\r) line endings to Unix (\n).
 *
 * Example:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipe([NormalizeLineEndings::class])->map()->getTarget();
 */
final class NormalizeLineEndings implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Convert Windows and Mac line endings to Unix
        return str_replace(["\r\n", "\r"], "\n", $value);
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
        return [];
    }
}
