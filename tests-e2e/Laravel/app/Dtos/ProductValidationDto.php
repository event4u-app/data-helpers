<?php

declare(strict_types=1);

namespace E2E\Laravel\Dtos;

use E2E\Laravel\Models\Product;
use event4u\DataHelpers\LiteDto\Attributes\Validation\ExistsCallback;
use event4u\DataHelpers\LiteDto\Attributes\Validation\Required;
use event4u\DataHelpers\LiteDto\Attributes\Validation\UniqueCallback;
use event4u\DataHelpers\LiteDto\LiteDto;

/**
 * DTO for testing callback-based validation with products.
 */
class ProductValidationDto extends LiteDto
{
    public function __construct(
        #[Required]
        #[UniqueCallback([self::class, 'validateUniqueSku'])]
        public readonly string $sku,

        #[Required]
        public readonly string $name,

        #[ExistsCallback([self::class, 'validateRelatedProductExists'])]
        public readonly ?int $relatedProductId = null,

        public readonly ?int $id = null,
    ) {}

    public static function validateUniqueSku(mixed $value, array $data): bool
    {
        return !Product::where('sku', $value)
            ->when(isset($data['id']), fn($q) => $q->where('id', '!=', $data['id']))
            ->exists();
    }

    public static function validateRelatedProductExists(mixed $value): bool
    {
        return Product::where('id', $value)->where('active', true)->exists();
    }
}

