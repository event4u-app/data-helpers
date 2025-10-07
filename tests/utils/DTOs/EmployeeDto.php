<?php

declare(strict_types=1);

namespace Tests\utils\DTOs;

class EmployeeDto
{
    public ?string $name = null;

    public ?string $email = null;

    public ?string $position = null;

    public ?float $salary = null;

    public ?string $hire_date = null;

    /** @var array<int, ProjectDto> */
    public array $projects = [];
}

