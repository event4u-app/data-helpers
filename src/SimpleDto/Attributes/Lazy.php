<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Marks a property as lazy-loaded.
 *
 * Lazy properties are not included in toArray() or JSON serialization by default.
 *
 * This is useful for:
 * - Large data fields (e.g., base64-encoded images, large text)
 * - Expensive computations or database queries
 * - Sensitive data that should only be loaded when needed
 * - Performance optimization
 *
 * @example Basic lazy property
 * ```php
 * class UserDto extends LiteDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[Lazy]
 *         public readonly string $biography,
 *     ) {}
 * }
 *
 * $user = UserDto::from(['name' => 'John', 'biography' => 'Long text...']);
 *
 * // Biography not included by default
 * $user->toArray(); // ['name' => 'John']
 * ```
 *
 * @example Multiple lazy properties
 * ```php
 * class ProductDto extends LiteDto
 * {
 *     public function __construct(
 *         public readonly string $name,
 *         #[Lazy]
 *         public readonly string $description,
 *         #[Lazy]
 *         public readonly string $specifications,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class Lazy {}
