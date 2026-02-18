<?php

declare(strict_types=1);

namespace Tests\Utils\LiteDtos\Symfony;

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\HasEntity;
use Tests\Utils\Entities\Employee;

#[HasEntity(Employee::class)]
class EmployeeLiteDto extends LiteDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $position = null,
    ) {}
}
