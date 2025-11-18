<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMutator;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

// Test DTOs for Deep Nesting
#[AutoCast]
class DeepNestedDto extends SimpleDto
{
    public function __construct(
        public readonly string $value,
        public readonly ?DeepNestedDto $nested = null,
    ) {}
}

describe('Deep Nesting Tests', function(): void {
    describe('DTO Deep Nesting', function(): void {
        it('handles 10-level deep nesting', function(): void {
            $data = ['value' => 'Level 1', 'nested' => null];
            $current = &$data;

            for ($i = 2; 10 >= $i; $i++) {
                $current['nested'] = ['value' => 'Level ' . $i, 'nested' => null];
                $current = &$current['nested'];
            }

            $dto = DeepNestedDto::fromArray($data);

            expect($dto->value)->toBe('Level 1');

            $node = $dto;
            for ($i = 2; 10 >= $i; $i++) {
                /** @phpstan-ignore-next-line property.nonObject */
                expect($node->nested)->toBeInstanceOf(DeepNestedDto::class);
                /** @phpstan-ignore-next-line property.nonObject */
                expect($node->nested?->value)->toBe('Level ' . $i);
                /** @phpstan-ignore-next-line property.nonObject */
                $node = $node->nested;
            }

            /** @phpstan-ignore-next-line property.nonObject */
            expect($node->nested)->toBeNull();
        });

        it('handles 20-level deep nesting', function(): void {
            $data = ['value' => 'Level 1', 'nested' => null];
            $current = &$data;

            for ($i = 2; 20 >= $i; $i++) {
                $current['nested'] = ['value' => 'Level ' . $i, 'nested' => null];
                $current = &$current['nested'];
            }

            $dto = DeepNestedDto::fromArray($data);

            expect($dto->value)->toBe('Level 1');

            // Verify we can traverse the entire chain
            $node = $dto;
            $count = 1;
            while (null !== $node->nested) {
                $node = $node->nested;
                $count++;
            }

            expect($count)->toBe(20);
        });

        it('handles 50-level deep nesting', function(): void {
            $data = ['value' => 'Level 1', 'nested' => null];
            $current = &$data;

            for ($i = 2; 50 >= $i; $i++) {
                $current['nested'] = ['value' => 'Level ' . $i, 'nested' => null];
                $current = &$current['nested'];
            }

            $dto = DeepNestedDto::fromArray($data);

            expect($dto->value)->toBe('Level 1');

            // Verify we can traverse the entire chain
            $node = $dto;
            $count = 1;
            while (null !== $node->nested) {
                $node = $node->nested;
                $count++;
            }

            expect($count)->toBe(50);
        });

        it('handles 100-level deep nesting', function(): void {
            $data = ['value' => 'Level 1', 'nested' => null];
            $current = &$data;

            for ($i = 2; 100 >= $i; $i++) {
                $current['nested'] = ['value' => 'Level ' . $i, 'nested' => null];
                $current = &$current['nested'];
            }

            $dto = DeepNestedDto::fromArray($data);

            expect($dto->value)->toBe('Level 1');

            // Verify we can traverse the entire chain
            $node = $dto;
            $count = 1;
            while (null !== $node->nested) {
                $node = $node->nested;
                $count++;
            }

            expect($count)->toBe(100);
        });
    });

    describe('DataAccessor Deep Nesting', function(): void {
        it('handles 20-level deep path access', function(): void {
            $data = ['level1' => []];
            $current = &$data['level1'];

            for ($i = 2; 20 >= $i; $i++) {
                $current['level' . $i] = [];
                $current = &$current['level' . $i];
            }
            $current['value'] = 'Deep Value';

            $path = 'level1.' . implode('.', array_map(fn($i) => 'level' . $i, range(2, 20))) . '.value';
            $accessor = new DataAccessor($data);
            $result = $accessor->get($path);

            expect($result)->toBe('Deep Value');
        });

        it('handles 30-level deep path access', function(): void {
            $data = ['level1' => []];
            $current = &$data['level1'];

            for ($i = 2; 30 >= $i; $i++) {
                $current['level' . $i] = [];
                $current = &$current['level' . $i];
            }
            $current['value'] = 'Very Deep Value';

            $path = 'level1.' . implode('.', array_map(fn($i) => 'level' . $i, range(2, 30))) . '.value';
            $accessor = new DataAccessor($data);
            $result = $accessor->get($path);

            expect($result)->toBe('Very Deep Value');
        });

        it('handles deep nesting with wildcards', function(): void {
            $data = ['level1' => []];
            $current = &$data['level1'];

            for ($i = 2; 10 >= $i; $i++) {
                $current['items'] = [
                    ['level' . $i => []],
                    ['level' . $i => []],
                ];
                $current = &$current['items'][0]['level' . $i];
            }
            $current['value'] = 'Wildcard Deep Value';

            $path = 'level1.items.*.level2.items.*.level3.value';
            $accessor = new DataAccessor($data);
            $result = $accessor->get($path);

            expect($result)->toBeArray();
        });
    });

    describe('DataMutator Deep Nesting', function(): void {
        it('handles 20-level deep path mutation', function(): void {
            $data = ['level1' => []];
            $current = &$data['level1'];

            for ($i = 2; 20 >= $i; $i++) {
                $current['level' . $i] = [];
                $current = &$current['level' . $i];
            }

            $path = 'level1.' . implode('.', array_map(fn($i) => 'level' . $i, range(2, 20))) . '.value';
            $mutator = DataMutator::make($data);
            $mutator->set($path, 'New Deep Value');

            $accessor = new DataAccessor($data);
            $result = $accessor->get($path);
            expect($result)->toBe('New Deep Value');
        });

        it('handles deep nesting with wildcard mutation', function(): void {
            $data = [
                'level1' => [
                    'items' => [
                        ['level2' => ['items' => [['value' => 1], ['value' => 2]]]],
                        ['level2' => ['items' => [['value' => 3], ['value' => 4]]]],
                    ],
                ],
            ];

            $mutator = DataMutator::make($data);
            $mutator->set('level1.items.*.level2.items.*.value', 100);

            $accessor = new DataAccessor($data);
            $results = $accessor->get('level1.items.*.level2.items.*.value');
            expect($results)->toBeArray();

            /** @phpstan-ignore-next-line foreach.nonIterable */
            foreach ($results as $result) {
                expect($result)->toBe(100);
            }
        });
    });

    describe('Serialization with Deep Nesting', function(): void {
        it('serializes 20-level deep DTO to JSON', function(): void {
            $data = ['value' => 'Level 1', 'nested' => null];
            $current = &$data;

            for ($i = 2; 20 >= $i; $i++) {
                $current['nested'] = ['value' => 'Level ' . $i, 'nested' => null];
                $current = &$current['nested'];
            }

            $dto = DeepNestedDto::fromArray($data);
            $json = json_encode($dto);

            expect($json)->toBeString()
                ->and($json)->toContain('Level 1')
                ->and($json)->toContain('Level 20');

            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray()
                ->and($decoded)->toHaveKey('value')
                ->and($decoded['value'])->toBe('Level 1');
        });

        it('can restore 20-level deep DTO from JSON', function(): void {
            $data = ['value' => 'Level 1', 'nested' => null];
            $current = &$data;

            for ($i = 2; 20 >= $i; $i++) {
                $current['nested'] = ['value' => 'Level ' . $i, 'nested' => null];
                $current = &$current['nested'];
            }

            $dto = DeepNestedDto::fromArray($data);
            $json = json_encode($dto);
            $decoded = json_decode($json, true);
            $restored = DeepNestedDto::fromArray($decoded);

            expect($restored->value)->toBe('Level 1');

            // Verify we can traverse the restored DTO
            $node = $restored;
            $count = 1;
            while (null !== $node->nested) {
                $node = $node->nested;
                $count++;
            }

            expect($count)->toBe(20);
        });
    });

    describe('Memory Efficiency with Deep Nesting', function(): void {
        it('uses reasonable memory for 50-level deep nesting', function(): void {
            $memoryBefore = memory_get_usage();

            $data = ['value' => 'Level 1', 'nested' => null];
            $current = &$data;

            for ($i = 2; 50 >= $i; $i++) {
                $current['nested'] = ['value' => 'Level ' . $i, 'nested' => null];
                $current = &$current['nested'];
            }

            $dto = DeepNestedDto::fromArray($data);

            $memoryAfter = memory_get_usage();
            $memoryUsed = $memoryAfter - $memoryBefore;

            // Should use less than 200KB for 50 levels
            expect($memoryUsed)->toBeLessThan(200 * 1024);
        });

        it('uses reasonable memory for 100-level deep nesting', function(): void {
            $memoryBefore = memory_get_usage();

            $data = ['value' => 'Level 1', 'nested' => null];
            $current = &$data;

            for ($i = 2; 100 >= $i; $i++) {
                $current['nested'] = ['value' => 'Level ' . $i, 'nested' => null];
                $current = &$current['nested'];
            }

            $dto = DeepNestedDto::fromArray($data);

            $memoryAfter = memory_get_usage();
            $memoryUsed = $memoryAfter - $memoryBefore;

            // Should use less than 400KB for 100 levels
            expect($memoryUsed)->toBeLessThan(400 * 1024);
        });
    });
});

