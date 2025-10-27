<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataFilter\Operators;

use event4u\DataHelpers\DataAccessor;

/**
 * Abstract base class for operators.
 *
 * Provides common functionality for value resolution in both wildcard and direct modes.
 * Uses Template Method Pattern with context-based value resolution.
 *
 * Operators define their config schema (e.g., ['min', 'max'] for BETWEEN),
 * and AbstractOperator handles all iteration and value resolution.
 * Operators only implement the final comparison logic in handle().
 */
abstract class AbstractOperator implements OperatorInterface
{
    /**
     * Get the configuration schema for this operator.
     *
     * Defines which keys the operator expects in its config.
     * For example: ['min', 'max'] for BETWEEN, ['pattern'] for LIKE.
     *
     * @return array<int, string> Array of config keys
     */
    abstract protected function getConfigSchema(): array;

    /**
     * Apply the operator to items (Template Method).
     *
     * This method handles all iteration and value resolution.
     * Subclasses can override this for complex cases (e.g., ORDER BY, LIMIT),
     * or just implement handle() for simple filtering.
     *
     * @param array<int|string, mixed> $items Items to process
     * @param mixed $config Operator configuration
     * @param OperatorContext $context Context for resolution
     * @return array<int|string, mixed> Processed items
     */
    public function apply(array $items, mixed $config, OperatorContext $context): array
    {
        if ([] === $items || !is_array($config)) {
            return $items;
        }

        $schema = $this->getConfigSchema();
        $filtered = [];

        foreach ($items as $index => $item) {
            $matches = true;

            foreach ($config as $fieldPath => $operatorConfig) {
                // Resolve actual value from item
                $actualValue = $this->resolveFieldValue($fieldPath, $index, $item, $context);

                // Prepare context with resolved operator-specific values
                $opContext = $this->prepareOperatorContext($operatorConfig, $schema, $context);

                // Call operator-specific logic
                if (!$this->handle($actualValue, $opContext)) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                $filtered[$index] = $item;
            }
        }

        return $filtered;
    }

    /**
     * Prepare operator context with resolved values.
     *
     * Takes the operator config and schema, resolves all values,
     * and creates a new context with those values.
     *
     * @param mixed $operatorConfig Operator configuration (can be array or single value)
     * @param array<int, string> $schema Config schema (keys to extract)
     * @param OperatorContext $baseContext Base context for resolution
     * @return OperatorContext New context with resolved values
     */
    protected function prepareOperatorContext(
        mixed $operatorConfig,
        array $schema,
        OperatorContext $baseContext
    ): OperatorContext {
        $values = [];

        // If schema is empty, no values to prepare
        if ([] === $schema) {
            return $baseContext->withValues($values);
        }

        // If config is an array and not associative
        if (is_array($operatorConfig) && !$this->isAssociativeArray($operatorConfig)) {
            // If schema has only one key, treat entire array as value for that key
            // Example: whereIn('role', ['admin', 'moderator']) -> values = ['admin', 'moderator']
            if (1 === count($schema)) {
                $key = $schema[0];
                $values[$key] = $this->resolveConfigValue($operatorConfig, $baseContext);

                return $baseContext->withValues($values);
            }

            // If schema has multiple keys, map values by position to schema keys
            // Example: between('price', 50, 150) -> min = 50, max = 150
            foreach ($schema as $index => $key) {
                $configValue = $operatorConfig[$index] ?? null;
                $values[$key] = $this->resolveConfigValue($configValue, $baseContext);
            }

            return $baseContext->withValues($values);
        }

        // For associative arrays or non-array configs
        foreach ($schema as $key) {
            // Extract value from config
            if (is_array($operatorConfig)) {
                $configValue = $operatorConfig[$key] ?? null;
            } else {
                // For single-value configs, use the value for the first schema key
                $configValue = [] === $values ? $operatorConfig : null;
            }

            // Resolve value (handles template expressions)
            $values[$key] = $this->resolveConfigValue($configValue, $baseContext);
        }

        return $baseContext->withValues($values);
    }

    /**
     * Check if an array is associative (has string keys).
     *
     * @param array<int|string, mixed> $array Array to check
     */
    private function isAssociativeArray(array $array): bool
    {
        if ([] === $array) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Handle the operator-specific comparison logic.
     *
     * This is the hook method that subclasses must implement.
     * It receives the actual value and a context with all operator-specific values resolved.
     *
     * @param mixed $actualValue Actual value from item
     * @param OperatorContext $context Context with operator-specific values (use $context->getValue('key'))
     * @return bool True if item matches, false otherwise
     */
    abstract protected function handle(mixed $actualValue, OperatorContext $context): bool;
    /**
     * Resolve a value from a field path.
     *
     * In wildcard mode: Resolves template expressions like {{ products.*.price }}
     * In direct mode: Gets value from item array using field name
     *
     * @param string $fieldPath Field path or template expression
     * @param int|string $index Current item index
     * @param mixed $item Current item data
     * @param OperatorContext $context Context for resolution
     */
    protected function resolveFieldValue(
        string $fieldPath,
        int|string $index,
        mixed $item,
        OperatorContext $context
    ): mixed {
        if ($context->isWildcardMode) {
            return $this->resolveWildcardValue($fieldPath, $index, $context);
        }

        return $this->resolveDirectValue($fieldPath, $item);
    }

    /**
     * Resolve value in wildcard mode (template expressions).
     *
     * @param string $fieldPath Template expression like {{ products.*.price }}
     * @param int|string $index Current item index
     * @param OperatorContext $context Context with source/target data
     */
    protected function resolveWildcardValue(string $fieldPath, int|string $index, OperatorContext $context): mixed
    {
        // Replace * with current index
        $actualFieldPath = str_replace('*', (string)$index, $fieldPath);

        // Check if it's a template expression
        if (str_starts_with($actualFieldPath, '{{') && str_ends_with($actualFieldPath, '}}')) {
            // Extract path from template
            $path = trim(substr($actualFieldPath, 2, -2));

            // Remove filters if present
            if (str_contains($path, '|')) {
                [$path] = explode('|', $path, 2);
                $path = trim($path);
            }

            // Try to get value from source first
            $accessor = new DataAccessor($context->source);
            $result = $accessor->get($path);

            // If not found in source, try target
            if (null === $result && null !== $context->target) {
                $accessor = new DataAccessor($context->target);
                $result = $accessor->get($path);
            }

            return $result;
        }

        // Return literal value
        return $actualFieldPath;
    }

    /**
     * Resolve value in direct mode (field names).
     *
     * @param string $fieldPath Field name like 'price' or 'user.name'
     * @param mixed $item Current item (array, object, Dto, Model)
     */
    protected function resolveDirectValue(string $fieldPath, mixed $item): mixed
    {
        // Use DataAccessor to handle arrays, objects, Dtos, Models
        $accessor = new DataAccessor($item);

        return $accessor->get($fieldPath);
    }

    /**
     * Resolve a configuration value (can be template expression or literal).
     *
     * @param mixed $value Value to resolve
     * @param OperatorContext $context Context for resolution
     */
    protected function resolveConfigValue(mixed $value, OperatorContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Check if it's a template expression
        if (str_starts_with($value, '{{') && str_ends_with($value, '}}')) {
            // Extract path from template
            $path = trim(substr($value, 2, -2));

            // Remove filters if present
            if (str_contains($path, '|')) {
                [$path] = explode('|', $path, 2);
                $path = trim($path);
            }

            // Try to get value from source first
            if (null !== $context->source) {
                $accessor = new DataAccessor($context->source);
                $result = $accessor->get($path);

                if (null !== $result) {
                    return $result;
                }
            }

            // Try target
            if (null !== $context->target) {
                $accessor = new DataAccessor($context->target);
                $result = $accessor->get($path);

                if (null !== $result) {
                    return $result;
                }
            }
        }

        // Return literal value
        return $value;
    }

    /**
     * Compare two values using an operator.
     *
     * @param mixed $actualValue Actual value from item
     * @param string $operator Comparison operator (=, !=, >, <, >=, <=, IN, NOT IN, LIKE, NOT LIKE)
     * @param mixed $expectedValue Expected value
     */
    protected function compareValues(mixed $actualValue, string $operator, mixed $expectedValue): bool
    {
        $operator = strtoupper(trim($operator));

        return match ($operator) {
            '=', '==' => $actualValue == $expectedValue,
            '!=', '<>', '!==' => $actualValue != $expectedValue,
            '>' => is_numeric($actualValue) && is_numeric($expectedValue) && $actualValue > $expectedValue,
            '>=' => is_numeric($actualValue) && is_numeric($expectedValue) && $actualValue >= $expectedValue,
            '<' => is_numeric($actualValue) && is_numeric($expectedValue) && $actualValue < $expectedValue,
            '<=' => is_numeric($actualValue) && is_numeric($expectedValue) && $actualValue <= $expectedValue,
            'IN' => is_array($expectedValue) && in_array($actualValue, $expectedValue, true),
            'NOT IN' => is_array($expectedValue) && !in_array($actualValue, $expectedValue, true),
            default => false,
        };
    }

    public function getAliases(): array
    {
        return [];
    }
}
