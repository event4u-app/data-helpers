<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

#[AutoCast]
class CompanyAutoCastDto extends SimpleDto
{
    /**
     * @param array<int, DepartmentAutoCastDto> $departments
     * @param array<int, ProjectAutoCastDto> $projects
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
