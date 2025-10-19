<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;

describe('DataMapper Property Accessor', function(): void {
    $setupTestData = function(): void {
        $this->source = [
            'user' => [
                'name' => '  John Doe  ',
                'email' => '  john@example.com  ',
                'profile' => [
                    'bio' => '  Software Developer  ',
                    'age' => 30,
                ],
            ],
            'products' => [
                ['id' => 1, 'name' => '  Product A  ', 'price' => 100],
                ['id' => 2, 'name' => '  Product B  ', 'price' => 200],
            ],
        ];

        $this->template = [
            'userName' => '{{ user.name }}',
            'userEmail' => '{{ user.email }}',
            'bio' => '{{ user.profile.bio }}',
            'age' => '{{ user.profile.age }}',
            'items' => [
                '*' => [
                    'id' => '{{ products.*.id }}',
                    'name' => '{{ products.*.name }}',
                    'price' => '{{ products.*.price }}',
                ],
            ],
        ];
    };

    beforeEach($setupTestData);

    describe('property()->setFilter()', function() use ($setupTestData): void {
        beforeEach($setupTestData);
        it('sets filter for a property', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false)  // Disable global trim
                ->property('userName')
                    ->setFilter(new TrimStrings())
                    ->end();

            $result = $mapper->map();
            $target = $result->getTarget();

            expect($target['userName'])->toBe('John Doe');
            expect($target['userEmail'])->toBe('  john@example.com  '); // Not trimmed
        });

        it('sets multiple filters as arguments', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false)
                ->property('userName')
                    ->setFilter(new TrimStrings(), new UppercaseStrings())
                    ->end();

            $result = $mapper->map();
            $target = $result->getTarget();

            expect($target['userName'])->toBe('JOHN DOE');
        });

        it('sets multiple filters as array', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false)
                ->property('userName')
                    ->setFilter([new TrimStrings(), new UppercaseStrings()])
                    ->end();

            $result = $mapper->map();
            $target = $result->getTarget();

            expect($target['userName'])->toBe('JOHN DOE');
        });

        it('works with nested properties', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false)
                ->property('bio')
                    ->setFilter(new TrimStrings())
                    ->end();

            $result = $mapper->map();
            $target = $result->getTarget();

            expect($target['bio'])->toBe('Software Developer');
        });

        it('can be chained for multiple properties', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false)
                ->property('userName')
                    ->setFilter(new TrimStrings())
                    ->end()
                ->property('userEmail')
                    ->setFilter(new TrimStrings())
                    ->end();

            $result = $mapper->map();
            $target = $result->getTarget();

            expect($target['userName'])->toBe('John Doe');
            expect($target['userEmail'])->toBe('john@example.com');
        });
    });

    describe('property()->resetFilter()', function() use ($setupTestData): void {
        beforeEach($setupTestData);

        it('resets filter for a property', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false)
                ->property('userName')
                    ->setFilter(new TrimStrings())
                    ->end();

            // Reset filter
            $mapper->property('userName')->resetFilter();

            $result = $mapper->map();
            $target = $result->getTarget();

            expect($target['userName'])->toBe('  John Doe  '); // Not trimmed
        });

        it('works even if no filter was set', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false);

            // Reset filter (should not error)
            $mapper->property('userName')->resetFilter();

            $result = $mapper->map();
            $target = $result->getTarget();

            expect($target['userName'])->toBe('  John Doe  ');
        });

        it('can be chained with setFilter', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false)
                ->property('userName')
                    ->setFilter(new TrimStrings())
                    ->resetFilter()
                    ->setFilter(new UppercaseStrings())
                    ->end();

            $result = $mapper->map();
            $target = $result->getTarget();

            expect($target['userName'])->toBe('  JOHN DOE  '); // Uppercase but not trimmed
        });
    });

    describe('property()->getFilter()', function() use ($setupTestData): void {
        beforeEach($setupTestData);

        it('returns filters for a property', function(): void {
            $filter1 = new TrimStrings();
            $filter2 = new UppercaseStrings();

            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->property('userName')
                    ->setFilter($filter1, $filter2)
                    ->end();

            $filters = $mapper->property('userName')->getFilter();

            expect($filters)->toHaveCount(2);
            expect($filters[0])->toBe($filter1);
            expect($filters[1])->toBe($filter2);
        });

        it('returns empty array if no filters set', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template);

            $filters = $mapper->property('userName')->getFilter();

            expect($filters)->toBeArray();
            expect($filters)->toHaveCount(0);
        });

        it('returns empty array after resetFilter', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->property('userName')
                    ->setFilter(new TrimStrings())
                    ->resetFilter()
                    ->end();

            $filters = $mapper->property('userName')->getFilter();

            expect($filters)->toHaveCount(0);
        });
    });

    describe('property()->getTarget()', function() use ($setupTestData): void {
        beforeEach($setupTestData);

        it('returns the mapping target for a property', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template);

            $target = $mapper->property('userName')->getTarget();

            expect($target)->toBe('{{ user.name }}');
        });

        it('returns nested template values', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template);

            $target = $mapper->property('items')->getTarget();

            expect($target)->toBeArray();
            expect($target['*']['id'])->toBe('{{ products.*.id }}');
        });

        it('returns null for non-existent property', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template);

            $target = $mapper->property('nonExistent')->getTarget();

            expect($target)->toBeNull();
        });

        it('works with nested property paths', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'user' => [
                        'name' => '{{ user.name }}',
                        'email' => '{{ user.email }}',
                    ],
                ]);

            $target = $mapper->property('user.name')->getTarget();

            expect($target)->toBe('{{ user.name }}');
        });
    });

    describe('property()->getMappedValue()', function() use ($setupTestData): void {
        beforeEach($setupTestData);

        it('returns the mapped value for a property', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false)
                ->property('userName')
                    ->setFilter(new TrimStrings())
                    ->end();

            $value = $mapper->property('userName')->getMappedValue();

            expect($value)->toBe('John Doe');
        });

        it('returns nested values', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template);

            $value = $mapper->property('age')->getMappedValue();

            expect($value)->toBe(30);
        });

        it('returns null for non-existent property', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template);

            $value = $mapper->property('nonExistent')->getMappedValue();

            expect($value)->toBeNull();
        });

        it('works with nested property paths', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'user' => [
                        'name' => '{{ user.name }}',
                        'email' => '{{ user.email }}',
                    ],
                ])
                ->trimValues(false);

            $value = $mapper->property('user.name')->getMappedValue();

            expect($value)->toBe('  John Doe  ');
        });

        it('returns array values', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template);

            $value = $mapper->property('items')->getMappedValue();

            expect($value)->toBeArray();
            expect($value)->toHaveCount(2);
            expect($value[0]['id'])->toBe(1);
        });
    });

    describe('property()->end()', function() use ($setupTestData): void {
        beforeEach($setupTestData);

        it('returns the parent mapper', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template);

            $result = $mapper->property('userName')->end();

            expect($result)->toBe($mapper);
        });

        it('allows continuing fluent chain', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->property('userName')
                    ->setFilter(new TrimStrings())
                    ->end()
                ->property('userEmail')
                    ->setFilter(new TrimStrings())
                    ->end();

            $result = $mapper->map();
            $target = $result->getTarget();

            expect($target['userName'])->toBe('John Doe');
            expect($target['userEmail'])->toBe('john@example.com');
        });
    });

    describe('Edge Cases', function() use ($setupTestData): void {
        beforeEach($setupTestData);

        it('handles empty property path', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template);

            $target = $mapper->property('')->getTarget();

            expect($target)->toBeNull();
        });

        it('handles property path with multiple dots', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template([
                    'user' => [
                        'profile' => [
                            'bio' => '{{ user.profile.bio }}',
                        ],
                    ],
                ])
                ->trimValues(false);

            $value = $mapper->property('user.profile.bio')->getMappedValue();

            expect($value)->toBe('  Software Developer  ');
        });

        it('handles wildcard properties', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template);

            $value = $mapper->property('items')->getMappedValue();

            expect($value)->toBeArray();
            expect($value)->toHaveCount(2);
        });

        it('handles null values in source', function(): void {
            $source = [
                'user' => [
                    'name' => null,
                ],
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'userName' => '{{ user.name }}',
                ]);

            $value = $mapper->property('userName')->getMappedValue();

            expect($value)->toBeNull();
        });

        it('handles numeric property keys', function(): void {
            $source = [
                'items' => [
                    0 => 'first',
                    1 => 'second',
                ],
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'items' => [
                        0 => '{{ items.0 }}',
                        1 => '{{ items.1 }}',
                    ],
                ]);

            $value = $mapper->property('items.0')->getMappedValue();

            expect($value)->toBe('first');
        });

        it('handles special characters in values', function(): void {
            $source = [
                'user' => [
                    'name' => '  John "Doe" O\'Brien  ',
                ],
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'userName' => '{{ user.name }}',
                ])
                ->trimValues(false)
                ->property('userName')
                    ->setFilter(new TrimStrings())
                    ->end();

            $value = $mapper->property('userName')->getMappedValue();

            expect($value)->toBe('John "Doe" O\'Brien');
        });

        it('handles empty arrays', function(): void {
            $source = [
                'items' => [],
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'items' => [
                        '*' => [
                            'id' => '{{ items.*.id }}',
                        ],
                    ],
                ]);

            $value = $mapper->property('items')->getMappedValue();

            // Empty wildcard arrays result in null, not empty array
            expect($value)->toBeNull();
        });

        it('handles deeply nested paths', function(): void {
            $source = [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'level4' => [
                                'value' => 'deep',
                            ],
                        ],
                    ],
                ],
            ];

            $mapper = DataMapper::source($source)
                ->template([
                    'deep' => [
                        'nested' => [
                            'value' => '{{ level1.level2.level3.level4.value }}',
                        ],
                    ],
                ]);

            $value = $mapper->property('deep.nested.value')->getMappedValue();

            expect($value)->toBe('deep');
        });

        it('handles overwriting filters', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false)
                ->property('userName')
                    ->setFilter(new TrimStrings())
                    ->end()
                ->property('userName')
                    ->setFilter(new UppercaseStrings())
                    ->end();

            $result = $mapper->map();
            $target = $result->getTarget();

            // Second setFilter should overwrite first
            expect($target['userName'])->toBe('  JOHN DOE  ');
        });

        it('handles getMappedValue called multiple times', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false)
                ->property('userName')
                    ->setFilter(new TrimStrings())
                    ->end();

            $value1 = $mapper->property('userName')->getMappedValue();
            $value2 = $mapper->property('userName')->getMappedValue();

            expect($value1)->toBe('John Doe');
            expect($value2)->toBe('John Doe');
            expect($value1)->toBe($value2);
        });
    });

    describe('Integration with setFilter()', function() use ($setupTestData): void {
        beforeEach($setupTestData);

        it('works alongside setFilter() method', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false)
                ->setFilter('userName', new TrimStrings())
                ->property('userEmail')
                    ->setFilter(new TrimStrings())
                    ->end();

            $result = $mapper->map();
            $target = $result->getTarget();

            expect($target['userName'])->toBe('John Doe');
            expect($target['userEmail'])->toBe('john@example.com');
        });

        it('property()->setFilter() overwrites setFilter()', function(): void {
            $mapper = DataMapper::source($this->source)
                ->template($this->template)
                ->trimValues(false)
                ->setFilter('userName', new TrimStrings())
                ->property('userName')
                    ->setFilter(new UppercaseStrings())
                    ->end();

            $result = $mapper->map();
            $target = $result->getTarget();

            // property()->setFilter() should overwrite
            expect($target['userName'])->toBe('  JOHN DOE  ');
        });
    });
});

