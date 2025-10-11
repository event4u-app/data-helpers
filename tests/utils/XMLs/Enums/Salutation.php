<?php

declare(strict_types=1);

namespace Tests\utils\XMLs\Enums;

/**
 * Salutation enum for contact persons.
 */
enum Salutation: string
{
    case MR = 'Mr';
    case MRS = 'Mrs';
    case MISS = 'Miss';
    case DIVERSE = 'Diverse';

    /** Get a human-readable label for the salutation. */
    public function label(): string
    {
        return match ($this) {
            self::MR => 'Mr.',
            self::MRS => 'Mrs.',
            self::MISS => 'Miss',
            self::DIVERSE => 'Diverse',
        };
    }

    /** Try to create from various input formats. */
    public static function tryFromAny(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_string($value)) {
            // Try exact match first
            $enum = self::tryFrom($value);
            if (null !== $enum) {
                return $enum;
            }

            // Try case-insensitive match
            $normalized = strtolower(trim($value));
            return match ($normalized) {
                'mr', 'mr.', 'herr' => self::MR,
                'mrs', 'mrs.', 'frau' => self::MRS,
                'miss', 'fräulein', 'frl', 'frl.' => self::MISS,
                'diverse', 'divers', 'other', 'x' => self::DIVERSE,
                default => null,
            };
        }

        return null;
    }
}

