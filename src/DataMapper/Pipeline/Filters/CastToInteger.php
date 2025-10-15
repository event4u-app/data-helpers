<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Casts numeric values to integers.
 *
 * Applies to fields containing 'id', 'count', 'quantity', 'age', or 'year' in the path.
 * Skips null values and non-numeric strings.
 *
 * Example:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipe([CastToInteger::class])->map()->getTarget();
 */
final class CastToInteger implements FilterInterface
{
    private const PATTERNS = ['id', 'count', 'quantity', 'age', 'year', 'number'];

    public function transform(mixed $value, HookContext $context): mixed
    {
        // Skip null values
        if (null === $value) {
            return $value;
        }

        // Check if path matches integer patterns
        $srcPath = $context->srcPath();
        $tgtPath = $context->tgtPath();

        $shouldCast = false;
        foreach (self::PATTERNS as $pattern) {
            if (
                (null !== $srcPath && str_contains(strtolower($srcPath), $pattern))
                || (null !== $tgtPath && str_contains(strtolower($tgtPath), $pattern))
            ) {
                $shouldCast = true;
                break;
            }
        }

        if (!$shouldCast) {
            return $value;
        }

        // Cast to integer if numeric
        if (is_numeric($value)) {
            return (int)$value;
        }

        return $value;
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

