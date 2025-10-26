<?php

declare(strict_types=1);

namespace Tests\utils\XMLs\Enums;

/**
 * Position Type Enum
 *
 * Represents different position types across XML versions:
 * - N = Normal/Standard Position
 * - T = Title/Header Position
 * - S = Sum/Total Position
 */
enum PositionType: string
{
    case NORMAL = 'N';
    case TITLE = 'T';
    case SUM = 'S';
    case STANDARD = 'Standard';
    case SECTION = 'Section';

    /** Get human-readable label */
    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal Position',
            self::TITLE => 'Title Position',
            self::SUM => 'Sum Position',
            self::STANDARD => 'Standard Position',
            self::SECTION => 'Section',
        };
    }

    /** Check if this is a calculable position (has quantity/price) */
    public function isCalculable(): bool
    {
        return match ($this) {
            self::NORMAL, self::STANDARD => true,
            self::TITLE, self::SUM, self::SECTION => false,
        };
    }
}
