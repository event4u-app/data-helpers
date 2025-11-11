<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Attributes;

use Attribute;
use event4u\DataHelpers\SimpleDto\Contracts\TransformAttribute;

/**
 * Transform a string to leetspeak (1337sp34k).
 *
 * This attribute automatically converts string values to leetspeak before validation.
 * It does not validate - it transforms the value.
 *
 * Example:
 * ```php
 * class GameDto extends SimpleDto
 * {
 *     public function __construct(
 *         #[Leetspeak]
 *         public readonly string $username,
 *
 *         #[Leetspeak]
 *         public readonly string $message,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Leetspeak implements TransformAttribute
{
    /** Leetspeak character mapping. */
    private const LEET_MAP = [
        'o' => '0',
        'O' => '0',
        'l' => '1',
        'L' => '1',
        'e' => '3',
        'E' => '3',
        'a' => '4',
        'A' => '4',
        's' => '5',
        'S' => '5',
        't' => '7',
        'T' => '7',
        'b' => '8',
        'B' => '8',
        'g' => '9',
        'G' => '9',
        'i' => '!',
        'I' => '!',
    ];

    public function transform(mixed $value, string $propertyName): mixed
    {
        if (null === $value || '' === $value) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        return strtr($value, self::LEET_MAP);
    }
}
