<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Casts values to booleans.
 *
 * When used with pipe syntax (|bool or |boolean), always casts values to booleans.
 * Converts: '1', 'true', 'yes', 'on' -> true
 *          '0', 'false', 'no', 'off', '' -> false
 *
 * Example:
 *   Template: ['result' => 'value|bool']
 *   DataMapper::source($source)->target($target)->template($mapping)->pipeline([CastToBoolean::class])->map()->getTarget();
 */
final class CastToBoolean implements FilterInterface
{
    private const TRUE_VALUES = ['1', 'true', 'yes', 'on', 1, true];
    private const FALSE_VALUES = ['0', 'false', 'no', 'off', '', 0, false];

    public function transform(mixed $value, HookContext $context): mixed
    {
        // Skip null values
        if (null === $value) {
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

    public function getFilter(): string
    {
        return 'bool';
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['bool', 'boolean'];
    }
}
