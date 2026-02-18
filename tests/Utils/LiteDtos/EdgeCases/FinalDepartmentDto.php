<?php

declare(strict_types=1);

namespace Tests\Utils\LiteDtos\EdgeCases;

class FinalDepartmentDto extends MiddleEmailDto
{
    public function __construct(
        string $name = '',
        string $email = '',
        public readonly string $department = '',
    ) {
        parent::__construct($name, $email);
    }
}
