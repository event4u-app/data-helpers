<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Attributes;

use Attribute;

/**
 * Marks a property as lazy-loaded.
 *
 * Lazy properties are not included in toArray() or JSON serialization by default.
 * They must be explicitly requested using include() method.
 *
 * This is useful for:
 * - Large data fields (e.g., base64-encoded images, large text)
 * - Expensive computations or database queries
 * - Sensitive data that should only be loaded when needed
 * - Performance optimization
 *
 * @example Basic lazy property
 * ```php
 * class UserDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[Lazy]
 *         public readonly string $biography,
 *     ) {}
 * }
 *
 * $user = UserDTO::fromArray(['name' => 'John', 'biography' => 'Long text...']);
 *
 * // Biography not included by default
 * $user->toArray(); // ['name' => 'John']
 *
 * // Include lazy property explicitly
 * $user->include(['biography'])->toArray();
 * // ['name' => 'John', 'biography' => 'Long text...']
 * ```
 *
 * @example Conditional lazy loading
 * ```php
 * class DocumentDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $title,
 *         #[Lazy(when: 'admin')]
 *         public readonly string $internalNotes,
 *     ) {}
 * }
 *
 * // Only loaded for admin users
 * $doc->withContext('admin')->toArray();
 * ```
 *
 * @example Multiple lazy properties
 * ```php
 * class ProductDTO extends SimpleDTO
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[Lazy]
 *         public readonly string $description,
 *         #[Lazy]
 *         public readonly string $specifications,
 *     ) {}
 * }
 *
 * // Include specific lazy properties
 * $product->include(['description'])->toArray();
 *
 * // Include all lazy properties
 * $product->includeAll()->toArray();
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Lazy
{
    /**
     * @param string|null $when Condition when this property should be automatically included
     *                           Can be a context name (e.g., 'admin', 'api', 'export')
     */
    public function __construct(
        public ?string $when = null,
    ) {}
}
