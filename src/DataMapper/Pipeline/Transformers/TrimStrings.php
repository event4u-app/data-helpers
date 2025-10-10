<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Trims characters from the beginning and end of string values.
 *
 * By default trims whitespace. You can specify custom characters to trim.
 *
 * Examples:
 *   Pipeline: new TrimStrings()                    // Trim whitespace (default)
 *   Pipeline: new TrimStrings('-')                 // Trim only '-'
 *   Pipeline: new TrimStrings(' -')                // Trim space and '-'
 *   Template: {{ value | trim }}                   // Trim whitespace (default)
 *   Template: {{ value | trim:"-" }}               // Trim only '-'
 *   Template: {{ value | trim:" -" }}              // Trim space and '-'
 *   Template: {{ value | trim:" \t\n\r" }}         // Trim space, tab, newline, carriage return
 *
 * Note: Uses PHP's trim() function. See https://www.php.net/manual/en/function.trim.php
 */
final readonly class TrimStrings implements TransformerInterface
{
    /** @param string|null $characters Characters to trim (null = whitespace) */
    public function __construct(
        private ?string $characters = null,
    ) {}

    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Get characters from context args (from filter syntax) or constructor
        $args = $context->extra();
        $characters = $this->characters;

        if (count($args) >= 1 && is_string($args[0])) {
            $characters = $args[0];
        }

        // Use default PHP trim if no characters specified
        if (null === $characters) {
            return trim($value);
        }

        // Trim specific characters
        return trim($value, $characters);
    }

    public function getHook(): string
    {
        return 'preTransform';
    }

    public function getFilter(): ?string
    {
        return null;
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['trim'];
    }
}
