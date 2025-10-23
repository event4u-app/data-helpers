<?php

declare(strict_types=1);

namespace Tests\utils\DTOs;

class CompanyDto
{
    public ?string $name = null;

    public ?string $registration_number = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $address = null;

    public ?string $city = null;

    public ?string $country = null;

    public ?int $founded_year = null;

    public ?int $employee_count = null;

    public ?float $annual_revenue = null;

    public ?bool $is_active = null;

    /** @var array<int, DepartmentDto> */
    public array $departments = [];

    /** @var array<int, ProjectDto> */
    public array $projects = [];
}
