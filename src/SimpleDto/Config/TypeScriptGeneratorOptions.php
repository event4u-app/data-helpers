<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Config;

use event4u\DataHelpers\SimpleDto\Enums\TypeScriptExportType;

/**
 * Configuration options for TypeScript interface generation.
 *
 * This class provides type-safe configuration for the TypeScriptGenerator.
 * All options have sensible defaults and can be customized as needed.
 *
 * Note: This is a simple configuration class, NOT a SimpleDto.
 * It doesn't need Dto features like serialization, validation, etc.
 *
 * @example
 * ```php
 * // Use default options
 * $options = TypeScriptGeneratorOptions::default();
 *
 * // Customize options
 * $options = new TypeScriptGeneratorOptions(
 *     exportType: TypeScriptExportType::Declare,
 *     includeComments: false,
 *     sortProperties: true,
 * );
 * ```
 */
final readonly class TypeScriptGeneratorOptions
{
    /**
     * Create TypeScript generator options.
     *
     * @param TypeScriptExportType $exportType Export type for interfaces (export, declare, or none)
     * @param bool $includeComments Whether to include JSDoc comments in generated interfaces
     * @param bool $sortProperties Whether to sort properties alphabetically
     */
    public function __construct(
        public TypeScriptExportType $exportType = TypeScriptExportType::Export,
        public bool $includeComments = true,
        public bool $sortProperties = false,
    ) {
    }

    /**
     * Create options with default values.
     *
     * Default configuration:
     * - exportType: Export (generates "export interface")
     * - includeComments: true (includes JSDoc comments)
     * - sortProperties: false (preserves property order)
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Create options for exported interfaces.
     *
     * Generates: `export interface UserDto { ... }`
     *
     * @param bool $includeComments Whether to include JSDoc comments
     * @param bool $sortProperties Whether to sort properties alphabetically
     */
    public static function export(bool $includeComments = true, bool $sortProperties = false): self
    {
        return new self(
            exportType: TypeScriptExportType::Export,
            includeComments: $includeComments,
            sortProperties: $sortProperties,
        );
    }

    /**
     * Create options for declared interfaces.
     *
     * Generates: `declare interface UserDto { ... }`
     *
     * @param bool $includeComments Whether to include JSDoc comments
     * @param bool $sortProperties Whether to sort properties alphabetically
     */
    public static function declare(bool $includeComments = true, bool $sortProperties = false): self
    {
        return new self(
            exportType: TypeScriptExportType::Declare,
            includeComments: $includeComments,
            sortProperties: $sortProperties,
        );
    }

    /**
     * Create options for plain interfaces (no export/declare).
     *
     * Generates: `interface UserDto { ... }`
     *
     * @param bool $includeComments Whether to include JSDoc comments
     * @param bool $sortProperties Whether to sort properties alphabetically
     */
    public static function plain(bool $includeComments = true, bool $sortProperties = false): self
    {
        return new self(
            exportType: TypeScriptExportType::None,
            includeComments: $includeComments,
            sortProperties: $sortProperties,
        );
    }

    /**
     * Create options without comments.
     *
     * Useful for generating minimal TypeScript definitions.
     *
     * @param TypeScriptExportType $exportType Export type for interfaces
     * @param bool $sortProperties Whether to sort properties alphabetically
     */
    public static function withoutComments(
        TypeScriptExportType $exportType = TypeScriptExportType::Export,
        bool $sortProperties = false
    ): self {
        return new self(
            exportType: $exportType,
            includeComments: false,
            sortProperties: $sortProperties,
        );
    }

    /**
     * Create options with sorted properties.
     *
     * Useful for generating consistent, alphabetically sorted interfaces.
     *
     * @param TypeScriptExportType $exportType Export type for interfaces
     * @param bool $includeComments Whether to include JSDoc comments
     */
    public static function sorted(
        TypeScriptExportType $exportType = TypeScriptExportType::Export,
        bool $includeComments = true
    ): self {
        return new self(
            exportType: $exportType,
            includeComments: $includeComments,
            sortProperties: true,
        );
    }
}
