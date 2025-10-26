<?php

declare(strict_types=1);

namespace Tests\Utils\XMLs\Enums;

/**
 * Project Status Enum
 *
 * Represents different project statuses across XML versions:
 * - version1: numeric codes (1, 2, 3, etc.)
 * - version2: letter codes (BB, etc.)
 * - version3: text values (Order, etc.)
 */
enum ProjectStatus: string
{
    case OFFER = 'offer';
    case ORDER = 'order';
    case ORDER_CALCULATION = 'order_calculation';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /** Get the status from version1 numeric code */
    public static function fromVersion1(string $code): ?self
    {
        return match ($code) {
            '1' => self::OFFER,
            '2' => self::ORDER_CALCULATION,
            '3' => self::ORDER,
            '4' => self::IN_PROGRESS,
            '5' => self::COMPLETED,
            '6' => self::CANCELLED,
            default => null,
        };
    }

    /** Get the status from version2 letter code */
    public static function fromVersion2(string $code): ?self
    {
        return match ($code) {
            'AN' => self::OFFER,
            'BB' => self::ORDER,
            'IP' => self::IN_PROGRESS,
            'AB' => self::COMPLETED,
            'ST' => self::CANCELLED,
            default => null,
        };
    }

    /** Get the status from version3 text value */
    public static function fromVersion3(string $text): ?self
    {
        return match (strtolower($text)) {
            'offer', 'angebot' => self::OFFER,
            'order', 'auftrag' => self::ORDER,
            'order calculation', 'auftragskalkulation' => self::ORDER_CALCULATION,
            'in progress', 'in bearbeitung' => self::IN_PROGRESS,
            'completed', 'abgeschlossen' => self::COMPLETED,
            'cancelled', 'storniert' => self::CANCELLED,
            default => null,
        };
    }

    /** Get human-readable label */
    public function label(): string
    {
        return match ($this) {
            self::OFFER => 'Offer',
            self::ORDER => 'Order',
            self::ORDER_CALCULATION => 'Order Calculation',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /** Convert to version1 format */
    public function toVersion1(): string
    {
        return match ($this) {
            self::OFFER => '1',
            self::ORDER_CALCULATION => '2',
            self::ORDER => '3',
            self::IN_PROGRESS => '4',
            self::COMPLETED => '5',
            self::CANCELLED => '6',
        };
    }

    /** Convert to version2 format */
    public function toVersion2(): string
    {
        return match ($this) {
            self::OFFER => 'AN',
            self::ORDER => 'BB',
            self::ORDER_CALCULATION => 'BB',
            self::IN_PROGRESS => 'IP',
            self::COMPLETED => 'AB',
            self::CANCELLED => 'ST',
        };
    }

    /** Convert to version3 format */
    public function toVersion3(): string
    {
        return match ($this) {
            self::OFFER => 'Offer',
            self::ORDER => 'Order',
            self::ORDER_CALCULATION => 'Order Calculation',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
