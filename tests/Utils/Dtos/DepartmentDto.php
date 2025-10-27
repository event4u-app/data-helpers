<?php

declare(strict_types=1);

namespace Tests\Utils\Dtos;

class DepartmentDto
{
    public ?string $name = null;

    public ?string $code = null;

    public ?float $budget = null;

    public ?int $employee_count = null;

    public ?string $manager_name = null;

    /** @var EmployeeDto[] */
    public array $employees = [];

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
