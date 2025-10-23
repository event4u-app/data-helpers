<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * Interface for filter operators.
 *
 * Operators can be used in both DataFilter (post-mapping) and QueryBuilder (during mapping).
 */
interface OperatorInterface
{
    /**
     * Apply the operator to filter/transform items.
     *
     * @param array<int|string, mixed> $items Items to process
     * @param mixed $config Operator configuration
     * @param OperatorContext $context Context for value resolution
     * @return array<int|string, mixed> Processed items
     */
    public function apply(array $items, mixed $config, OperatorContext $context): array;

    /**
     * Get the operator name.
     *
     * @return string Operator name (e.g., 'WHERE', 'ORDER BY', 'BETWEEN')
     */
    public function getName(): string;

    /**
     * Get operator aliases.
     *
     * @return array<int, string> Alternative names for this operator
     */
    public function getAliases(): array;
}
