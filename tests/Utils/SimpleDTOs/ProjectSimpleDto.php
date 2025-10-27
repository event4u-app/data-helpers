<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos;

use event4u\DataHelpers\SimpleDto;

class ProjectSimpleDto extends SimpleDto
{
    /** @param array<int, EmployeeSimpleDto> $employees */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $code = null,
        public readonly ?float $budget = null,
        public readonly ?string $start_date = null,
        public readonly ?string $end_date = null,
        public readonly ?string $status = null,
        public readonly array $employees = [],
    ) {}
}
