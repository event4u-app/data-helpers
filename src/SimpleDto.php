<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\SimpleDto\DtoInterface;
use event4u\DataHelpers\SimpleDto\SimpleDtoTrait;
use JsonSerializable;

/**
 * Base class for immutable Data Transfer Objects.
 *
 * Provides JSON serialization and simple array conversion using
 * the SimpleDtoTrait. Extend this class to create your own Dtos.
 *
 * Example usage:
 *   class ProductDto extends SimpleDto {
 *       public function __construct(
 *           public readonly string $name,
 *           public readonly float $price,
 *           public readonly ?string $description = null,
 *       ) {}
 *   }
 *
 *   $product = ProductDto::fromArray([
 *       'name' => 'Laptop',
 *       'price' => 999.99,
 *       'description' => 'High-performance laptop',
 *   ]);
 *
 *   echo $product->name; // 'Laptop'
 *   $array = $product->toArray();
 *   $json = json_encode($product);
 */
abstract class SimpleDto implements DtoInterface, JsonSerializable
{
    use SimpleDtoTrait;
}
