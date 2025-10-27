<?php

declare(strict_types=1);

namespace Tests\Utils\Docu\Dtos;

use event4u\DataHelpers\SimpleDto;

class EmployeeDto extends SimpleDto
{
    /**
     * @param array<int, EmailDto> $emails
     * @param array<int, OrderDto> $orders
     */
    public function __construct(
        public string $name = '',
        public array $emails = [],
        public array $orders = [],
    ) {
    }
}
