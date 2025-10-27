<?php

declare(strict_types=1);

namespace Tests\Utils\Docu\Dtos;

use event4u\DataHelpers\SimpleDto;

class DepartmentDto extends SimpleDto
{
    /** @param array<int, EmployeeDto> $employees */
    public function __construct(
        public string $name = '',
        public array $employees = [],
    ) {
    }
}
