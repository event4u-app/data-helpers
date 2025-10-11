<?php

declare(strict_types=1);

namespace Tests\utils\Doctrine\Enums;

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::PENDING => 'Pending',
        };
    }

    public static function tryFromAny(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (!is_string($value)) {
            return null;
        }

        // Case-insensitive matching
        $value = strtolower(trim($value));

        return match ($value) {
            'active', 'aktiv' => self::ACTIVE,
            'inactive', 'inaktiv' => self::INACTIVE,
            'pending', 'ausstehend' => self::PENDING,
            default => self::tryFrom($value),
        };
    }
}
