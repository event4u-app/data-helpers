<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Casts values to booleans.
 *
 * Applies to fields containing 'is_', 'has_', 'can_', 'should_', or 'active' in the path.
 * Converts: '1', 'true', 'yes', 'on' -> true
 *          '0', 'false', 'no', 'off', '' -> false
 *
 * Example:
 *   DataMapper::source($source)->target($target)->template($mapping)->pipe([CastToBoolean::class])->map()->getTarget();
 */
final class CastToBoolean implements FilterInterface
{
    private const PATTERNS = ['is_', 'has_', 'can_', 'should_', 'active', 'enabled', 'disabled'];

    private const TRUE_VALUES = ['1', 'true', 'yes', 'on', 1, true];
    private const FALSE_VALUES = ['0', 'false', 'no', 'off', '', 0, false];

    public function transform(mixed $value, HookContext $context): mixed
    {
        // Skip null values
        if (null === $value) {
            return $value;
        }

        // Check if path matches boolean patterns
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

        // Cast to boolean
        $normalized = is_string($value) ? strtolower(trim($value)) : $value;

        if (in_array($normalized, self::TRUE_VALUES, true)) {
            return true;
        }

        if (in_array($normalized, self::FALSE_VALUES, true)) {
            return false;
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

