<?php

declare(strict_types=1);

namespace Tests\Utils\Dtos;

use event4u\DataHelpers\SimpleDto;

final class AddressDto extends SimpleDto
{
    public string $city = '';
}
