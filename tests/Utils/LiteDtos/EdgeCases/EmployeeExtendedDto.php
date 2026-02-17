<?php

declare(strict_types=1);

namespace Tests\Utils\LiteDtos\EdgeCases;

use event4u\DataHelpers\LiteDto\Attributes\HasModel;
use Tests\Utils\Models\Employee;

#[HasModel(Employee::class)]
class EmployeeExtendedDto extends BasePersonDto
{
    public function __construct(
        ?int $id = null,
        string $name = '',
        string $email = '',
        public readonly string $department = '',
        public readonly string $position = '',
        public readonly int $salary = 0,
    ) {
        parent::__construct($id, $name, $email);
    }
}
