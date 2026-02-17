<?php

declare(strict_types=1);

namespace Tests\Utils\SimpleDtos\EdgeCases;

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\HasModel;
use Tests\Utils\Models\Company;

#[HasModel(Company::class)]
class CompanyWithAddressDto extends SimpleDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name = '',
        public readonly string $industry = '',
        public readonly ?AddressSimpleDto $address = null,
    ) {}
}
