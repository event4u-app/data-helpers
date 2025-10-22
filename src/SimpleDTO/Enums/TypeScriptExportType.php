<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Enums;

/**
 * TypeScript export type enum for interface generation.
 *
 * Provides type-safe export types for TypeScript interface generation.
 *
 * Example:
 * ```php
 * use event4u\DataHelpers\SimpleDTO\Enums\TypeScriptExportType;
 *
 * $generator = new TypeScriptGenerator();
 * $typescript = $generator->generate(
 *     [UserDTO::class],
 *     ['exportType' => TypeScriptExportType::Export]
 * );
 *
 * // Get prefix
 * $prefix = TypeScriptExportType::Export->getPrefix();
 * // Result: 'export'
 *
 * // Parse from string
 * $type = TypeScriptExportType::fromString('declare');
 * ```
 */
enum TypeScriptExportType: string
{
    case Export = 'export';
    case Declare = 'declare';
    case None = 'none';

    /**
     * Get the prefix for interface declaration.
     *
     * @return string The prefix string
     */
    public function getPrefix(): string
    {
        return match ($this) {
            self::Export => 'export',
            self::Declare => 'declare',
            self::None => '',
        };
    }

    /**
     * Parse a TypeScript export type from a string.
     *
     * @param string $type The type string (e.g., 'export', 'declare', 'none')
     *
     * @return self|null The export type or null if invalid
     */
    public static function fromString(string $type): ?self
    {
        return match ($type) {
            'export' => self::Export,
            'declare' => self::Declare,
            'none' => self::None,
            default => null,
        };
    }

    /**
     * Get all available export types.
     *
     * @return array<string> Array of export type strings
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    /**
     * Check if a string is a valid export type.
     *
     * @param string $type The type string to check
     *
     * @return bool True if valid, false otherwise
     */
    public static function isValid(string $type): bool
    {
        return self::fromString($type) instanceof \event4u\DataHelpers\SimpleDTO\Enums\TypeScriptExportType;
    }
}
