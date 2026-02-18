<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos\Symfony;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasEntity;
use Tests\Utils\Entities\Employee;

#[HasEntity(Employee::class)]
class EmployeeSimpleDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $position = null,
    ) {}
}
