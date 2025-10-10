<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

final class LowercaseEmails implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $srcPath = $context->srcPath();
        $tgtPath = $context->tgtPath();

        if (
            (null !== $srcPath && str_contains(strtolower($srcPath), 'email'))
            || (null !== $tgtPath && str_contains(strtolower($tgtPath), 'email'))
        ) {
            return strtolower($value);
        }

        return $value;
    }

    public function getHook(): string
    {
        return 'preTransform';
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
