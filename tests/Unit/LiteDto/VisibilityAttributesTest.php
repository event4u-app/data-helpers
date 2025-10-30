<?php

declare(strict_types=1);

use event4u\DataHelpers\LiteDto\Attributes\Hidden;
use event4u\DataHelpers\LiteDto\Attributes\HiddenFromArray;
use event4u\DataHelpers\LiteDto\Attributes\HiddenFromJson;
use event4u\DataHelpers\LiteDto\LiteDto;

describe('LiteDto Visibility Attributes', function(): void {
    describe('Hidden Attribute', function(): void {
        it('hides properties from both toArray() and toJson()', function(): void {
            $dto = new class('John', 'john@example.com', 'secret123') extends LiteDto {
                public function __construct(
                    public readonly string $name,
                    public readonly string $email,
                    #[Hidden]
                    public readonly string $password,
                ) {}
            };

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
            $dto = new class('Product', 'INT-SKU-001') extends LiteDto {
                public function __construct(
                    public readonly string $name,
                    #[HiddenFromArray]
                    public readonly string $internalSku,
                ) {}
            };

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

        it('works with multiple properties', function(): void {
            $dto = new class('Laptop', 999.99, 'INT-LAP-001', 42) extends LiteDto {
                public function __construct(
                    public readonly string $name,
                    public readonly float $price,
                    #[HiddenFromArray]
                    public readonly string $internalSku,
                    #[HiddenFromArray]
                    public readonly int $stockLevel,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKeys(['name', 'price']);
            expect($array)->not->toHaveKey('internalSku');
            expect($array)->not->toHaveKey('stockLevel');

            $json = json_decode($dto->toJson(), true);
            expect($json)->toHaveKeys(['name', 'price', 'internalSku', 'stockLevel']);
        });
    });

    describe('HiddenFromJson Attribute', function(): void {
        it('hides properties only from toJson()', function(): void {
            $dto = new class('ORD-123', 100.0, 'Debug: processed in 0.5s') extends LiteDto {
                public function __construct(
                    public readonly string $orderId,
                    public readonly float $total,
                    #[HiddenFromJson]
                    public readonly string $debugInfo,
                ) {}
            };

            // Property is accessible
            expect($dto->debugInfo)->toBe('Debug: processed in 0.5s');

            // Visible in toArray()
            $array = $dto->toArray();
            expect($array)->toHaveKey('orderId');
            expect($array)->toHaveKey('total');
            expect($array)->toHaveKey('debugInfo');
            expect($array['debugInfo'])->toBe('Debug: processed in 0.5s');

            // Hidden from toJson()
            $json = json_decode($dto->toJson(), true);
            expect($json)->toHaveKey('orderId');
            expect($json)->toHaveKey('total');
            expect($json)->not->toHaveKey('debugInfo');
        });

        it('works with multiple properties', function(): void {
            $dto = new class('ORD-456', 200.0, 'Debug info', ['step1', 'step2']) extends LiteDto {
                /** @param array<string> $processingSteps */
                public function __construct(
                    public readonly string $orderId,
                    public readonly float $total,
                    #[HiddenFromJson]
                    public readonly string $debugInfo,
                    #[HiddenFromJson]
                    public readonly array $processingSteps,
                ) {}
            };

            $array = $dto->toArray();
            expect($array)->toHaveKeys(['orderId', 'total', 'debugInfo', 'processingSteps']);

            $json = json_decode($dto->toJson(), true);
            expect($json)->toHaveKeys(['orderId', 'total']);
            expect($json)->not->toHaveKey('debugInfo');
            expect($json)->not->toHaveKey('processingSteps');
        });
    });

    describe('Combined Visibility Attributes', function(): void {
        it('combines Hidden, HiddenFromArray, and HiddenFromJson', function(): void {
            $dto = new class('John', 'john@example.com', 'secret123', 'INT-001', 'Debug info') extends LiteDto {
                public function __construct(
                    public readonly string $name,
                    public readonly string $email,
                    #[Hidden]
                    public readonly string $password,
                    #[HiddenFromArray]
                    public readonly string $internalId,
                    #[HiddenFromJson]
                    public readonly string $debugInfo,
                ) {}
            };

            // All properties accessible
            expect($dto->password)->toBe('secret123');
            expect($dto->internalId)->toBe('INT-001');
            expect($dto->debugInfo)->toBe('Debug info');

            // toArray(): password hidden, internalId hidden, debugInfo visible
            $array = $dto->toArray();
            expect($array)->toHaveKeys(['name', 'email', 'debugInfo']);
            expect($array)->not->toHaveKey('password');
            expect($array)->not->toHaveKey('internalId');

            // toJson(): password hidden, internalId visible, debugInfo hidden
            $json = json_decode($dto->toJson(), true);
            expect($json)->toHaveKeys(['name', 'email', 'internalId']);
            expect($json)->not->toHaveKey('password');
            expect($json)->not->toHaveKey('debugInfo');
        });
    });
});
