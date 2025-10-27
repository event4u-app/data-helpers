<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Converts empty values to null.
 *
 * By default, converts:
 * - Empty strings ("") to null
 * - Empty arrays ([]) to null
 * - null to null
 *
 * Optional conversions (disabled by default):
 * - Integer zero (0) - enable with convertZero: true
 * - String zero ("0") - enable with convertStringZero: true
 *
 * Note: Boolean false is NEVER converted to null.
 *
 * Useful for database operations where empty values should be stored as NULL.
 *
 * Examples:
 *   Pipeline: DataMapper::source($source)->target($target)->template($mapping)->pipeline([new ConvertEmptyToNull()])->map()->getTarget();
 *   Template: {{ value | empty_to_null }}
 *   Template: {{ value | empty_to_null:"zero" }}
 *   Template: {{ value | empty_to_null:"string_zero" }}
 *   Template: {{ value | empty_to_null:"zero,string_zero" }}
 */
final class ConvertEmptyToNull implements FilterInterface
{
    public function __construct(
        private readonly bool $convertZero = false,
        private readonly bool $convertStringZero = false,
    ) {}

    public function transform(mixed $value, HookContext $context): mixed
    {
        // Get parameters from context args (from filter syntax) or constructor
        $args = $context->extra();
        $convertZero = $this->convertZero;
        $convertStringZero = $this->convertStringZero;

        // Parse parameters from template filter syntax
        // Supports: {{ value | empty_to_null:"zero" }}
        //           {{ value | empty_to_null:"string_zero" }}
        //           {{ value | empty_to_null:"zero,string_zero" }}
        if (count($args) >= 1 && is_string($args[0])) {
            $options = $this->parseOptions($args[0]);
            $convertZero = $options['zero'];
            $convertStringZero = $options['string_zero'];
        }

        // Don't convert boolean false to null
        if (is_bool($value)) {
            return $value;
        }

        // Handle null
        if ($value === null) {
            return null;
        }

        // Handle empty string
        if ($value === '') {
            return null;
        }

        // Handle empty array
        if (is_array($value) && count($value) === 0) {
            return null;
        }

        // Handle integer zero (optional)
        if ($convertZero && $value === 0) {
            return null;
        }

        // Handle string zero (optional)
        if ($convertStringZero && $value === '0') {
            return null;
        }

        return $value;
    }

    /**
     * Parse options from string parameter.
     *
     * Accepts:
     * - "zero" → convertZero: true
     * - "string_zero" → convertStringZero: true
     * - "zero,string_zero" → both true
     *
     * @return array{zero: bool, string_zero: bool}
     */
    private function parseOptions(string $options): array
    {
        $result = [
            'zero' => false,
            'string_zero' => false,
        ];

        // Split by comma and trim
        $parts = array_map('trim', explode(',', strtolower($options)));

        foreach ($parts as $part) {
            if ($part === 'zero') {
                $result['zero'] = true;
            } elseif ($part === 'string_zero') {
                $result['string_zero'] = true;
            }
        }

        return $result;
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
        return ['empty_to_null'];
    }
}
