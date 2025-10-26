<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

/**
 * SELECT Operator - Select only specific fields from items.
 *
 * Configuration: ['field1', 'field2', ...]
 *
 * Example:
 *   SELECT => ['name', 'email']
 *   Result: Only 'name' and 'email' fields are kept in each item
 */
final class SelectOperator extends AbstractOperator
{
    public function getName(): string
    {
        return 'SELECT';
    }

    protected function getConfigSchema(): array
    {
        return [];
    }

    protected function handle(mixed $actualValue, OperatorContext $context): bool
    {
        return true;
    }

    public function apply(array $items, mixed $config, OperatorContext $context): array
    {
        if (!is_array($config)) {
            return $items;
        }

        $fields = $config;

        return array_map(function($item) use ($fields) {
            if (!is_array($item)) {
                return $item;
            }

            $result = [];
            foreach ($fields as $field) {
                if ((is_string($field) || is_int($field)) && array_key_exists($field, $item)) {
                    $result[$field] = $item[$field];
                }
            }

            return $result;
        }, $items);
    }
}
