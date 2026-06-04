<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\DataMapper\Pipeline\ResolvesSourceArguments;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Divides a numeric value by a divisor.
 *
 * The divisor can be a fixed literal or a source path (resolved before the
 * filter runs, see ResolvesSourceArguments).
 *
 * Examples:
 *   Template: {{ duration.minutes | divide:60 }}            // minutes -> hours
 *   Template: {{ total.amount | divide:order.installments }} // divisor from source
 *
 * Non-numeric values, missing/non-numeric divisors, and division by zero are
 * returned unchanged.
 */
final class Divide implements FilterInterface, ResolvesSourceArguments
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

        // Avoid division by zero - return the value unchanged
        if (0.0 === (float)$args[0]) {
            return $value;
        }

        return $value / $args[0];
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
        return ['divide'];
    }
}
