<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto;
use event4u\DataHelpers\LiteDto\Attributes\MapFrom;

describe('LiteDto Null to Default Value Tests', function(): void {
    describe('Non-Nullable Parameters with Default Values', function(): void {
        it('uses default value when field is missing', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly float $amount = 0.0,
                ) {}
            };

            $result = $dto::from(['name' => 'Test']);

            expect($result->name)->toBe('Test')
                ->and($result->amount)->toBe(0.0);
        });

        it('uses default value when field is null (non-nullable)', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly float $amount = 0.0,
                ) {}
            };

            $result = $dto::from(['name' => 'Test', 'amount' => null]);

            expect($result->name)->toBe('Test')
                ->and($result->amount)->toBe(0.0);
        });

        it('uses provided value when field has value', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly float $amount = 0.0,
                ) {}
            };

            $result = $dto::from(['name' => 'Test', 'amount' => 42.5]);

            expect($result->name)->toBe('Test')
                ->and($result->amount)->toBe(42.5);
        });

        it('handles multiple non-nullable parameters with defaults', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly float $amount = 0.0,
                    public readonly int $quantity = 1,
                    public readonly bool $active = true,
                ) {}
            };

            $result = $dto::from([
                'name' => 'Test',
                'amount' => null,
                'quantity' => null,
                'active' => null,
            ]);

            expect($result->name)->toBe('Test')
                ->and($result->amount)->toBe(0.0)
                ->and($result->quantity)->toBe(1)
                ->and($result->active)->toBe(true);
        });

        it('handles string default value with null', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = 'default',
                    public readonly string $description = 'No description',
                ) {}
            };

            $result = $dto::from([
                'name' => null,
                'description' => null,
            ]);

            expect($result->name)->toBe('default')
                ->and($result->description)->toBe('No description');
        });

        it('handles array default value with null', function(): void {
            $dto = new class extends LiteDto {
                /** @param array<string> $tags */
                public function __construct(
                    public readonly string $name = '',
                    public readonly array $tags = [],
                ) {}
            };

            $result = $dto::from([
                'name' => 'Test',
                'tags' => null,
            ]);

            expect($result->name)->toBe('Test')
                ->and($result->tags)->toBe([]);
        });
    });

    describe('Nullable Parameters with Default Values', function(): void {
        it('uses default value when field is missing (nullable)', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly ?float $amount = 0.0,
                ) {}
            };

            $result = $dto::from(['name' => 'Test']);

            expect($result->name)->toBe('Test')
                ->and($result->amount)->toBe(0.0);
        });

        it('keeps null when field is null (nullable)', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly ?float $amount = 0.0,
                ) {}
            };

            $result = $dto::from(['name' => 'Test', 'amount' => null]);

            expect($result->name)->toBe('Test')
                ->and($result->amount)->toBeNull();
        });

        it('uses provided value when field has value (nullable)', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly ?float $amount = 0.0,
                ) {}
            };

            $result = $dto::from(['name' => 'Test', 'amount' => 42.5]);

            expect($result->name)->toBe('Test')
                ->and($result->amount)->toBe(42.5);
        });

        it('handles multiple nullable parameters with defaults', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly ?float $amount = 0.0,
                    public readonly ?int $quantity = 1,
                    public readonly ?bool $active = true,
                ) {}
            };

            $result = $dto::from([
                'name' => 'Test',
                'amount' => null,
                'quantity' => null,
                'active' => null,
            ]);

            expect($result->name)->toBe('Test')
                ->and($result->amount)->toBeNull()
                ->and($result->quantity)->toBeNull()
                ->and($result->active)->toBeNull();
        });

        it('handles nullable string with null default', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly ?string $description = null,
                ) {}
            };

            $result = $dto::from([
                'name' => 'Test',
                'description' => null,
            ]);

            expect($result->name)->toBe('Test')
                ->and($result->description)->toBeNull();
        });
    });

    describe('Mixed Nullable and Non-Nullable Parameters', function(): void {
        it('handles mix of nullable and non-nullable with null values', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly float $price = 0.0,           // Non-nullable
                    public readonly ?float $discount = 0.0,       // Nullable
                    public readonly int $quantity = 1,            // Non-nullable
                    public readonly ?string $notes = null,        // Nullable
                ) {}
            };

            $result = $dto::from([
                'name' => 'Product',
                'price' => null,      // Should use default (0.0)
                'discount' => null,   // Should stay null
                'quantity' => null,   // Should use default (1)
                'notes' => null,      // Should stay null
            ]);

            expect($result->name)->toBe('Product')
                ->and($result->price)->toBe(0.0)
                ->and($result->discount)->toBeNull()
                ->and($result->quantity)->toBe(1)
                ->and($result->notes)->toBeNull();
        });

        it('handles partial null values with mix of nullable and non-nullable', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    public readonly float $price = 0.0,           // Non-nullable
                    public readonly ?float $discount = 0.0,       // Nullable
                    public readonly int $quantity = 1,            // Non-nullable
                    public readonly ?string $notes = null,        // Nullable
                ) {}
            };

            $result = $dto::from([
                'name' => 'Product',
                'price' => 99.99,     // Has value
                'discount' => null,   // Should stay null
                'quantity' => null,   // Should use default (1)
                // notes is missing - should use default (null)
            ]);

            expect($result->name)->toBe('Product')
                ->and($result->price)->toBe(99.99)
                ->and($result->discount)->toBeNull()
                ->and($result->quantity)->toBe(1)
                ->and($result->notes)->toBeNull();
        });
    });

    describe('With MapFrom Attribute', function(): void {
        it('uses default when mapped field is null (non-nullable)', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    #[MapFrom('product_amount')]
                    public readonly float $amount = 0.0,
                ) {}
            };

            $result = $dto::from([
                'name' => 'Test',
                'product_amount' => null,
            ]);

            expect($result->name)->toBe('Test')
                ->and($result->amount)->toBe(0.0);
        });

        it('keeps null when mapped field is null (nullable)', function(): void {
            $dto = new class extends LiteDto {
                public function __construct(
                    public readonly string $name = '',
                    #[MapFrom('product_amount')]
                    public readonly ?float $amount = 0.0,
                ) {}
            };

            $result = $dto::from([
                'name' => 'Test',
                'product_amount' => null,
            ]);

            expect($result->name)->toBe('Test')
                ->and($result->amount)->toBeNull();
        });
    });
});
