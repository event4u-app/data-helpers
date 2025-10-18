<?php

declare(strict_types=1);

namespace event4u\DataHelpers;

use event4u\DataHelpers\SimpleDTO\DTOInterface;
use event4u\DataHelpers\SimpleDTO\SimpleDTOTrait;
use JsonSerializable;

/**
 * Base class for immutable Data Transfer Objects.
 *
 * Provides JSON serialization and simple array conversion using
 * the SimpleDTOTrait. Extend this class to create your own DTOs.
 *
 * Example usage:
 *   class ProductDTO extends SimpleDTO {
 *       public function __construct(
 *           public readonly string $name,
 *           public readonly float $price,
 *           public readonly ?string $description = null,
 *       ) {}
 *   }
 *
 *   $product = ProductDTO::fromArray([
 *       'name' => 'Laptop',
 *       'price' => 999.99,
 *       'description' => 'High-performance laptop',
 *   ]);
 *
 *   echo $product->name; // 'Laptop'
 *   $array = $product->toArray();
 *   $json = json_encode($product);
 */
abstract class SimpleDTO implements DTOInterface, JsonSerializable
{
    use SimpleDTOTrait;
}

