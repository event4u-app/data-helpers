<?php

declare(strict_types=1);

namespace Tests\utils\Docu;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;

/**
 * Example pipeline filter that skips empty values.
 * Used in documentation examples.
 */
class SkipEmptyValues implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        // Return '__skip__' for empty values (they will be skipped by DataMapper)
        if (empty($value) && 0 !== $value && '0' !== $value && false !== $value) {
            return '__skip__';
        }

        return $value;
    }

    public function getHook(): string
    {
        return 'beforeWrite';
    }

    public function getFilter(): ?string
    {
        return null;
    }

    public function getAliases(): array
    {
        return ['skip_empty'];
    }
}
