<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;

describe('SimpleDTO Sorting', function(): void {
    describe('Default Behavior', function(): void {
        it('does not sort by default', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $zebra = 'z',
                    public readonly string $alpha = 'a',
                    public readonly string $beta = 'b',
                ) {}
            };

            $data = ['zebra' => 'z', 'alpha' => 'a', 'beta' => 'b'];
            $result = $dto::fromArray($data);
            $array = $result->toArray();

            // Keys should be in original order (property definition order)
            expect(array_keys($array))->toBe(['zebra', 'alpha', 'beta']);
        });
    });

    describe('Basic Sorting', function(): void {
        it('sorts keys ascending', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $zebra = 'z',
                    public readonly string $alpha = 'a',
                    public readonly string $beta = 'b',
                ) {}
            };

            $data = ['zebra' => 'z', 'alpha' => 'a', 'beta' => 'b'];
            $result = $dto::fromArray($data);
            $sorted = $result->sorted();
            $array = $sorted->toArray();

            expect(array_keys($array))->toBe(['alpha', 'beta', 'zebra']);
        });

        it('sorts keys descending', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $zebra = 'z',
                    public readonly string $alpha = 'a',
                    public readonly string $beta = 'b',
                ) {}
            };

            $data = ['zebra' => 'z', 'alpha' => 'a', 'beta' => 'b'];
            $result = $dto::fromArray($data);
            $sorted = $result->sorted('desc');
            $array = $sorted->toArray();

            expect(array_keys($array))->toBe(['zebra', 'beta', 'alpha']);
        });

        it('can disable sorting after enabling', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $zebra = 'z',
                    public readonly string $alpha = 'a',
                    public readonly string $beta = 'b',
                ) {}
            };

            $data = ['zebra' => 'z', 'alpha' => 'a', 'beta' => 'b'];
            $result = $dto::fromArray($data);
            $sorted = $result->sorted()->unsorted();
            $array = $sorted->toArray();

            expect(array_keys($array))->toBe(['zebra', 'alpha', 'beta']);
        });
    });

    describe('JSON Serialization', function(): void {
        it('applies sorting to jsonSerialize', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $zebra = 'z',
                    public readonly string $alpha = 'a',
                    public readonly string $beta = 'b',
                ) {}
            };

            $data = ['zebra' => 'z', 'alpha' => 'a', 'beta' => 'b'];
            $result = $dto::fromArray($data);
            $sorted = $result->sorted();
            $json = json_encode($sorted);

            expect($json)->toBe('{"alpha":"a","beta":"b","zebra":"z"}');
        });
    });

    describe('Nested Sorting', function(): void {
        it('does not sort nested arrays by default', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'test',
                    public readonly array $nested = [],
                ) {}
            };

            $data = [
                'name' => 'test',
                'nested' => [
                    'zebra' => 'z',
                    'alpha' => 'a',
                    'beta' => 'b',
                ],
            ];
            $result = $dto::fromArray($data);
            $sorted = $result->sorted();
            $array = $sorted->toArray();

            // Top level sorted
            expect(array_keys($array))->toBe(['name', 'nested']);
            // Nested not sorted
            expect(array_keys($array['nested']))->toBe(['zebra', 'alpha', 'beta']);
        });

        it('sorts nested arrays when enabled', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $name = 'test',
                    public readonly array $nested = [],
                ) {}
            };

            $data = [
                'name' => 'test',
                'nested' => [
                    'zebra' => 'z',
                    'alpha' => 'a',
                    'beta' => 'b',
                ],
            ];
            $result = $dto::fromArray($data);
            $sorted = $result->sorted()->withNestedSort();
            $array = $sorted->toArray();

            // Top level sorted
            expect(array_keys($array))->toBe(['name', 'nested']);
            // Nested also sorted
            expect(array_keys($array['nested']))->toBe(['alpha', 'beta', 'zebra']);
        });

        it('sorts deeply nested arrays', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly array $data = [],
                ) {}
            };

            $data = [
                'data' => [
                    'zebra' => [
                        'nested_z' => 'z',
                        'nested_a' => 'a',
                    ],
                    'alpha' => [
                        'nested_z' => 'z',
                        'nested_a' => 'a',
                    ],
                ],
            ];
            $result = $dto::fromArray($data);
            $sorted = $result->sorted()->withNestedSort();
            $array = $sorted->toArray();

            // Top level sorted
            expect(array_keys($array['data']))->toBe(['alpha', 'zebra']);
            // Nested also sorted
            expect(array_keys($array['data']['alpha']))->toBe(['nested_a', 'nested_z']);
            expect(array_keys($array['data']['zebra']))->toBe(['nested_a', 'nested_z']);
        });
    });

    describe('Custom Sort Callback', function(): void {
        it('sorts using custom callback', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $zebra = 'z',
                    public readonly string $alpha = 'a',
                    public readonly string $beta = 'b',
                ) {}
            };

            $data = ['zebra' => 'z', 'alpha' => 'a', 'beta' => 'b'];
            $result = $dto::fromArray($data);

            // Reverse alphabetical
            $sorted = $result->sortedBy(fn($a, $b): int => strcmp($b, $a));
            $array = $sorted->toArray();

            expect(array_keys($array))->toBe(['zebra', 'beta', 'alpha']);
        });

        it('sorts by key length', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $a = '1',
                    public readonly string $abc = '3',
                    public readonly string $ab = '2',
                ) {}
            };

            $data = ['a' => '1', 'abc' => '3', 'ab' => '2'];
            $result = $dto::fromArray($data);

            // Sort by key length
            $sorted = $result->sortedBy(fn($a, $b): int => strlen($a) <=> strlen($b));
            $array = $sorted->toArray();

            expect(array_keys($array))->toBe(['a', 'ab', 'abc']);
        });
    });

    describe('Immutability', function(): void {
        it('does not modify original DTO', function(): void {
            $dto = new class extends SimpleDTO {
                public function __construct(
                    public readonly string $zebra = 'z',
                    public readonly string $alpha = 'a',
                ) {}
            };

            $data = ['zebra' => 'z', 'alpha' => 'a'];
            $result = $dto::fromArray($data);

            $sorted = $result->sorted();

            // Original should not be sorted
            $originalArray = $result->toArray();
            expect(array_keys($originalArray))->toBe(['zebra', 'alpha']);

            // Sorted should be sorted
            $sortedArray = $sorted->toArray();
            expect(array_keys($sortedArray))->toBe(['alpha', 'zebra']);
        });
    });
});

