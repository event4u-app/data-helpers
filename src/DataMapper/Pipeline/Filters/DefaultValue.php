<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Returns a default value if the input is null or blank.
 *
 * Examples:
 *   Pipeline: new DefaultValue('Unknown')
 *   Template: {{ value | default }}           // Returns '' if null/blank
 *   Template: {{ value | default:"Unknown" }} // Returns 'Unknown' if null/blank
 *   Template: {{ value | default:"0" }}       // Returns '0' (string) if null/blank
 *   Template: {{ value | default:0 }}         // Returns 0 if null/blank
 */
final readonly class DefaultValue implements FilterInterface
{
    /** @param mixed $defaultValue Default value to return if input is null/blank */
    public function __construct(
        private mixed $defaultValue = '',
    ) {
    }

    public function transform(mixed $value, HookContext $context): mixed
    {
        // If value is not null and not empty, return it
        if (null !== $value && '' !== $value) {
            return $value;
        }

        // Get default value from context args (from filter syntax)
        $args = $context->extra();

        // Fallback to constructor parameter
        return $args[0] ?? $this->defaultValue;
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
        return ['default'];
    }
}
