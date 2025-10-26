<?php

declare(strict_types=1);

namespace Tests\utils\Docu;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * Example pipeline filter that trims whitespace from string values.
 * Used in documentation examples.
 */
class TrimStrings implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    public function getHook(): string
    {
        return 'afterTransform';
    }

    public function getFilter(): ?string
    {
        return null;
    }

    public function getAliases(): array
    {
        return ['trim'];
    }
}
