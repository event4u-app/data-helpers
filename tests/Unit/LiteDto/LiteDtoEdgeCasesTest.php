<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\Map;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;
use event4u\DataHelpers\LiteDto\Attributes\MapTo;

// Test DTOs for edge cases
class EdgeCaseUserLiteDto extends LiteDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name = '',
        public readonly string $email = '',
        public readonly int $age = 0,
        public readonly bool $active = false,
    ) {}
}

class EdgeCaseProfileLiteDto extends LiteDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name = '',
        public readonly ?string $bio = null,
        public readonly ?string $website = null,
        public readonly ?int $score = null,
    ) {}
}

class EdgeCaseProductLiteDto extends LiteDto
{
    public function __construct(
        public readonly ?int $product_id = null,
        #[MapFrom('external_sku')]
        #[MapTo('sku_code')]
        public readonly ?string $sku = null,
        #[Map('product_name')]
        public readonly string $name = '',
        #[Map('unit_price')]
        public readonly float $price = 0.0,
    ) {}
}

class EdgeCaseAddressLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $street = '',
        public readonly string $city = '',
        public readonly string $zip = '',
    ) {}
}

class EdgeCaseCompanyLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $name = '',
        public readonly ?EdgeCaseAddressLiteDto $address = null,
    ) {}
}

class EdgeCaseOrderItemLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $product_name = '',
        public readonly int $quantity = 0,
        public readonly float $price = 0.0,
    ) {}
}

class EdgeCaseOrderLiteDto extends LiteDto
{
    public function __construct(
        public readonly string $order_number = '',
        /** @var array<array<string, mixed>>|null */
        public readonly ?array $items = null,
    ) {}
}

class EdgeCaseBasePersonLiteDto extends LiteDto
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly string $name = '',
        public readonly string $email = '',
    ) {}
}

class EdgeCaseEmployeeLiteDto extends EdgeCaseBasePersonLiteDto
{
    public function __construct(
        ?int $id = null,
        string $name = '',
        string $email = '',
        public readonly string $department = '',
    ) {
        parent::__construct($id, $name, $email);
    }
}

describe('LiteDto Edge Cases - toArrayOnlyExplicitlySet()', function(): void {
    test('Edge Case A: Only explicitly set properties are returned', function(): void {
        // Create DTO with partial data
        $dto = EdgeCaseUserLiteDto::from([
            'id' => 1,
            'name' => 'Jane Doe',
        ]);

        $array = $dto->toArrayOnlyExplicitlySet();

        // Only id and name should be in the array
        expect($array)->toHaveKey('id')
            ->and($array)->toHaveKey('name')
            ->and($array)->not->toHaveKey('email')
            ->and($array)->not->toHaveKey('age')
            ->and($array)->not->toHaveKey('active')
            ->and($array['id'])->toBe(1)
            ->and($array['name'])->toBe('Jane Doe');
    });

    test('Edge Case A: wasPropertyExplicitlySet() detects changes', function(): void {
        $dto = EdgeCaseUserLiteDto::from([
            'name' => 'John',
        ]);

        expect($dto->wasPropertyExplicitlySet('name'))->toBeTrue()
            ->and($dto->wasPropertyExplicitlySet('email'))->toBeFalse()
            ->and($dto->wasPropertyExplicitlySet('age'))->toBeFalse();
    });

    test('Edge Case B: Nullable properties - explicit null is included', function(): void {
        // Explicitly set bio to null
        $dto1 = EdgeCaseProfileLiteDto::from([
            'name' => 'Alice',
            'bio' => null,
        ]);

        $array1 = $dto1->toArrayOnlyExplicitlySet();

        expect($array1)->toHaveKey('name')
            ->and($array1)->toHaveKey('bio')
            ->and($array1)->not->toHaveKey('website')
            ->and($array1)->not->toHaveKey('score')
            ->and($array1['bio'])->toBeNull();
    });

    test('Edge Case B: Nullable properties - not provided is excluded', function(): void {
        // Don't provide bio at all
        $dto = EdgeCaseProfileLiteDto::from([
            'name' => 'Bob',
        ]);

        $array = $dto->toArrayOnlyExplicitlySet();

        expect($array)->toHaveKey('name')
            ->and($array)->not->toHaveKey('bio')
            ->and($array)->not->toHaveKey('website')
            ->and($array)->not->toHaveKey('score');
    });

    test('Edge Case C: Mapped properties use correct output names', function(): void {
        $dto = EdgeCaseProductLiteDto::from([
            'product_id' => 1,
            'external_sku' => 'SKU-001',
        ]);

        $array = $dto->toArrayOnlyExplicitlySet();

        // Should use MapTo name for output
        expect($array)->toHaveKey('product_id')
            ->and($array)->toHaveKey('sku_code')
            ->and($array)->not->toHaveKey('sku')
            ->and($array)->not->toHaveKey('external_sku')
            ->and($array)->not->toHaveKey('product_name')
            ->and($array['sku_code'])->toBe('SKU-001');
    });

    test('Edge Case C: wasPropertyExplicitlySet() works with mapped properties', function(): void {
        $dto = EdgeCaseProductLiteDto::from([
            'external_sku' => 'SKU-123',
            'product_name' => 'Widget',
        ]);

        expect($dto->wasPropertyExplicitlySet('sku'))->toBeTrue()
            ->and($dto->wasPropertyExplicitlySet('name'))->toBeTrue()
            ->and($dto->wasPropertyExplicitlySet('price'))->toBeFalse();
    });

    test('Edge Case D: Nested DTOs are converted to arrays', function(): void {
        $dto = EdgeCaseCompanyLiteDto::from([
            'name' => 'Tech Corp',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Berlin',
                'zip' => '10115',
            ],
        ]);

        $array = $dto->toArrayOnlyExplicitlySet();

        expect($array)->toHaveKey('name')
            ->and($array)->toHaveKey('address')
            ->and($array['address'])->toBeArray()
            ->and($array['address']['street'])->toBe('123 Main St')
            ->and($array['address']['city'])->toBe('Berlin');
    });

    test('Edge Case E: Collections of DTOs are converted to arrays', function(): void {
        $dto = EdgeCaseOrderLiteDto::from([
            'order_number' => 'ORD-001',
            'items' => [
                ['product_name' => 'Widget', 'quantity' => 2, 'price' => 10.00],
                ['product_name' => 'Gadget', 'quantity' => 1, 'price' => 25.00],
            ],
        ]);

        $array = $dto->toArrayOnlyExplicitlySet();

        expect($array)->toHaveKey('order_number')
            ->and($array)->toHaveKey('items')
            ->and($array['items'])->toBeArray()
            ->and($array['items'])->toHaveCount(2)
            ->and($array['items'][0]['product_name'])->toBe('Widget');
    });

    test('Edge Case F: Inheritance preserves parent properties', function(): void {
        $dto = EdgeCaseEmployeeLiteDto::from([
            'name' => 'John',
            'email' => 'john@example.com',
            'department' => 'Engineering',
        ]);

        $array = $dto->toArrayOnlyExplicitlySet();

        // All explicitly set properties should be included (parent + child)
        expect($array)->toHaveKey('name')
            ->and($array)->toHaveKey('email')
            ->and($array)->toHaveKey('department')
            ->and($array)->not->toHaveKey('id')
            ->and($array['name'])->toBe('John')
            ->and($array['email'])->toBe('john@example.com')
            ->and($array['department'])->toBe('Engineering');
    });

    test('Edge Case F: wasPropertyExplicitlySet() works with inherited properties', function(): void {
        $dto = EdgeCaseEmployeeLiteDto::from([
            'name' => 'Jane',
            'department' => 'Product',
        ]);

        expect($dto->wasPropertyExplicitlySet('name'))->toBeTrue()
            ->and($dto->wasPropertyExplicitlySet('email'))->toBeFalse()
            ->and($dto->wasPropertyExplicitlySet('department'))->toBeTrue()
            ->and($dto->wasPropertyExplicitlySet('id'))->toBeFalse();
    });
});
