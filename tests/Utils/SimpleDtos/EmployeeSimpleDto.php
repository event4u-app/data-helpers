<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos;

use event4u\DataHelpers\SimpleDto;

class EmployeeSimpleDto extends SimpleDto
{
    /** @param array<int, ProjectSimpleDto> $projects */
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $position,
        public readonly float $salary,
        public readonly string $hire_date,
        public readonly array $projects = [],
    ) {}
}
