<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\DataMapper\Pipeline\ResolvesSourceArguments;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Multiplies a numeric value by a factor.
 *
 * The factor can be a fixed literal or a source path (resolved before the
 * filter runs, see ResolvesSourceArguments).
 *
 * Examples:
 *   Template: {{ duration.hours | multiply:60 }}            // hours -> minutes
 *   Template: {{ price.net | multiply:order.taxFactor }}    // factor from source
 *
 * Non-numeric values and missing/non-numeric factors are returned unchanged.
 */
final class Multiply implements FilterInterface, ResolvesSourceArguments
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_numeric($value)) {
            return $value;
        }

        $args = $context->extra();
        if (!isset($args[0]) || !is_numeric($args[0])) {
            return $value;
        }

        return $value * $args[0];
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
        return ['multiply'];
    }
}
