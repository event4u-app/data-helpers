<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

#[AutoCast]
class EmployeeAutoCastDto extends SimpleDto
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $position = null,
        public readonly ?float $salary = null,
        public readonly ?int $years_of_service = null,
    ) {}
}
