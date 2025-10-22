<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Replace strings with other strings.
 *
 * Supports three modes:
 * 1. Simple replacement: replace:search:replacement
 * 2. Multiple searches, single replacement: replace:[search1,search2]:replacement
 * 3. Multiple searches, multiple replacements: replace:[search1,search2]:[replace1,replace2]
 *
 * Optional third parameter for case-insensitive matching: replace:search:replacement:true
 *
 * Examples:
 *   Template: {{ value | replace:"Mr":"Herr" }}
 *   Template: {{ value | replace:[Mr,Mrs]:Person }}
 *   Template: {{ value | replace:[Mr,Mrs]:[Herr,Frau] }}
 *   Template: {{ value | replace:"mr":"herr":true }}
 *
 * Pipeline: new Replace('search', 'replacement')
 * Pipeline: new Replace(['Mr', 'Mrs'], 'Person')
 * Pipeline: new Replace(['Mr', 'Mrs'], ['Herr', 'Frau'])
 */
final readonly class Replace implements FilterInterface
{
    /**
     * @param string|array<int, string>|null $search Search string(s) or null to use args
     * @param string|array<int, string>|null $replacement Replacement string(s)
     * @param bool $caseInsensitive Case-insensitive matching
     */
    public function __construct(
        private string|array|null $search = null,
        private string|array|null $replacement = null,
        private bool $caseInsensitive = false,
    ) {
    }

    public function transform(mixed $value, HookContext $context): mixed
    {
        // Only work with strings
        if (!is_string($value)) {
            return $value;
        }

        // Get arguments from filter syntax
        $args = $context->extra();

        // Determine search, replacement, and case sensitivity
        $search = $this->search;
        $replacement = $this->replacement;
        $caseInsensitive = $this->caseInsensitive;

        // Parse arguments if provided
        if ([] !== $args) {
            // First arg: search (string or array syntax)
            if (is_array($args[0])) {
                $search = $args[0];
            } elseif (is_string($args[0]) && $this->isArraySyntax($args[0])) {
                $search = $this->parseArraySyntax($args[0]);
            } else {
                $search = $args[0] ?? null;
            }

            // Second arg: replacement (string or array syntax)
            if (isset($args[1])) {
                if (is_array($args[1])) {
                    $replacement = $args[1];
                } elseif (is_string($args[1]) && $this->isArraySyntax($args[1])) {
                    $replacement = $this->parseArraySyntax($args[1]);
                } else {
                    $replacement = $args[1];
                }
            }

            // Third arg: case insensitive flag
            if (isset($args[2])) {
                $caseInsensitive = filter_var($args[2], FILTER_VALIDATE_BOOLEAN);
            }
        }

        // If no search provided, return value unchanged
        if (null === $search || ([] === $search)) {
            return $value;
        }

        // Type check for PHPStan
        if (!is_string($search) && !is_array($search)) {
            return $value;
        }

        if (null !== $replacement && !is_string($replacement) && !is_array($replacement)) {
            return $value;
        }

        // Ensure arrays are properly typed
        if (is_array($search)) {
            /** @var array<int, string> $search */
            $search = array_values($search);
        }

        if (is_array($replacement)) {
            /** @var array<int, string> $replacement */
            $replacement = array_values($replacement);
        }

        // Perform replacement
        return $this->performReplace($value, $search, $replacement, $caseInsensitive);
    }

    /**
     * Perform the actual replacement.
     *
     * @param string|array<int, string> $search
     * @param string|array<int, string>|null $replacement
     */
    private function performReplace(
        string $value,
        string|array $search,
        string|array|null $replacement,
        bool $caseInsensitive
    ): string
    {
        // Case 1: Single search, single replacement
        if (is_string($search) && (is_string($replacement) || null === $replacement)) {
            if (null === $replacement) {
                return $value;
            }

            return $caseInsensitive
                ? str_ireplace($search, $replacement, $value)
                : str_replace($search, $replacement, $value);
        }

        // Case 2: Array search, single replacement (replace all searches with same value)
        if (is_array($search) && is_string($replacement)) {
            // Sort by length (longest first) to avoid partial replacements
            $sortedSearch = $search;
            usort($sortedSearch, fn($a, $b): int => strlen($b) - strlen($a));

            // Create array of same replacement for each search
            $replacements = array_fill(0, count($sortedSearch), $replacement);

            return $caseInsensitive
                ? str_ireplace($sortedSearch, $replacements, $value)
                : str_replace($sortedSearch, $replacements, $value);
        }

        // Case 3: Array search, array replacement (1:1 mapping)
        if (is_array($search) && is_array($replacement)) {
            // Sort by search length (longest first) to avoid partial replacements
            // Keep the mapping between search and replacement
            $pairs = [];
            $count = min(count($search), count($replacement));

            for ($i = 0; $i < $count; $i++) {
                $pairs[] = ['search' => $search[$i], 'replace' => $replacement[$i]];
            }

            // Sort by search string length (longest first)
            usort($pairs, fn(array $a, array $b): int => strlen($b['search']) - strlen($a['search']));

            // Extract sorted arrays
            $sortedSearch = array_column($pairs, 'search');
            $sortedReplace = array_column($pairs, 'replace');

            return $caseInsensitive
                ? str_ireplace($sortedSearch, $sortedReplace, $value)
                : str_replace($sortedSearch, $sortedReplace, $value);
        }

        // Case 4: Array search, no replacement
        if (is_array($search) && null === $replacement) {
            // Remove all search strings
            return $caseInsensitive
                ? str_ireplace($search, '', $value)
                : str_replace($search, '', $value);
        }

        return $value;
    }

    /** Check if a string is array syntax: [value1,value2,value3] */
    private function isArraySyntax(string $str): bool
    {
        return str_starts_with($str, '[') && str_ends_with($str, ']');
    }

    /**
     * Parse array syntax: [Mr,Mrs,Dr] into ['Mr', 'Mrs', 'Dr']
     *
     * @return array<int, string>
     */
    private function parseArraySyntax(string $str): array
    {
        // Remove brackets
        $str = trim($str, '[]');

        if ('' === $str) {
            return [];
        }

        // Split by comma
        $items = explode(',', $str);

        // Trim each item
        return array_map('trim', $items);
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
        return ['replace'];
    }
}
