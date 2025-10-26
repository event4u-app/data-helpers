<?php

declare(strict_types=1);

namespace Tests\Utils\Docu\DTOs;

use event4u\DataHelpers\SimpleDTO;

class OrderDTO extends SimpleDTO
{
    public function __construct(
        public int $id = 0,
        public float $total = 0.0,
        public string $status = '',
    ) {
    }
}
