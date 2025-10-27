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
 * - Boolean false - enable with convertFalse: true
 *
 * Useful for database operations where empty values should be stored as NULL.
 *
 * Examples:
 *   Pipeline: DataMapper::source($source)->target($target)->template($mapping)->pipeline([new ConvertEmptyToNull()])->map()->getTarget();
 *   Template: {{ value | empty_to_null }}
 *   Template: {{ value | empty_to_null:"zero" }}
 *   Template: {{ value | empty_to_null:"string_zero" }}
 *   Template: {{ value | empty_to_null:"false" }}
 *   Template: {{ value | empty_to_null:"zero,string_zero,false" }}
 */
final readonly class ConvertEmptyToNull implements FilterInterface
{
    public function __construct(
        private bool $convertZero = false,
        private bool $convertStringZero = false,
        private bool $convertFalse = false,
    ) {}

    public function transform(mixed $value, HookContext $context): mixed
    {
        // Get parameters from context args (from filter syntax) or constructor
        $args = $context->extra();
        $convertZero = $this->convertZero;
        $convertStringZero = $this->convertStringZero;
        $convertFalse = $this->convertFalse;

        // Parse parameters from template filter syntax
        // Supports: {{ value | empty_to_null:"zero" }}
        //           {{ value | empty_to_null:"string_zero" }}
        //           {{ value | empty_to_null:"false" }}
        //           {{ value | empty_to_null:"zero,string_zero,false" }}
        if (count($args) >= 1 && is_string($args[0])) {
            $options = $this->parseOptions($args[0]);
            $convertZero = $options['zero'];
            $convertStringZero = $options['string_zero'];
            $convertFalse = $options['false'];
        }

        // Handle null
        if (null === $value) {
            return null;
        }

        // Handle empty string
        if ('' === $value) {
            return null;
        }

        // Handle empty array
        if (is_array($value) && [] === $value) {
            return null;
        }

        // Handle integer zero (optional)
        if ($convertZero && 0 === $value) {
            return null;
        }

        // Handle string zero (optional)
        if ($convertStringZero && '0' === $value) {
            return null;
        }

        // Handle boolean false (optional)
        if ($convertFalse && false === $value) {
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
     * - "false" → convertFalse: true
     * - "zero,string_zero,false" → all true
     *
     * @return array{zero: bool, string_zero: bool, false: bool}
     */
    private function parseOptions(string $options): array
    {
        $result = [
            'zero' => false,
            'string_zero' => false,
            'false' => false,
        ];

        // Split by comma and trim
        $parts = array_map('trim', explode(',', strtolower($options)));

        foreach ($parts as $part) {
            if ('zero' === $part) {
                $result['zero'] = true;
            } elseif ('string_zero' === $part) {
                $result['string_zero'] = true;
            } elseif ('false' === $part) {
                $result['false'] = true;
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
