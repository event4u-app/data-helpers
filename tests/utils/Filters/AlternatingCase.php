<?php

declare(strict_types=1);

namespace Tests\utils\Filters;

use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Transforms strings to alternating case (every 2nd, 4th, 6th... character uppercase).
 *
 * Example:
 *   "hello world" => "hElLo wOrLd"
 *   "test" => "tEsT"
 */
final class AlternatingCase implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $result = '';
        $length = mb_strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($value, $i, 1);

            // Every 2nd, 4th, 6th... character (1-based index: 2, 4, 6...)
            // In 0-based: 1, 3, 5... (odd indices)
            if (0 === ($i + 1) % 2) {
                $result .= mb_strtoupper($char);
            } else {
                $result .= mb_strtolower($char);
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
        return ['alternating', 'alt_case', 'zigzag'];
    }
}
