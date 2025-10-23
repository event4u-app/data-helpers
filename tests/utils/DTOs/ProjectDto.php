<?php

declare(strict_types=1);

namespace Tests\utils\DTOs;

class ProjectDto
{
    public ?string $name = null;

    public ?string $code = null;

    public ?float $budget = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?string $status = null;

    /** @var array<int, EmployeeDto> */
    public array $employees = [];
}
