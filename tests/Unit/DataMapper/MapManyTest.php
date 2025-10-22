<?php

declare(strict_types=1);

use event4u\DataHelpers\DataAccessor;
use event4u\DataHelpers\DataMapper;

/**
 * Tests for mapMany() and mapManyReverse() methods in FluentDataMapper.
 *
 * @internal
 */
describe('DataMapper mapMany() and mapManyReverse()', function(): void {
    describe('mapMany() - Bulk mapping', function(): void {
        it('maps multiple source-target pairs using the same template', function(): void {
            $source1 = ['name' => 'Alice', 'email' => 'alice@example.com'];
            $source2 = ['name' => 'Bob', 'email' => 'bob@example.com'];
            $source3 = ['name' => 'Charlie', 'email' => 'charlie@example.com'];

            $results = DataMapper::source([])
                ->template([
                    'fullname' => '{{ name }}',
                    'contact.email' => '{{ email }}',
                ])
                ->mapMany([
                    ['source' => $source1, 'target' => []],
                    ['source' => $source2, 'target' => []],
                    ['source' => $source3, 'target' => []],
                ]);

            expect($results)->toHaveCount(3);

            expect($results[0]->toArray())->toBe([
                'fullname' => 'Alice',
                'contact' => ['email' => 'alice@example.com'],
            ]);

            expect($results[1]->toArray())->toBe([
                'fullname' => 'Bob',
                'contact' => ['email' => 'bob@example.com'],
            ]);

            expect($results[2]->toArray())->toBe([
                'fullname' => 'Charlie',
                'contact' => ['email' => 'charlie@example.com'],
            ]);
        });

        it('respects pipeline filters for all mappings', function(): void {
            $source1 = ['name' => '  Alice  '];
            $source2 = ['name' => '  Bob  '];

            $results = DataMapper::source([])
                ->template(['name' => '{{ name }}'])
                ->trimValues(true)
                ->mapMany([
                    ['source' => $source1, 'target' => []],
                    ['source' => $source2, 'target' => []],
                ]);

            expect($results)->toHaveCount(2);
            expect($results[0]->toArray())->toBe(['name' => 'Alice']);
            expect($results[1]->toArray())->toBe(['name' => 'Bob']);
        });

        it('respects skipNull setting for all mappings', function(): void {
            $source1 = ['name' => 'Alice', 'email' => null];
            $source2 = ['name' => 'Bob', 'email' => 'bob@example.com'];

            $results = DataMapper::source([])
                ->template([
                    'name' => '{{ name }}',
                    'email' => '{{ email }}',
                ])
                ->mapMany([
                    ['source' => $source1, 'target' => []],
                    ['source' => $source2, 'target' => []],
                ]);

            expect($results)->toHaveCount(2);
            expect($results[0]->toArray())->toBe(['name' => 'Alice']);
            expect($results[1]->toArray())->toBe(['name' => 'Bob', 'email' => 'bob@example.com']);
        });

        it('maps to different target types', function(): void {
            $source1 = ['name' => 'Alice'];
            $source2 = ['name' => 'Bob'];

            $target1 = new class {
                public ?string $name = null;
            };

            $target2 = [];

            $results = DataMapper::source([])
                ->template(['name' => '{{ name }}'])
                ->mapMany([
                    ['source' => $source1, 'target' => $target1],
                    ['source' => $source2, 'target' => $target2],
                ]);

            expect($results)->toHaveCount(2);
            $target0 = $results[0]->getTarget();
            assert(is_object($target0));
            $acc0 = new DataAccessor($target0);
            expect($acc0->get('name'))->toBe('Alice');
            expect($results[1]->toArray())->toBe(['name' => 'Bob']);
        });

        it('returns results indexed by 0, 1, 2, ...', function(): void {
            $results = DataMapper::source([])
                ->template(['name' => '{{ name }}'])
                ->mapMany([
                    ['source' => ['name' => 'Alice'], 'target' => []],
                    ['source' => ['name' => 'Bob'], 'target' => []],
                    ['source' => ['name' => 'Charlie'], 'target' => []],
                ]);

            expect($results)->toHaveKey(0);
            expect($results)->toHaveKey(1);
            expect($results)->toHaveKey(2);
            expect($results)->not->toHaveKey(3);
        });
    });

    describe('reverseManyMap() - Bulk reverse mapping', function(): void {
        it('maps multiple source-target pairs in reverse direction', function(): void {
            $source1 = ['fullname' => 'Alice A.', 'contact' => ['email' => 'alice@example.com']];
            $source2 = ['fullname' => 'Bob B.', 'contact' => ['email' => 'bob@example.com']];

            $results = DataMapper::source([])
                ->template([
                    'fullname' => '{{ name }}',
                    'contact.email' => '{{ email }}',
                ])
                ->reverseManyMap([
                    ['source' => $source1, 'target' => []],
                    ['source' => $source2, 'target' => []],
                ]);

            expect($results)->toHaveCount(2);

            expect($results[0]->toArray())->toBe([
                'name' => 'Alice A.',
                'email' => 'alice@example.com',
            ]);

            expect($results[1]->toArray())->toBe([
                'name' => 'Bob B.',
                'email' => 'bob@example.com',
            ]);
        });

        it('respects pipeline filters for all reverse mappings', function(): void {
            $source1 = ['name' => '  Alice  '];
            $source2 = ['name' => '  Bob  '];

            $results = DataMapper::source([])
                ->template(['name' => '{{ name }}'])
                ->trimValues(true)
                ->reverseManyMap([
                    ['source' => $source1, 'target' => []],
                    ['source' => $source2, 'target' => []],
                ]);

            expect($results)->toHaveCount(2);
            expect($results[0]->toArray())->toBe(['name' => 'Alice']);
            expect($results[1]->toArray())->toBe(['name' => 'Bob']);
        });

        it('returns results indexed by 0, 1, 2, ...', function(): void {
            $results = DataMapper::source([])
                ->template(['name' => '{{ name }}'])
                ->reverseManyMap([
                    ['source' => ['name' => 'Alice'], 'target' => []],
                    ['source' => ['name' => 'Bob'], 'target' => []],
                ]);

            expect($results)->toHaveKey(0);
            expect($results)->toHaveKey(1);
            expect($results)->not->toHaveKey(2);
        });
    });
});
