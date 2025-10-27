<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

#[AutoCast]
class DepartmentAutoCastDto extends SimpleDto
{
    /** @param array<int, EmployeeAutoCastDto> $employees */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?int $budget = null,
        public readonly ?string $manager_name = null,
        public readonly array $employees = [],
    ) {}
}
