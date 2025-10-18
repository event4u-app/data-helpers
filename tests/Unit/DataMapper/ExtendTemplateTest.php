<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;

/**
 * Tests for extendTemplate(), addPipelineFilter(), and copy() methods.
 *
 * @internal
 */
describe('DataMapper - extendTemplate() and addPipelineFilter()', function(): void {
    describe('extendTemplate()', function(): void {
        it('extends template with new mappings', function(): void {
            $source = [
                'user' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                    'phone' => '123-456-7890',
                ],
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'name' => '{{ user.name }}',
                ])
                ->extendTemplate([
                    'email' => '{{ user.email }}',
                ]);

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ]);
        });

        it('overrides existing mappings when extending', function(): void {
            $source = [
                'user' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'name' => '{{ user.name }}',
                    'email' => '{{ user.email }}',
                ])
                ->extendTemplate([
                    'email' => 'hardcoded@example.com',
                ]);

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'name' => 'Alice',
                'email' => 'hardcoded@example.com',
            ]);
        });

        it('can be chained multiple times', function(): void {
            $source = [
                'user' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                    'phone' => '123-456-7890',
                ],
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'name' => '{{ user.name }}',
                ])
                ->extendTemplate([
                    'email' => '{{ user.email }}',
                ])
                ->extendTemplate([
                    'phone' => '{{ user.phone }}',
                ]);

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'phone' => '123-456-7890',
            ]);
        });

        it('works with nested templates', function(): void {
            $source = [
                'user' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                    'address' => [
                        'street' => 'Main St',
                        'city' => 'New York',
                    ],
                ],
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'profile' => [
                        'name' => '{{ user.name }}',
                    ],
                ])
                ->extendTemplate([
                    'profile' => [
                        'email' => '{{ user.email }}',
                    ],
                    'location' => [
                        'city' => '{{ user.address.city }}',
                    ],
                ]);

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'profile' => [
                    'email' => 'alice@example.com',
                ],
                'location' => [
                    'city' => 'New York',
                ],
            ]);
        });

        it('works with empty initial template', function(): void {
            $source = [
                'user' => [
                    'name' => 'Alice',
                ],
            ];

            $mapper = DataMapper::source($source)
                ->extendTemplate([
                    'name' => '{{ user.name }}',
                ]);

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'name' => 'Alice',
            ]);
        });
    });

    describe('addPipelineFilter()', function(): void {
        it('adds a single filter to existing pipeline', function(): void {
            $source = [
                'name' => '  alice  ',
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'name' => '{{ name }}',
                ])
                ->pipeline([new TrimStrings()])
                ->addPipelineFilter(new UppercaseStrings());

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'name' => 'ALICE',
            ]);
        });

        it('can be chained multiple times', function(): void {
            $source = [
                'name' => '  alice  ',
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'name' => '{{ name }}',
                ])
                ->pipeline([new TrimStrings()])
                ->addPipelineFilter(new UppercaseStrings())
                ->addPipelineFilter(new LowercaseStrings());

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'name' => 'alice',
            ]);
        });

        it('works with empty initial pipeline', function(): void {
            $source = [
                'name' => '  alice  ',
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'name' => '{{ name }}',
                ])
                ->addPipelineFilter(new TrimStrings());

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'name' => 'alice',
            ]);
        });

        it('maintains filter order', function(): void {
            $source = [
                'name' => '  alice  ',
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'name' => '{{ name }}',
                ])
                ->addPipelineFilter(new UppercaseStrings())
                ->addPipelineFilter(new TrimStrings());

            $result = $mapper->map();

            // UppercaseStrings first: '  alice  ' -> '  ALICE  '
            // TrimStrings second: '  ALICE  ' -> 'ALICE'
            expect($result->toArray())->toBe([
                'name' => 'ALICE',
            ]);
        });
    });

    describe('copy() with extended API', function(): void {
        it('copy() creates independent instance that can be extended', function(): void {
            $source = [
                'user' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
            ];

            $original = DataMapper::source($source)
                ->template([
                    'name' => '{{ user.name }}',
                ]);

            $copy = $original->copy()
                ->extendTemplate([
                    'email' => '{{ user.email }}',
                ]);

            $originalResult = $original->map();
            $copyResult = $copy->map();

            expect($originalResult->toArray())->toBe([
                'name' => 'Alice',
            ]);

            expect($copyResult->toArray())->toBe([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ]);
        });

        it('copy() with addPipelineFilter() creates independent filter chain', function(): void {
            $source = [
                'name' => '  alice  ',
            ];

            $original = DataMapper::source($source)
                ->template([
                    'name' => '{{ name }}',
                ])
                ->pipeline([new TrimStrings()]);

            $copy = $original->copy()
                ->addPipelineFilter(new UppercaseStrings());

            $originalResult = $original->map();
            $copyResult = $copy->map();

            expect($originalResult->toArray())->toBe([
                'name' => 'alice',
            ]);

            expect($copyResult->toArray())->toBe([
                'name' => 'ALICE',
            ]);
        });

        it('copy() can override template completely after copying', function(): void {
            $source = [
                'user' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                ],
            ];

            $original = DataMapper::source($source)
                ->template([
                    'name' => '{{ user.name }}',
                ]);

            $copy = $original->copy()
                ->template([
                    'email' => '{{ user.email }}',
                ]);

            $originalResult = $original->map();
            $copyResult = $copy->map();

            expect($originalResult->toArray())->toBe([
                'name' => 'Alice',
            ]);

            expect($copyResult->toArray())->toBe([
                'email' => 'alice@example.com',
            ]);
        });

        it('copy() with both extendTemplate() and addPipelineFilter()', function(): void {
            $source = [
                'user' => [
                    'name' => '  alice  ',
                    'email' => '  ALICE@EXAMPLE.COM  ',
                ],
            ];

            $original = DataMapper::source($source)
                ->template([
                    'name' => '{{ user.name }}',
                ])
                ->pipeline([new TrimStrings()]);

            $copy = $original->copy()
                ->extendTemplate([
                    'email' => '{{ user.email }}',
                ])
                ->addPipelineFilter(new LowercaseStrings());

            $originalResult = $original->map();
            $copyResult = $copy->map();

            expect($originalResult->toArray())->toBe([
                'name' => 'alice',
            ]);

            expect($copyResult->toArray())->toBe([
                'name' => 'alice',
                'email' => 'alice@example.com',
            ]);
        });

        it('copy() with setValueFilters() creates independent property filters', function(): void {
            $source = [
                'name' => '  alice  ',
                'email' => '  bob@example.com  ',
            ];

            $original = DataMapper::source($source)
                ->template([
                    'name' => '{{ name }}',
                    'email' => '{{ email }}',
                ])
                ->trimValues(false)
                ->setValueFilters('name', new TrimStrings());

            $copy = $original->copy()
                ->setValueFilters('email', new TrimStrings());

            $originalResult = $original->map();
            $copyResult = $copy->map();

            expect($originalResult->toArray())->toBe([
                'name' => 'alice',
                'email' => '  bob@example.com  ',
            ]);

            expect($copyResult->toArray())->toBe([
                'name' => 'alice',
                'email' => 'bob@example.com',
            ]);
        });
    });

    describe('Edge Cases', function(): void {
        it('extendTemplate() with wildcard mappings', function(): void {
            $source = [
                'users' => [
                    ['name' => 'Alice'],
                    ['name' => 'Bob'],
                ],
                'total' => 2,
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'people' => [
                        '*' => [
                            'name' => '{{ users.*.name }}',
                        ],
                    ],
                ])
                ->extendTemplate([
                    'count' => '{{ total }}',
                ]);

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'people' => [
                    ['name' => 'Alice'],
                    ['name' => 'Bob'],
                ],
                'count' => 2,
            ]);
        });

        it('addPipelineFilter() with multiple data types', function(): void {
            $source = [
                'name' => '  alice  ',
                'age' => 30,
                'active' => true,
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'name' => '{{ name }}',
                    'age' => '{{ age }}',
                    'active' => '{{ active }}',
                ])
                ->addPipelineFilter(new TrimStrings());

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'name' => 'alice',
                'age' => 30,
                'active' => true,
            ]);
        });

        it('extendTemplate() with null values', function(): void {
            $source = [
                'name' => 'Alice',
                'email' => null,
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'name' => '{{ name }}',
                ])
                ->extendTemplate([
                    'email' => '{{ email }}',
                ])
                ->skipNull(false);

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'name' => 'Alice',
                'email' => null,
            ]);
        });

        it('extendTemplate() with skipNull=true skips null values', function(): void {
            $source = [
                'name' => 'Alice',
                'email' => null,
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'name' => '{{ name }}',
                ])
                ->extendTemplate([
                    'email' => '{{ email }}',
                ])
                ->skipNull(true);

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'name' => 'Alice',
            ]);
        });

        it('copy() preserves all options (skipNull, reindexWildcard, trimValues)', function(): void {
            $source = [
                'name' => '  alice  ',
            ];

            $original = DataMapper::source($source)
                ->template([
                    'name' => '{{ name }}',
                ])
                ->skipNull(false)
                ->reindexWildcard(true)
                ->trimValues(false);

            $copy = $original->copy();

            $result = $copy->map();

            expect($result->toArray())->toBe([
                'name' => '  alice  ',
            ]);
        });

        it('extendTemplate() with empty array does nothing', function(): void {
            $source = [
                'name' => 'Alice',
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'name' => '{{ name }}',
                ])
                ->extendTemplate([]);

            $result = $mapper->map();

            expect($result->toArray())->toBe([
                'name' => 'Alice',
            ]);
        });

        it('multiple copy() calls create independent instances', function(): void {
            $source = [
                'name' => 'Alice',
                'email' => 'alice@example.com',
                'phone' => '123-456-7890',
            ];

            $original = DataMapper::source($source)
                ->template([
                    'name' => '{{ name }}',
                ]);

            $copy1 = $original->copy()
                ->extendTemplate(['email' => '{{ email }}']);

            $copy2 = $original->copy()
                ->extendTemplate(['phone' => '{{ phone }}']);

            $originalResult = $original->map();
            $copy1Result = $copy1->map();
            $copy2Result = $copy2->map();

            expect($originalResult->toArray())->toBe([
                'name' => 'Alice',
            ]);

            expect($copy1Result->toArray())->toBe([
                'name' => 'Alice',
                'email' => 'alice@example.com',
            ]);

            expect($copy2Result->toArray())->toBe([
                'name' => 'Alice',
                'phone' => '123-456-7890',
            ]);
        });
    });
});

