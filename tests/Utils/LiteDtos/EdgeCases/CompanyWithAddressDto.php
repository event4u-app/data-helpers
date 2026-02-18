<?php

declare(strict_types=1);

namespace Tests\Utils\LiteDtos\EdgeCases;

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\HasModel;
use Tests\Utils\Models\Company;

#[HasModel(Company::class)]
class CompanyWithAddressDto extends LiteDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name = '',
        public readonly string $industry = '',
        public readonly ?AddressLiteDto $address = null,
    ) {}
}
