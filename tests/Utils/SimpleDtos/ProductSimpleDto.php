<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasModel;
use event4u\DataHelpers\SimpleDto\Attributes\Map;
use Tests\Utils\Models\Product;

#[HasModel(Product::class)]
class ProductSimpleDto extends SimpleDto
{
    public function __construct(
        #[Map('product_id')]
        public readonly ?int $id = null,
        #[Map('external_sku')]
        public readonly ?string $sku = null,
        #[Map('product_name')]
        public readonly string $name = '',
        #[Map('unit_price')]
        public readonly float $price = 0.0,
        #[Map('stock_quantity')]
        public readonly int $stock = 0,
    ) {}
}
