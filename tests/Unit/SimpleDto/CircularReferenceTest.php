<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

// Test DTOs for Circular Reference Testing
#[AutoCast]
class CircularNodeDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly ?CircularNodeDto $next = null,
    ) {}
}

#[AutoCast]
class CircularParentDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        /** @var array<int, array<string, mixed>> */
        public readonly array $children = [],
    ) {}
}

#[AutoCast]
class CircularChildDto extends SimpleDto
{
    public function __construct(
        public readonly string $name,
        public readonly ?CircularParentDto $parent = null,
    ) {}
}

describe('Circular Reference Handling', function(): void {
    describe('Simple Circular References', function(): void {
        it('handles null circular references', function(): void {
            $dto = CircularNodeDto::fromArray([
                'name' => 'Node 1',
                'next' => null,
            ]);

            expect($dto->name)->toBe('Node 1')
                ->and($dto->next)->toBeNull();
        });

        it('handles single-level circular reference', function(): void {
            $dto = CircularNodeDto::fromArray([
                'name' => 'Node 1',
                'next' => [
                    'name' => 'Node 2',
                    'next' => null,
                ],
            ]);

            expect($dto->name)->toBe('Node 1')
                ->and($dto->next)->toBeInstanceOf(CircularNodeDto::class)
                ->and($dto->next?->name)->toBe('Node 2')
                ->and($dto->next?->next)->toBeNull();
        });

        it('handles multi-level circular reference chain', function(): void {
            $dto = CircularNodeDto::fromArray([
                'name' => 'Node 1',
                'next' => [
                    'name' => 'Node 2',
                    'next' => [
                        'name' => 'Node 3',
                        'next' => null,
                    ],
                ],
            ]);

            expect($dto->name)->toBe('Node 1')
                ->and($dto->next?->name)->toBe('Node 2')
                ->and($dto->next?->next?->name)->toBe('Node 3')
                ->and($dto->next?->next?->next)->toBeNull();
        });
    });

    describe('Parent-Child Circular References', function(): void {
        it('handles parent without children', function(): void {
            $dto = CircularParentDto::fromArray([
                'name' => 'Parent',
                'children' => [],
            ]);

            expect($dto->name)->toBe('Parent')
                ->and($dto->children)->toBeArray()
                ->and($dto->children)->toHaveCount(0);
        });

        it('handles children without parent reference', function(): void {
            $dto = CircularParentDto::fromArray([
                'name' => 'Parent',
                'children' => [
                    ['name' => 'Child 1', 'parent' => null],
                    ['name' => 'Child 2', 'parent' => null],
                ],
            ]);

            expect($dto->name)->toBe('Parent')
                ->and($dto->children)->toHaveCount(2)
                ->and($dto->children[0])->toBeArray()
                ->and($dto->children[0]['name'])->toBe('Child 1');
        });

        it('handles nested parent-child structures', function(): void {
            $dto = CircularChildDto::fromArray([
                'name' => 'Child 1',
                'parent' => [
                    'name' => 'Parent',
                    'children' => [],
                ],
            ]);

            expect($dto->name)->toBe('Child 1')
                ->and($dto->parent)->toBeInstanceOf(CircularParentDto::class)
                ->and($dto->parent?->name)->toBe('Parent')
                ->and($dto->parent?->children)->toBeArray();
        });
    });

    describe('Serialization with Circular References', function(): void {
        it('serializes simple circular reference to JSON', function(): void {
            $dto = CircularNodeDto::fromArray([
                'name' => 'Node 1',
                'next' => [
                    'name' => 'Node 2',
                    'next' => null,
                ],
            ]);

            $json = json_encode($dto);

            expect($json)->toBeString()
                ->and($json)->toContain('Node 1')
                ->and($json)->toContain('Node 2');

            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray()
                ->and($decoded)->toHaveKey('name')
                ->and($decoded['name'])->toBe('Node 1');
        });

        it('serializes parent-child to JSON', function(): void {
            $dto = CircularParentDto::fromArray([
                'name' => 'Parent',
                'children' => [
                    ['name' => 'Child 1', 'parent' => null],
                ],
            ]);

            $json = json_encode($dto);

            expect($json)->toBeString()
                ->and($json)->toContain('Parent')
                ->and($json)->toContain('Child 1');

            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray()
                ->and($decoded)->toHaveKey('name')
                ->and($decoded['name'])->toBe('Parent');
        });

        it('can be converted back from JSON', function(): void {
            $dto = CircularNodeDto::fromArray([
                'name' => 'Node 1',
                'next' => [
                    'name' => 'Node 2',
                    'next' => null,
                ],
            ]);

            $json = json_encode($dto);
            $decoded = json_decode($json, true);
            $restored = CircularNodeDto::fromArray($decoded);

            expect($restored->name)->toBe('Node 1')
                ->and($restored->next)->toBeInstanceOf(CircularNodeDto::class)
                ->and($restored->next?->name)->toBe('Node 2');
        });
    });

    describe('Deep Circular Reference Chains', function(): void {
        it('handles 10-level deep circular reference', function(): void {
            $data = ['name' => 'Node 1', 'next' => null];
            $current = &$data;

            for ($i = 2; 10 >= $i; $i++) {
                $current['next'] = ['name' => 'Node ' . $i, 'next' => null];
                $current = &$current['next'];
            }

            $dto = CircularNodeDto::fromArray($data);

            expect($dto->name)->toBe('Node 1');

            $node = $dto;
            for ($i = 2; 10 >= $i; $i++) {
                /** @phpstan-ignore-next-line property.nonObject */
                expect($node->next)->toBeInstanceOf(CircularNodeDto::class);
                /** @phpstan-ignore-next-line property.nonObject */
                expect($node->next?->name)->toBe('Node ' . $i);
                /** @phpstan-ignore-next-line property.nonObject */
                $node = $node->next;
            }

            /** @phpstan-ignore-next-line property.nonObject */
            expect($node->next)->toBeNull();
        });

        it('handles 20-level deep circular reference', function(): void {
            $data = ['name' => 'Node 1', 'next' => null];
            $current = &$data;

            for ($i = 2; 20 >= $i; $i++) {
                $current['next'] = ['name' => 'Node ' . $i, 'next' => null];
                $current = &$current['next'];
            }

            $dto = CircularNodeDto::fromArray($data);

            expect($dto->name)->toBe('Node 1');

            $node = $dto;
            for ($i = 2; 20 >= $i; $i++) {
                /** @phpstan-ignore-next-line property.nonObject */
                expect($node->next)->toBeInstanceOf(CircularNodeDto::class);
                /** @phpstan-ignore-next-line property.nonObject */
                $node = $node->next;
            }

            /** @phpstan-ignore-next-line property.nonObject */
            expect($node->next)->toBeNull();
        });

        it('handles 50-level deep circular reference', function(): void {
            $data = ['name' => 'Node 1', 'next' => null];
            $current = &$data;

            for ($i = 2; 50 >= $i; $i++) {
                $current['next'] = ['name' => 'Node ' . $i, 'next' => null];
                $current = &$current['next'];
            }

            $dto = CircularNodeDto::fromArray($data);

            expect($dto->name)->toBe('Node 1');

            // Verify we can traverse the entire chain
            $node = $dto;
            $count = 1;
            while ($node->next instanceof \CircularNodeDto) {
                $node = $node->next;
                $count++;
            }

            expect($count)->toBe(50);
        });
    });

    describe('Memory Efficiency with Circular References', function(): void {
        it('uses reasonable memory for deep circular references', function(): void {
            $memoryBefore = memory_get_usage();

            $data = ['name' => 'Node 1', 'next' => null];
            $current = &$data;

            for ($i = 2; 100 >= $i; $i++) {
                $current['next'] = ['name' => 'Node ' . $i, 'next' => null];
                $current = &$current['next'];
            }

            $dto = CircularNodeDto::fromArray($data);

            $memoryAfter = memory_get_usage();
            $memoryUsed = $memoryAfter - $memoryBefore;

            // Should use less than 500KB for 100 nodes
            expect($memoryUsed)->toBeLessThan(500 * 1024);
        });
    });
});
