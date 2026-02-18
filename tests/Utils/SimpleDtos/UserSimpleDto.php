<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasModel;
use Tests\Utils\Models\User;

#[HasModel(User::class)]
class UserSimpleDto extends SimpleDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name = '',
        public readonly string $email = '',
        public readonly int $age = 0,
        public readonly bool $active = false,
    ) {}
}
