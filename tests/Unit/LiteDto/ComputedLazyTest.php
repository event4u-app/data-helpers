<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\Computed;
use event4u\DataHelpers\LiteDto\Attributes\Lazy;
use event4u\DataHelpers\LiteDto\LiteDto;

// Test DTOs for Computed
class OrderWithComputedDto extends LiteDto
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

class OrderWithCustomNameDto extends LiteDto
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

class OrderWithLazyComputedDto extends LiteDto
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

// Test DTOs for Lazy
class UserWithLazyDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[Lazy]
        public readonly string $biography,
    ) {}
}

class ProductWithMultipleLazyDto extends LiteDto
{
    public function __construct(
        public readonly string $name,
        #[Lazy]
        public readonly string $description,
        #[Lazy]
        public readonly string $specifications,
    ) {}
}

// Test DTOs for Combined
class OrderWithComputedAndLazyDto extends LiteDto
{
    public function __construct(
        public readonly float $price,
        public readonly int $quantity,
        #[Lazy]
        public readonly string $internalNotes,
    ) {}

    #[Computed]
    public function total(): float
    {
        return $this->price * $this->quantity;
    }
}

describe('LiteDto Computed & Lazy Attributes', function(): void {
    describe('Computed Attribute', function(): void {
        it('includes computed properties in toArray()', function(): void {
            $order = new OrderWithComputedDto(100.0, 2);
            $array = $order->toArray();

            expect($array)->toHaveKey('price');
            expect($array)->toHaveKey('quantity');
            expect($array)->toHaveKey('total');
            expect($array['price'])->toBe(100.0);
            expect($array['quantity'])->toBe(2);
            expect($array['total'])->toBe(200.0);
        });

        it('includes computed properties in toJson()', function(): void {
            $order = new OrderWithComputedDto(50.0, 3);
            $json = json_decode($order->toJson(), true);

            expect($json)->toHaveKey('price');
            expect($json)->toHaveKey('quantity');
            expect($json)->toHaveKey('total');
            expect($json['total'])->toBe(150); // JSON converts 150.0 to 150
        });

        it('supports custom names for computed properties', function(): void {
            $order = new OrderWithCustomNameDto(100.0, 2);
            $array = $order->toArray();

            expect($array)->toHaveKey('orderTotal');
            expect($array)->not->toHaveKey('total');
            expect($array['orderTotal'])->toBe(200.0);
        });

        it('excludes lazy computed properties from toArray()', function(): void {
            $order = new OrderWithLazyComputedDto(100.0, 2);
            $array = $order->toArray();

            expect($array)->toHaveKey('total');
            expect($array)->not->toHaveKey('expensiveCalculation');
            expect($array['total'])->toBe(200.0);
        });

        it('works with from() method', function(): void {
            $order = OrderWithComputedDto::from(['price' => 25.0, 'quantity' => 4]);
            $array = $order->toArray();

            expect($array['total'])->toBe(100.0);
        });
    });

    describe('Lazy Attribute', function(): void {
        it('excludes lazy properties from toArray()', function(): void {
            $user = new UserWithLazyDto('John Doe', 'Long biography text...');
            $array = $user->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->not->toHaveKey('biography');
            expect($array['name'])->toBe('John Doe');
        });

        it('excludes lazy properties from toJson()', function(): void {
            $user = new UserWithLazyDto('Jane Smith', 'Another long text...');
            $json = json_decode($user->toJson(), true);

            expect($json)->toHaveKey('name');
            expect($json)->not->toHaveKey('biography');
            expect($json['name'])->toBe('Jane Smith');
        });

        it('excludes multiple lazy properties', function(): void {
            $product = new ProductWithMultipleLazyDto(
                'Laptop',
                'Long description...',
                'Technical specifications...'
            );
            $array = $product->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->not->toHaveKey('description');
            expect($array)->not->toHaveKey('specifications');
            expect($array['name'])->toBe('Laptop');
        });

        it('works with from() method', function(): void {
            $user = UserWithLazyDto::from([
                'name' => 'Bob',
                'biography' => 'Bio text...',
            ]);
            $array = $user->toArray();

            expect($array)->toHaveKey('name');
            expect($array)->not->toHaveKey('biography');
            expect($user->biography)->toBe('Bio text...');
        });
    });

    describe('Combined Computed & Lazy', function(): void {
        it('includes computed and excludes lazy properties', function(): void {
            $order = new OrderWithComputedAndLazyDto(100.0, 2, 'Internal notes...');
            $array = $order->toArray();

            expect($array)->toHaveKey('price');
            expect($array)->toHaveKey('quantity');
            expect($array)->toHaveKey('total');
            expect($array)->not->toHaveKey('internalNotes');
            expect($array['total'])->toBe(200.0);
        });

        it('works with from() method', function(): void {
            $order = OrderWithComputedAndLazyDto::from([
                'price' => 50.0,
                'quantity' => 3,
                'internalNotes' => 'Secret notes...',
            ]);
            $array = $order->toArray();

            expect($array['total'])->toBe(150.0);
            expect($array)->not->toHaveKey('internalNotes');
            expect($order->internalNotes)->toBe('Secret notes...');
        });
    });

    describe('Performance: Feature Flags', function(): void {
        it('has minimal overhead when no attributes are used', function(): void {
            $dto = new class('test') extends LiteDto {
                public function __construct(
                    public readonly string $name,
                ) {}
            };

            $start = hrtime(true);
            for ($i = 0; 1000 > $i; $i++) {
                $dto->toArray();
            }
            $end = hrtime(true);
            $duration = ($end - $start) / 1_000_000; // Convert to milliseconds

            // Should be very fast (< 10ms for 1000 iterations)
            expect($duration)->toBeLessThan(10.0);
        });
    });
});
