<?php

declare(strict_types=1);

namespace Tests\Utils\DTOs;

final class UserDTO
{
    public string $name = '';
    public string $email = '';
    public ?ProfileDTO $profile = null;
}
