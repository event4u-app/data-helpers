<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\Computed;
use event4u\DataHelpers\LiteDto\Attributes\Hidden;
use event4u\DataHelpers\LiteDto\Attributes\HiddenFromArray;
use event4u\DataHelpers\LiteDto\Attributes\HiddenFromJson;
use event4u\DataHelpers\LiteDto\Attributes\Lazy;
use event4u\DataHelpers\LiteDto\Attributes\UltraFast;
use event4u\DataHelpers\LiteDto\LiteDto;

// UltraFast with Hidden
#[UltraFast]
class UltraFastWithHiddenDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        #[Hidden]
        public readonly string $password,
    ) {}
}

// UltraFast with HiddenFromArray
#[UltraFast]
class UltraFastWithHiddenFromArrayDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[HiddenFromArray]
        public readonly string $internalSku,
    ) {}
}

// UltraFast with HiddenFromJson
#[UltraFast]
class UltraFastWithHiddenFromJsonDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[HiddenFromJson]
        public readonly string $debugInfo,
    ) {}
}

// UltraFast with Lazy
#[UltraFast]
class UltraFastWithLazyDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[Lazy]
        public readonly string $biography,
    ) {}
}

// UltraFast with Computed
#[UltraFast]
class UltraFastWithComputedDto extends LiteDto
{
    public function __construct(
        public readonly float $price,
        public readonly int $quantity,
    ) {}

    #[Computed]
    public function total(): float
    {
        return $this->price * $this->quantity;
    }
}

// UltraFast with Computed (custom name)
#[UltraFast]
class UltraFastWithComputedCustomNameDto extends LiteDto
{
    public function __construct(
        public readonly float $price,
        public readonly int $quantity,
    ) {}

    #[Computed(name: 'orderTotal')]
    public function total(): float
    {
        return $this->price * $this->quantity;
    }
}

// UltraFast with Lazy Computed
#[UltraFast]
class UltraFastWithLazyComputedDto extends LiteDto
{
    public function __construct(
        public readonly float $price,
        public readonly int $quantity,
    ) {}

    #[Computed]
    public function total(): float
    {
        return $this->price * $this->quantity;
    }

    #[Computed(lazy: true)]
    public function expensiveCalculation(): string
    {
        return 'expensive result';
    }
}

// UltraFast with all visibility attributes combined
#[UltraFast]
class UltraFastWithAllVisibilityDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[Hidden]
        public readonly string $password,
        #[HiddenFromArray]
        public readonly string $internalSku,
        #[HiddenFromJson]
        public readonly string $debugInfo,
    ) {}
}

// UltraFast with Computed and Lazy combined
#[UltraFast]
class UltraFastWithComputedAndLazyDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[Lazy]
        public readonly string $biography,
    ) {}

    #[Computed]
    public function displayName(): string
    {
        return strtoupper($this->name);
    }
}

describe('UltraFast with Visibility and Computed', function(): void {
    describe('Hidden Attribute', function(): void {
        it('hides properties from both toArray() and toJson()', function(): void {
            $dto = UltraFastWithHiddenDto::from([
                'name' => 'John',
                'email' => 'john@example.com',
                'password' => 'secret123',
            ]);

            // Property is accessible
            expect($dto->password)->toBe('secret123');

            // Hidden from toArray()
            $array = $dto->toArray();
            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('email');
            expect($array)->not->toHaveKey('password');

            // Hidden from toJson()
            $json = json_decode($dto->toJson(), true);
            expect($json)->toHaveKey('name');
            expect($json)->toHaveKey('email');
            expect($json)->not->toHaveKey('password');
        });
    });

    describe('HiddenFromArray Attribute', function(): void {
        it('hides properties only from toArray()', function(): void {
            $dto = UltraFastWithHiddenFromArrayDto::from([
                'name' => 'Product',
                'internalSku' => 'INT-SKU-001',
            ]);

            // Property is accessible
            expect($dto->internalSku)->toBe('INT-SKU-001');

            // Hidden from toArray()
            $array = $dto->toArray();
            expect($array)->toHaveKey('name');
            expect($array)->not->toHaveKey('internalSku');

            // Visible in toJson()
            $json = json_decode($dto->toJson(), true);
            expect($json)->toHaveKey('name');
            expect($json)->toHaveKey('internalSku');
            expect($json['internalSku'])->toBe('INT-SKU-001');
        });
    });

    describe('HiddenFromJson Attribute', function(): void {
        it('hides properties only from toJson()', function(): void {
            $dto = UltraFastWithHiddenFromJsonDto::from([
                'name' => 'Product',
                'debugInfo' => 'Debug data',
            ]);

            // Property is accessible
            expect($dto->debugInfo)->toBe('Debug data');

            // Visible in toArray()
            $array = $dto->toArray();
            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('debugInfo');
            expect($array['debugInfo'])->toBe('Debug data');

            // Hidden from toJson()
            $json = json_decode($dto->toJson(), true);
            expect($json)->toHaveKey('name');
            expect($json)->not->toHaveKey('debugInfo');
        });
    });

    describe('Lazy Attribute', function(): void {
        it('excludes lazy properties from toArray()', function(): void {
            $dto = UltraFastWithLazyDto::from([
                'name' => 'John',
                'biography' => 'Long biography text...',
            ]);

            // Property is accessible
            expect($dto->biography)->toBe('Long biography text...');

            // Excluded from toArray()
            $array = $dto->toArray();
            expect($array)->toHaveKey('name');
            expect($array)->not->toHaveKey('biography');

            // Excluded from toJson()
            $json = json_decode($dto->toJson(), true);
            expect($json)->toHaveKey('name');
            expect($json)->not->toHaveKey('biography');
        });
    });

    describe('Computed Attribute', function(): void {
        it('includes computed properties in toArray()', function(): void {
            $dto = UltraFastWithComputedDto::from([
                'price' => 10.0,
                'quantity' => 5,
            ]);

            $array = $dto->toArray();
            expect($array)->toHaveKey('price');
            expect($array)->toHaveKey('quantity');
            expect($array)->toHaveKey('total');
            expect($array['total'])->toBe(50.0);
        });

        it('supports custom names for computed properties', function(): void {
            $dto = UltraFastWithComputedCustomNameDto::from([
                'price' => 10.0,
                'quantity' => 5,
            ]);

            $array = $dto->toArray();
            expect($array)->toHaveKey('orderTotal');
            expect($array['orderTotal'])->toBe(50.0);
            expect($array)->not->toHaveKey('total');
        });

        it('excludes lazy computed properties from toArray()', function(): void {
            $dto = UltraFastWithLazyComputedDto::from([
                'price' => 10.0,
                'quantity' => 5,
            ]);

            $array = $dto->toArray();
            expect($array)->toHaveKey('total');
            expect($array['total'])->toBe(50.0);
            expect($array)->not->toHaveKey('expensiveCalculation');
        });
    });

    describe('Combined Attributes', function(): void {
        it('combines all visibility attributes', function(): void {
            $dto = UltraFastWithAllVisibilityDto::from([
                'name' => 'Product',
                'password' => 'secret',
                'internalSku' => 'INT-001',
                'debugInfo' => 'Debug',
            ]);

            // toArray() - password and internalSku hidden
            $array = $dto->toArray();
            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('debugInfo');
            expect($array)->not->toHaveKey('password');
            expect($array)->not->toHaveKey('internalSku');

            // toJson() - password and debugInfo hidden
            $json = json_decode($dto->toJson(), true);
            expect($json)->toHaveKey('name');
            expect($json)->toHaveKey('internalSku');
            expect($json)->not->toHaveKey('password');
            expect($json)->not->toHaveKey('debugInfo');
        });

        it('combines Computed and Lazy', function(): void {
            $dto = UltraFastWithComputedAndLazyDto::from([
                'name' => 'John',
                'biography' => 'Long text...',
            ]);

            $array = $dto->toArray();
            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('displayName');
            expect($array['displayName'])->toBe('JOHN');
            expect($array)->not->toHaveKey('biography');
        });
    });
});
