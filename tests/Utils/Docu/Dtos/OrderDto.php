<?php

declare(strict_types=1);

namespace Tests\Utils\Docu\Dtos;

use event4u\DataHelpers\SimpleDto;

class OrderDto extends SimpleDto
{
    public function __construct(
        public int $id = 0,
        public float $total = 0.0,
        public string $status = '',
    ) {
    }
}
