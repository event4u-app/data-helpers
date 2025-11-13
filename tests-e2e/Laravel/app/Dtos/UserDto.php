<?php

declare(strict_types=1);

namespace E2E\Laravel\Dtos;

use E2E\Laravel\Models\User;
use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasModel;
use event4u\DataHelpers\SimpleDto\SimpleDtoEloquentTrait;

#[HasModel(User::class)]
class UserDto extends SimpleDto
{
    use SimpleDtoEloquentTrait;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {
    }
}

