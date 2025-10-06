<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Casts numeric values to floats.
 *
 * Applies to fields containing 'price', 'amount', 'total', 'rate', or 'percentage' in the path.
 * Skips null values and non-numeric strings.
 *
 * Example:
 *   DataMapper::pipe([CastToFloat::class])->map($source, $target, $mapping);
 */
final class CastToFloat implements TransformerInterface
{
    private const PATTERNS = ['price', 'amount', 'total', 'rate', 'percentage', 'cost', 'fee'];

    public function transform(mixed $value, HookContext $context): mixed
    {
        // Skip null values
        if (null === $value) {
            return $value;
        }

        // Check if path matches float patterns
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

        // Cast to float if numeric
        if (is_numeric($value)) {
            return (float) $value;
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
}

