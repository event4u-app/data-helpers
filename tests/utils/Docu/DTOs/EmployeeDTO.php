<?php

declare(strict_types=1);

namespace Tests\utils\Docu\DTOs;

use event4u\DataHelpers\SimpleDTO;

class EmployeeDTO extends SimpleDTO
{
    /**
     * @param array<int, EmailDTO> $emails
     * @param array<int, OrderDTO> $orders
     */
    public function __construct(
        public string $name = '',
        public array $emails = [],
        public array $orders = [],
    ) {
    }
}
