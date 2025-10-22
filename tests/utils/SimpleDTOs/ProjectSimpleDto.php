<?php

declare(strict_types=1);

namespace Tests\utils\SimpleDTOs;

use event4u\DataHelpers\SimpleDTO;

class ProjectSimpleDto extends SimpleDTO
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
