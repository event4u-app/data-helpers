<?php

declare(strict_types=1);

namespace Tests\Utils\Docu\Dtos;

use event4u\DataHelpers\SimpleDto;

class AddressDto extends SimpleDto
{
    public function __construct(
        public string $street = '',
        public string $city = '',
        public string $country = '',
    ) {
    }
}
