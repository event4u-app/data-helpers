<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;
use Closure;

/**
 * Adapter to use DataFilter operators in Wildcard mode.
 *
 * Converts Wildcard operator signature to DataFilter operator signature.
 */
final class WildcardOperatorAdapter
{
    /**
     * Adapt a DataFilter operator for use in Wildcard mode.
     *
     * Wildcard signature: function(array $items, mixed $config, mixed $sources, array $aliases): array
     * DataFilter signature: function apply(array $items, mixed $config, OperatorContext $context): array
     *
     * @param OperatorInterface $operator DataFilter operator instance
     * @return Closure(array<int|string, mixed>, mixed, mixed, array<string, mixed>): array<int|string, mixed>
     */
    public static function adapt(OperatorInterface $operator): Closure
    {
        return function(array $items, mixed $config, mixed $sources, array $aliases) use ($operator): array {
            // Create OperatorContext for wildcard mode
            $context = new OperatorContext(
                $sources,
                $aliases,
                true,
                $items
            );

            // Call the operator's apply method
            return $operator->apply($items, $config, $context);
        };
    }
}

