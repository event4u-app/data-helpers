<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\Between;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\Required;
use event4u\DataHelpers\SimpleDto\DataCollection;

// Test Dtos
class IDESupportUserDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {}
}

class IDESupportProductDto extends SimpleDto
{
    public function __construct(
        #[Required]
        public readonly string $name,
        #[Required]
        #[Between(0, 999999)]
        public readonly float $price,
    ) {}
}

class IDESupportOrderDto extends SimpleDto
{
    public function __construct(
        public readonly int $id,
        public readonly DateTimeImmutable $createdAt,
        public readonly float $total,
    ) {}

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'createdAt' => 'datetime',
            'total' => 'float',
        ];
    }
}

class IDESupportCustomerDto extends SimpleDto
{
    public function __construct(
        #[MapFrom('customer_id')]
        public readonly int $id,
        #[MapFrom('customer_name')]
        public readonly string $name,
    ) {}
}

describe('IDE Support', function(): void {
    it('provides type inference for fromArray()', function(): void {
        $user = IDESupportUserDto::fromArray([
            'name' => 'John Doe',
            'age' => 30,
        ]);

        expect($user)->toBeInstanceOf(IDESupportUserDto::class)
            ->and($user->name)->toBe('John Doe')
            ->and($user->age)->toBe(30);
    });

    it('provides type inference for collection()', function(): void {
        $users = IDESupportUserDto::collection([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
        ]);

        expect($users)->toBeInstanceOf(DataCollection::class)
            ->and($users->count())->toBe(2)
            ->and($users->first())->toBeInstanceOf(IDESupportUserDto::class);
    });

    it('provides type inference for DataCollection::forDto()', function(): void {
        $users = DataCollection::forDto(IDESupportUserDto::class, [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
        ]);

        expect($users)->toBeInstanceOf(DataCollection::class)
            ->and($users->count())->toBe(2)
            ->and($users->first())->toBeInstanceOf(IDESupportUserDto::class);
    });

    it('provides type inference for DataCollection::wrapDto()', function(): void {
        $user = IDESupportUserDto::fromArray(['name' => 'John', 'age' => 30]);
        $users = DataCollection::wrapDto(IDESupportUserDto::class, [$user]);

        expect($users)->toBeInstanceOf(DataCollection::class)
            ->and($users->count())->toBe(1)
            ->and($users->first())->toBeInstanceOf(IDESupportUserDto::class);
    });

    it('supports validation attributes', function(): void {
        $product = IDESupportProductDto::fromArray([
            'name' => 'Laptop',
            'price' => 999.99,
        ]);

        expect($product)->toBeInstanceOf(IDESupportProductDto::class)
            ->and($product->name)->toBe('Laptop')
            ->and($product->price)->toBe(999.99);
    });

    it('supports cast types', function(): void {
        $order = IDESupportOrderDto::fromArray([
            'id' => 1,
            'createdAt' => '2024-01-01 12:00:00',
            'total' => '99.99',
        ]);

        expect($order)->toBeInstanceOf(IDESupportOrderDto::class)
            ->and($order->id)->toBe(1)
            ->and($order->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($order->total)->toBe(99.99);
    });

    it('supports property mapping', function(): void {
        $customer = IDESupportCustomerDto::fromArray([
            'customer_id' => 1,
            'customer_name' => 'John Doe',
        ]);

        expect($customer)->toBeInstanceOf(IDESupportCustomerDto::class)
            ->and($customer->id)->toBe(1)
            ->and($customer->name)->toBe('John Doe');
    });

    it('supports DataCollectionOf attribute', function(): void {
        $members = DataCollection::forDto(IDESupportUserDto::class, [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
        ]);

        expect($members)->toBeInstanceOf(DataCollection::class)
            ->and($members->count())->toBe(2)
            ->and($members->first())->toBeInstanceOf(IDESupportUserDto::class);
    });

    it('supports generic type hints', function(): void {
        $users = DataCollection::forDto(IDESupportUserDto::class, [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
        ]);

        // Simulate a function that uses generic type hints
        $getUserNames = function(DataCollection $users): array {
            $names = [];
            foreach ($users as $user) {
                /** @phpstan-ignore-next-line unknown */
                $names[] = $user->name;
            }
            return $names;
        };

        $names = $getUserNames($users);

        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        expect($names)->toBe(['John', 'Jane']);
    });

    it('verifies .phpstorm.meta.php file exists', function(): void {
        $metaFile = __DIR__ . '/../../../.phpstorm.meta.php';
        expect(file_exists($metaFile))->toBeTrue();
    });

    it('verifies .phpstorm.meta.php contains required overrides', function(): void {
        $metaFile = __DIR__ . '/../../../.phpstorm.meta.php';
        $content = file_get_contents($metaFile);

        expect($content)->toContain('override(SimpleDto::fromArray(0)')
            ->and($content)->toContain('override(SimpleDto::collection(0)')
            ->and($content)->toContain('override(DataCollection::forDto(0)')
            ->and($content)->toContain('override(DataCollection::wrapDto(0)');
    });

    it('verifies .phpstorm.meta.php contains cast type suggestions', function(): void {
        $metaFile = __DIR__ . '/../../../.phpstorm.meta.php';
        $content = file_get_contents($metaFile);

        expect($content)->toContain('simpleDtoCastTypes')
            ->and($content)->toContain("'datetime'")
            ->and($content)->toContain("'boolean'")
            ->and($content)->toContain("'collection'");
    });

    it('verifies .phpstorm.meta.php contains validation attribute suggestions', function(): void {
        $metaFile = __DIR__ . '/../../../.phpstorm.meta.php';
        $content = file_get_contents($metaFile);

        expect($content)->toContain('validationRuleValues')
            ->and($content)->toContain('Between::__construct()')
            ->and($content)->toContain('Min::__construct()')
            ->and($content)->toContain('Max::__construct()');
    });

    it('verifies .phpstorm.meta.php contains property mapping suggestions', function(): void {
        $metaFile = __DIR__ . '/../../../.phpstorm.meta.php';
        $content = file_get_contents($metaFile);

        expect($content)->toContain('commonPropertyNames')
            ->and($content)->toContain('MapFrom::__construct()')
            ->and($content)->toContain("'created_at'")
            ->and($content)->toContain("'user.name'");
    });

    it('verifies .phpstorm.meta.php contains naming convention suggestions', function(): void {
        $metaFile = __DIR__ . '/../../../.phpstorm.meta.php';
        $content = file_get_contents($metaFile);

        expect($content)->toContain('namingConventions')
            ->and($content)->toContain('MapInputName::__construct()')
            ->and($content)->toContain("'snake_case'")
            ->and($content)->toContain("'camelCase'");
    });
});
