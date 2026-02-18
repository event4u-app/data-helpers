<?php

declare(strict_types=1);

namespace Tests\Utils\LiteDtos;

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\HasModel;
use Tests\Utils\Models\User;

#[HasModel(User::class)]
class UserLiteDto extends LiteDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name = '',
        public readonly string $email = '',
        public readonly int $age = 0,
        public readonly bool $active = false,
    ) {}
}
