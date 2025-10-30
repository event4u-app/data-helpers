<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\DataCollectionOf;
use event4u\DataHelpers\LiteDto\Attributes\MapInputName;
use event4u\DataHelpers\LiteDto\Attributes\MapOutputName;
use event4u\DataHelpers\LiteDto\LiteDto;
use event4u\DataHelpers\SimpleDto\Enums\NamingConvention;

// Test DTOs for MapInputName
#[MapInputName(NamingConvention::SnakeCase)]
class MapInputNameSnakeCaseDto extends LiteDto
{
    public function __construct(
        public readonly string $userName,
        public readonly string $emailAddress,
        public readonly int $userId,
    ) {}
}

#[MapInputName(NamingConvention::KebabCase)]
class MapInputNameKebabCaseDto extends LiteDto
{
    public function __construct(
        public readonly string $productName,
        public readonly float $productPrice,
    ) {}
}

#[MapInputName(NamingConvention::PascalCase)]
class MapInputNamePascalCaseDto extends LiteDto
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}
}

// Test DTOs for MapOutputName
#[MapOutputName(NamingConvention::SnakeCase)]
class MapOutputNameSnakeCaseDto extends LiteDto
{
    public function __construct(
        public readonly string $userName,
        public readonly string $emailAddress,
        public readonly int $userId,
    ) {}
}

#[MapOutputName(NamingConvention::KebabCase)]
class MapOutputNameKebabCaseDto extends LiteDto
{
    public function __construct(
        public readonly string $productName,
        public readonly float $productPrice,
    ) {}
}

#[MapOutputName(NamingConvention::PascalCase)]
class MapOutputNamePascalCaseDto extends LiteDto
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
    ) {}
}

// Test DTOs for DataCollectionOf
class OrderItemDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
    ) {}
}

class OrderDto extends LiteDto
{
    /** @param array<OrderItemDto> $items */
    public function __construct(
        public readonly string $orderId,
        #[DataCollectionOf(OrderItemDto::class)]
        public readonly array $items,
    ) {}
}

class AddressDto extends LiteDto
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
    ) {}
}

class UserWithAddressesDto extends LiteDto
{
    /** @param array<AddressDto> $addresses */
    public function __construct(
        public readonly string $name,
        #[DataCollectionOf(AddressDto::class)]
        public readonly array $addresses,
    ) {}
}

// Test DTOs for Combined Attributes
#[MapInputName(NamingConvention::SnakeCase)]
#[MapOutputName(NamingConvention::KebabCase)]
class CombinedMappingDto extends LiteDto
{
    public function __construct(
        public readonly string $userName,
        public readonly string $emailAddress,
    ) {}
}

class OrderItemWithNamesDto extends LiteDto
{
    public function __construct(
        public readonly string $itemName,
        public readonly float $itemPrice,
    ) {}
}

#[MapInputName(NamingConvention::SnakeCase)]
class OrderWithItemsDto extends LiteDto
{
    /** @param array<OrderItemWithNamesDto> $orderItems */
    public function __construct(
        public readonly string $orderId,
        #[DataCollectionOf(OrderItemWithNamesDto::class)]
        public readonly array $orderItems,
    ) {}
}

describe('LiteDto Mapping Attributes', function(): void {
    describe('MapInputName Attribute', function(): void {
        it('transforms input keys from snake_case to camelCase', function(): void {
            $instance = MapInputNameSnakeCaseDto::from([
                'user_name' => 'Jane Smith',
                'email_address' => 'jane@example.com',
                'user_id' => 123,
            ]);

            expect($instance->userName)->toBe('Jane Smith');
            expect($instance->emailAddress)->toBe('jane@example.com');
            expect($instance->userId)->toBe(123);
        });

        it('transforms input keys from kebab-case to camelCase', function(): void {
            $instance = MapInputNameKebabCaseDto::from([
                'product-name' => 'Laptop',
                'product-price' => 1299.99,
            ]);

            expect($instance->productName)->toBe('Laptop');
            expect($instance->productPrice)->toBe(1299.99);
        });

        it('transforms input keys from PascalCase to camelCase', function(): void {
            $instance = MapInputNamePascalCaseDto::from([
                'FirstName' => 'Jane',
                'LastName' => 'Smith',
            ]);

            expect($instance->firstName)->toBe('Jane');
            expect($instance->lastName)->toBe('Smith');
        });
    });

    describe('MapOutputName Attribute', function(): void {
        it('transforms output keys from camelCase to snake_case', function(): void {
            $dto = new MapOutputNameSnakeCaseDto('John Doe', 'john@example.com', 42);
            $array = $dto->toArray();

            expect($array)->toHaveKey('user_name');
            expect($array)->toHaveKey('email_address');
            expect($array)->toHaveKey('user_id');
            expect($array['user_name'])->toBe('John Doe');
            expect($array['email_address'])->toBe('john@example.com');
            expect($array['user_id'])->toBe(42);
        });

        it('transforms output keys from camelCase to kebab-case', function(): void {
            $dto = new MapOutputNameKebabCaseDto('Product', 99.99);
            $array = $dto->toArray();

            expect($array)->toHaveKey('product-name');
            expect($array)->toHaveKey('product-price');
            expect($array['product-name'])->toBe('Product');
            expect($array['product-price'])->toBe(99.99);
        });

        it('transforms output keys from camelCase to PascalCase', function(): void {
            $dto = new MapOutputNamePascalCaseDto('John', 'Doe');
            $array = $dto->toArray();

            expect($array)->toHaveKey('FirstName');
            expect($array)->toHaveKey('LastName');
            expect($array['FirstName'])->toBe('John');
            expect($array['LastName'])->toBe('Doe');
        });

        it('works with toJson()', function(): void {
            $dto = new MapOutputNameSnakeCaseDto('John Doe', 'john@example.com', 42);
            $json = json_decode($dto->toJson(), true);

            expect($json)->toHaveKey('user_name');
            expect($json)->toHaveKey('email_address');
            expect($json['user_name'])->toBe('John Doe');
            expect($json['email_address'])->toBe('john@example.com');
        });
    });

    describe('DataCollectionOf Attribute', function(): void {
        it('converts array of arrays to array of DTOs', function(): void {
            $order = OrderDto::from([
                'orderId' => 'ORD-123',
                'items' => [
                    ['name' => 'Item 1', 'price' => 10.0],
                    ['name' => 'Item 2', 'price' => 20.0],
                    ['name' => 'Item 3', 'price' => 30.0],
                ],
            ]);

            expect($order->orderId)->toBe('ORD-123');
            expect($order->items)->toBeArray();
            expect($order->items)->toHaveCount(3);
            expect($order->items[0])->toBeInstanceOf(OrderItemDto::class);
            expect($order->items[0]->name)->toBe('Item 1');
            expect($order->items[0]->price)->toBe(10.0);
            expect($order->items[1]->name)->toBe('Item 2');
            expect($order->items[2]->name)->toBe('Item 3');
        });

        it('works with nested DTOs', function(): void {
            $user = UserWithAddressesDto::from([
                'name' => 'John Doe',
                'addresses' => [
                    ['street' => '123 Main St', 'city' => 'New York'],
                    ['street' => '456 Oak Ave', 'city' => 'Los Angeles'],
                ],
            ]);

            expect($user->name)->toBe('John Doe');
            expect($user->addresses)->toHaveCount(2);
            expect($user->addresses[0])->toBeInstanceOf(AddressDto::class);
            expect($user->addresses[0]->street)->toBe('123 Main St');
            expect($user->addresses[0]->city)->toBe('New York');
            expect($user->addresses[1]->street)->toBe('456 Oak Ave');
            expect($user->addresses[1]->city)->toBe('Los Angeles');
        });
    });

    describe('Combined Mapping Attributes', function(): void {
        it('combines MapInputName and MapOutputName', function(): void {
            // Input: snake_case
            $instance = CombinedMappingDto::from([
                'user_name' => 'John Doe',
                'email_address' => 'john@example.com',
            ]);

            expect($instance->userName)->toBe('John Doe');
            expect($instance->emailAddress)->toBe('john@example.com');

            // Output: kebab-case
            $array = $instance->toArray();
            expect($array)->toHaveKey('user-name');
            expect($array)->toHaveKey('email-address');
            expect($array['user-name'])->toBe('John Doe');
            expect($array['email-address'])->toBe('john@example.com');
        });

        it('combines MapInputName with DataCollectionOf', function(): void {
            $order = OrderWithItemsDto::from([
                'order_id' => 'ORD-456',
                'order_items' => [
                    ['itemName' => 'Product A', 'itemPrice' => 50.0],
                    ['itemName' => 'Product B', 'itemPrice' => 75.0],
                ],
            ]);

            expect($order->orderId)->toBe('ORD-456');
            expect($order->orderItems)->toHaveCount(2);
            expect($order->orderItems[0]->itemName)->toBe('Product A');
            expect($order->orderItems[0]->itemPrice)->toBe(50.0);
        });
    });
});
