<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos;

use event4u\DataHelpers\SimpleDto;

class CompanySimpleDto extends SimpleDto
{
    /**
     * @param array<int, DepartmentSimpleDto> $departments
     * @param array<int, ProjectSimpleDto> $projects
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $registration_number = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $address = null,
        public readonly ?string $city = null,
        public readonly ?string $country = null,
        public readonly ?int $founded_year = null,
        public readonly ?int $employee_count = null,
        public readonly ?float $annual_revenue = null,
        public readonly ?bool $is_active = null,
        public readonly array $departments = [],
        public readonly array $projects = [],
    ) {}
}
