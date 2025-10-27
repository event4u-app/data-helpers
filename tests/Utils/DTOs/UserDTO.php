<?php

declare(strict_types=1);

namespace Tests\Utils\DTOs;

use event4u\DataHelpers\SimpleDTO;

final class UserDTO extends SimpleDTO
{
    public string $name = '';
    public string $email = '';
    public ?ProfileDTO $profile = null;
}
