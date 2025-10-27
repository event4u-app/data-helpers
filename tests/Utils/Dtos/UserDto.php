<?php

declare(strict_types=1);

namespace Tests\Utils\Dtos;

use event4u\DataHelpers\SimpleDto;

final class UserDto extends SimpleDto
{
    public string $name = '';
    public string $email = '';
    public ?ProfileDto $profile = null;
}
