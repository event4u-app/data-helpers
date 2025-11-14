<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\User;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasEntity;
use event4u\DataHelpers\SimpleDto\SimpleDtoDoctrineTrait;

#[HasEntity(User::class)]
class UserDto extends SimpleDto
{
    use SimpleDtoDoctrineTrait;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {
    }
}

