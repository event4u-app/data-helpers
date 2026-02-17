<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos\EdgeCases;

use event4u\DataHelpers\SimpleDto;

class OrderItemDto extends SimpleDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name = '',
        public readonly int $quantity = 0,
        public readonly float $price = 0.0,
    ) {}
}
