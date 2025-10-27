<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos;

use event4u\DataHelpers\SimpleDto;

class DepartmentSimpleDto extends SimpleDto
{
    /** @param array<int, EmployeeSimpleDto> $employees */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $code = null,
        public readonly ?float $budget = null,
        public readonly ?int $employee_count = null,
        public readonly ?string $manager_name = null,
        public readonly array $employees = [],
    ) {}
}
