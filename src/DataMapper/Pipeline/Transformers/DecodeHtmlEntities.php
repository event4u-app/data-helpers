<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Transformers;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\TransformerInterface;

/**
 * Decodes HTML entities in string values.
 *
 * Converts encoded entities like &amp;#32; (space), &amp;#45; (hyphen),
 * &amp;lt;, &amp;gt;, &amp;quot;, etc. to their actual characters.
 *
 * Uses html_entity_decode() with ENT_QUOTES | ENT_HTML5 flags.
 *
 * Example:
 *   DataMapper::pipe([DecodeHtmlEntities::class])->map($source, $target, $mapping);
 *   Template: {{ value | decode_html }}
 *   Template: {{ value | html_decode }}
 *
 * Input:  "Sample&amp;#32;&amp;#45;&amp;#32;Swimming&amp;#32;Pool"
 * Output: "Sample - Swimming Pool"
 */
final class DecodeHtmlEntities implements TransformerInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Decode HTML entities (including numeric entities like &#32;)
        // ENT_QUOTES: Convert both double and single quotes
        // ENT_HTML5: Handle HTML5 entities
        //
        // Decode multiple times to handle double-encoded entities like &amp;#32;
        // Stop when no more changes occur (max 10 iterations to prevent infinite loops)
        $decoded = $value;
        $maxIterations = 10;
        $iteration = 0;

        do {
            $previous = $decoded;
            $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $iteration++;
        } while ($decoded !== $previous && $iteration < $maxIterations);

        return $decoded;
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
        return ['decode_html', 'html_decode', 'decode_entities'];
    }
}

