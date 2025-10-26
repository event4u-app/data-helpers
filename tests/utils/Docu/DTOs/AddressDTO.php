<?php

declare(strict_types=1);

namespace Tests\utils\Docu\DTOs;

use event4u\DataHelpers\SimpleDTO;

class AddressDTO extends SimpleDTO
{
    public function __construct(
        public string $street = '',
        public string $city = '',
        public string $country = '',
    ) {
    }
}
