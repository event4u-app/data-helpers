<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;

describe('Nested Comparison', function(): void {
    it('compares nested arrays with dot notation', function(): void {
        $dto = new class (
            'John',
            ['street' => 'Main St', 'city' => 'New York']
        ) extends SimpleDto {
            /** @param array<string, mixed> $address */
            public function __construct(
                public readonly string $name,
                public readonly array $address,
            ) {
            }
        };

        $user = $dto::fromArray([
            'name' => 'John',
            'address' => [
                'street' => 'Main St',
                'city' => 'New York',
            ],
        ]);

        $diff = $user->diff([
            'name' => 'John',
            'address' => [
                'street' => 'Broadway',
                'city' => 'New York',
            ],
        ]);

        expect($diff)->toBe([
            'address.street' => [
                'dto' => 'Main St',
                'data' => 'Broadway',
            ],
        ]);
    });

    it('compares deeply nested arrays', function(): void {
        $dto = new class (
            'John',
            ['profile' => ['address' => ['street' => 'Main St', 'city' => 'New York']]]
        ) extends SimpleDto {
            /** @param array<string, mixed> $profile */
            public function __construct(
                public readonly string $name,
                public readonly array $profile,
            ) {
            }
        };

        $user = $dto::fromArray([
            'name' => 'John',
            'profile' => [
                'address' => [
                    'street' => 'Main St',
                    'city' => 'New York',
                ],
            ],
        ]);

        $diff = $user->diff([
            'name' => 'John',
            'profile' => [
                'address' => [
                    'street' => 'Broadway',
                    'city' => 'New York',
                ],
            ],
        ]);

        expect($diff)->toBe([
            'profile.address.street' => [
                'dto' => 'Main St',
                'data' => 'Broadway',
            ],
        ]);
    });

    it('disables nested comparison when nested is false', function(): void {
        $dto = new class (
            'John',
            ['street' => 'Main St', 'city' => 'New York']
        ) extends SimpleDto {
            /** @param array<string, mixed> $address */
            public function __construct(
                public readonly string $name,
                public readonly array $address,
            ) {
            }
        };

        $user = $dto::fromArray([
            'name' => 'John',
            'address' => [
                'street' => 'Main St',
                'city' => 'New York',
            ],
        ]);

        $diff = $user->diff([
            'name' => 'John',
            'address' => [
                'street' => 'Broadway',
                'city' => 'New York',
            ],
        ], nested: false);

        expect($diff)->toBe([
            'address' => [
                'dto' => ['street' => 'Main St', 'city' => 'New York'],
                'data' => ['street' => 'Broadway', 'city' => 'New York'],
            ],
        ]);
    });

    it('compares multiple nested differences', function(): void {
        $dto = new class (
            'John',
            ['street' => 'Main St', 'city' => 'New York', 'zip' => '10001']
        ) extends SimpleDto {
            /** @param array<string, mixed> $address */
            public function __construct(
                public readonly string $name,
                public readonly array $address,
            ) {
            }
        };

        $user = $dto::fromArray([
            'name' => 'John',
            'address' => [
                'street' => 'Main St',
                'city' => 'New York',
                'zip' => '10001',
            ],
        ]);

        $diff = $user->diff([
            'name' => 'Jane',
            'address' => [
                'street' => 'Broadway',
                'city' => 'Los Angeles',
                'zip' => '10001',
            ],
        ]);

        expect($diff)->toBe([
            'name' => [
                'dto' => 'John',
                'data' => 'Jane',
            ],
            'address.street' => [
                'dto' => 'Main St',
                'data' => 'Broadway',
            ],
            'address.city' => [
                'dto' => 'New York',
                'data' => 'Los Angeles',
            ],
        ]);
    });
});

describe('Type Variations', function(): void {
    it('compares null values', function(): void {
        $dto = new class ('John', null) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly ?string $email,
            ) {
            }
        };

        $user = $dto::fromArray([
            'name' => 'John',
            'email' => null,
        ]);

        $diff = $user->diff([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        expect($diff)->toBe([
            'email' => [
                'dto' => null,
                'data' => 'john@example.com',
            ],
        ]);
    });

    it('compares boolean values', function(): void {
        $dto = new class ('John', true) extends SimpleDto {
            public function __construct(
                public readonly string $name,
                public readonly bool $active,
            ) {
            }
        };

        $user = $dto::fromArray([
            'name' => 'John',
            'active' => true,
        ]);

        $diff = $user->diff([
            'name' => 'John',
            'active' => false,
        ]);

        expect($diff)->toBe([
            'active' => [
                'dto' => true,
                'data' => false,
            ],
        ]);
    });

    it('compares numeric values', function(): void {
        $dto = new class (100, 99.99) extends SimpleDto {
            public function __construct(
                public readonly int $quantity,
                public readonly float $price,
            ) {
            }
        };

        $product = $dto::fromArray([
            'quantity' => 100,
            'price' => 99.99,
        ]);

        $diff = $product->diff([
            'quantity' => 50,
            'price' => 149.99,
        ]);

        expect($diff)->toBe([
            'quantity' => [
                'dto' => 100,
                'data' => 50,
            ],
            'price' => [
                'dto' => 99.99,
                'data' => 149.99,
            ],
        ]);
    });

    it('compares array values', function(): void {
        $dto = new class (['tag1', 'tag2']) extends SimpleDto {
            /** @param array<int, string> $tags */
            public function __construct(
                public readonly array $tags,
            ) {
            }
        };

        $product = $dto::fromArray([
            'tags' => ['tag1', 'tag2'],
        ]);

        $diff = $product->diff([
            'tags' => ['tag1', 'tag3'],
        ]);

        // Nested comparison is enabled by default, so array elements are compared individually
        expect($diff)->toBe([
            'tags.1' => [
                'dto' => 'tag2',
                'data' => 'tag3',
            ],
        ]);
    });

    it('compares array values without nested comparison', function(): void {
        $dto = new class (['tag1', 'tag2']) extends SimpleDto {
            /** @param array<int, string> $tags */
            public function __construct(
                public readonly array $tags,
            ) {
            }
        };

        $product = $dto::fromArray([
            'tags' => ['tag1', 'tag2'],
        ]);

        $diff = $product->diff([
            'tags' => ['tag1', 'tag3'],
        ], nested: false);

        // With nested=false, arrays are compared as whole values
        expect($diff)->toBe([
            'tags' => [
                'dto' => ['tag1', 'tag2'],
                'data' => ['tag1', 'tag3'],
            ],
        ]);
    });

    it('compares empty arrays', function(): void {
        $dto = new class ([]) extends SimpleDto {
            /** @param array<int, string> $tags */
            public function __construct(
                public readonly array $tags,
            ) {
            }
        };

        $product = $dto::fromArray([
            'tags' => [],
        ]);

        $diff = $product->diff([
            'tags' => ['tag1'],
        ]);

        // Nested comparison detects new element at index 0
        expect($diff)->toBe([
            'tags.0' => [
                'dto' => null,
                'data' => 'tag1',
            ],
        ]);
    });

    it('compares empty arrays without nested comparison', function(): void {
        $dto = new class ([]) extends SimpleDto {
            /** @param array<int, string> $tags */
            public function __construct(
                public readonly array $tags,
            ) {
            }
        };

        $product = $dto::fromArray([
            'tags' => [],
        ]);

        $diff = $product->diff([
            'tags' => ['tag1'],
        ], nested: false);

        // With nested=false, arrays are compared as whole values
        expect($diff)->toBe([
            'tags' => [
                'dto' => [],
                'data' => ['tag1'],
            ],
        ]);
    });

    it('compares empty strings', function(): void {
        $dto = new class ('') extends SimpleDto {
            public function __construct(
                public readonly string $description,
            ) {
            }
        };

        $product = $dto::fromArray([
            'description' => '',
        ]);

        $diff = $product->diff([
            'description' => 'New description',
        ]);

        expect($diff)->toBe([
            'description' => [
                'dto' => '',
                'data' => 'New description',
            ],
        ]);
    });

    it('compares zero values', function(): void {
        $dto = new class (0, 0.0) extends SimpleDto {
            public function __construct(
                public readonly int $count,
                public readonly float $amount,
            ) {
            }
        };

        $product = $dto::fromArray([
            'count' => 0,
            'amount' => 0.0,
        ]);

        $diff = $product->diff([
            'count' => 1,
            'amount' => 1.0,
        ]);

        expect($diff)->toBe([
            'count' => [
                'dto' => 0,
                'data' => 1,
            ],
            'amount' => [
                'dto' => 0.0,
                'data' => 1.0,
            ],
        ]);
    });
});

describe('Edge Cases', function(): void {
    it('handles empty Dto', function(): void {
        $dto = new class extends SimpleDto
        {
        };

        $empty = $dto::fromArray([]);

        $diff = $empty->diff([
            'name' => 'John',
        ]);

        expect($diff)->toBe([
            'name' => [
                'dto' => null,
                'data' => 'John',
            ],
        ]);
    });

    it('handles empty compare data', function(): void {
        $dto = new class ('John') extends SimpleDto {
            public function __construct(
                public readonly string $name,
            ) {
            }
        };

        $user = $dto::fromArray([
            'name' => 'John',
        ]);

        $diff = $user->diff([]);

        expect($diff)->toBe([
            'name' => [
                'dto' => 'John',
                'data' => null,
            ],
        ]);
    });

    it('handles both empty', function(): void {
        $dto = new class extends SimpleDto
        {
        };

        $empty = $dto::fromArray([]);

        $diff = $empty->diff([]);

        expect($diff)->toBe([]);
    });

    it('handles numeric string keys', function(): void {
        $dto = new class (['0' => 'value1', '1' => 'value2']) extends SimpleDto {
            /** @param array<string, string> $items */
            public function __construct(
                public readonly array $items,
            ) {
            }
        };

        $data = $dto::fromArray([
            'items' => ['0' => 'value1', '1' => 'value2'],
        ]);

        $diff = $data->diff([
            'items' => ['0' => 'value1', '1' => 'value3'],
        ]);

        expect($diff)->toBe([
            'items.1' => [
                'dto' => 'value2',
                'data' => 'value3',
            ],
        ]);
    });

    it('handles special characters in keys', function(): void {
        $dto = new class (['key-with-dash' => 'value1', 'key_with_underscore' => 'value2']) extends SimpleDto {
            /** @param array<string, string> $data */
            public function __construct(
                public readonly array $data,
            ) {
            }
        };

        $data = $dto::fromArray([
            'data' => [
                'key-with-dash' => 'value1',
                'key_with_underscore' => 'value2',
            ],
        ]);

        $diff = $data->diff([
            'data' => [
                'key-with-dash' => 'changed',
                'key_with_underscore' => 'value2',
            ],
        ]);

        expect($diff)->toBe([
            'data.key-with-dash' => [
                'dto' => 'value1',
                'data' => 'changed',
            ],
        ]);
    });

    it('handles very deep nesting', function(): void {
        $dto = new class (['a' => ['b' => ['c' => ['d' => ['e' => 'value']]]]]) extends SimpleDto {
            /** @param array<string, mixed> $nested */
            public function __construct(
                public readonly array $nested,
            ) {
            }
        };

        $data = $dto::fromArray([
            'nested' => ['a' => ['b' => ['c' => ['d' => ['e' => 'value']]]]],
        ]);

        $diff = $data->diff([
            'nested' => ['a' => ['b' => ['c' => ['d' => ['e' => 'changed']]]]],
        ]);

        expect($diff)->toBe([
            'nested.a.b.c.d.e' => [
                'dto' => 'value',
                'data' => 'changed',
            ],
        ]);
    });
});

describe('Invalid Input Handling', function(): void {
    it('throws exception for invalid JSON', function(): void {
        $dto = new class ('John') extends SimpleDto {
            public function __construct(
                public readonly string $name,
            ) {
            }
        };

        $user = $dto::fromArray(['name' => 'John']);

        expect(fn(): array => $user->diff('invalid json'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('throws exception for invalid XML', function(): void {
        $dto = new class ('John') extends SimpleDto {
            public function __construct(
                public readonly string $name,
            ) {
            }
        };

        $user = $dto::fromArray(['name' => 'John']);

        expect(fn(): array => $user->diff('<invalid xml'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('throws exception for unsupported type', function(): void {
        $dto = new class ('John') extends SimpleDto {
            public function __construct(
                public readonly string $name,
            ) {
            }
        };

        $user = $dto::fromArray(['name' => 'John']);

        expect(fn(): array => $user->diff(123))
            ->toThrow(InvalidArgumentException::class);
    });
});
