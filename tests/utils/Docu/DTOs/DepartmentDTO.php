<?php

declare(strict_types=1);

namespace Tests\utils\Docu\DTOs;

use event4u\DataHelpers\SimpleDTO;

class DepartmentDTO extends SimpleDTO
{
    /** @param array<int, EmployeeDTO> $employees */
    public function __construct(
        public string $name = '',
        public array $employees = [],
    ) {
    }
}
