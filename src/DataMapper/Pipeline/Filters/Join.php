<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Joins array elements into a string with a separator.
 *
 * Examples:
 *   Pipeline: new Join(', ')
 *   Template: {{ value | join }}         // Joins with ', ' (default)
 *   Template: {{ value | join:", " }}    // Joins with ', '
 *   Template: {{ value | join:" | " }}   // Joins with ' | '
 *   Template: {{ value | join:"" }}      // Joins with no separator
 */
final readonly class Join implements FilterInterface
{
    /** @param string $separator Separator to use when joining array elements */
    public function __construct(
        private string $separator = ', ',
    ) {
    }

    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        // Get separator from context args (from filter syntax)
        $args = $context->extra();
        $separator = isset($args[0]) && is_string($args[0]) ? $args[0] : $this->separator;

        return implode($separator, $value);
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
        return ['join'];
    }
}
