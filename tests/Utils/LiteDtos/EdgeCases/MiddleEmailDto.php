<?php

declare(strict_types=1);

namespace Tests\Utils\LiteDtos\EdgeCases;

class MiddleEmailDto extends BaseNameDto
{
    public function __construct(
        string $name = '',
        public readonly string $email = '',
    ) {
        parent::__construct($name);
    }
}
