<?php

declare(strict_types=1);

namespace event4u\DataHelpers\LiteDto\Attributes;

use Attribute;

/**
 * Attribute to link a DTO to a plain PHP object/class.
 *
 * This attribute allows DTOs to specify their corresponding plain PHP class,
 * enabling automatic object resolution in toObject() method.
 *
 * Example:
 * ```php
 * #[HasObject(Product::class)]
 * class ProductDto extends LiteDto
 * {
 *     use LiteDtoObjectTrait;
 *
 *     public function __construct(
 *         public readonly int $id,
 *         public readonly string $name,
 *         public readonly float $price,
 *     ) {}
 * }
 *
 * // Usage:
 * $dto = new ProductDto(1, 'Laptop', 999.99);
 * $object = $dto->toObject(); // Automatically uses Product::class
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
class HasObject
{
    /** @param class-string $objectClass The plain PHP object class */
    public function __construct(public readonly string $objectClass)
    {
    }
}
