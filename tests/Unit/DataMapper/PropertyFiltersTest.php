<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;
use event4u\DataHelpers\Enums\DataMapperHook;

describe('FluentDataMapper - Property-specific Filters', function(): void {
    it('can set a single filter for a property', function(): void {
        $source = [
            'user' => [
                'name' => '  John Doe  ',
                'email' => 'john@example.com',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings())
            ->map();

        expect($result->getTarget()['name'])->toBe('John Doe');
        expect($result->getTarget()['email'])->toBe('john@example.com');
    });

    it('can set multiple filters as arguments for a property', function(): void {
        $source = [
            'user' => [
                'name' => '  John Doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings(), new UppercaseStrings())
            ->map();

        expect($result->getTarget()['name'])->toBe('JOHN DOE');
    });

    it('can set multiple filters as array for a property', function(): void {
        $source = [
            'user' => [
                'name' => '  John Doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', [new TrimStrings(), new UppercaseStrings()])
            ->map();

        expect($result->getTarget()['name'])->toBe('JOHN DOE');
    });

    it('can set a single filter as array for a property', function(): void {
        $source = [
            'user' => [
                'name' => '  John Doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', [new TrimStrings()])
            ->map();

        expect($result->getTarget()['name'])->toBe('John Doe');
    });

    it('behaves identically for multiple filters as arguments vs array', function(): void {
        $source = [
            'user' => [
                'name' => '  John Doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        // Variant 1: Multiple filters as arguments
        $result1 = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings(), new UppercaseStrings())
            ->map();

        // Variant 2: Multiple filters as array
        $result2 = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', [new TrimStrings(), new UppercaseStrings()])
            ->map();

        expect($result1->getTarget()['name'])->toBe($result2->getTarget()['name']);
        expect($result1->getTarget()['name'])->toBe('JOHN DOE');
    });

    it('behaves identically for single filter vs single filter as array', function(): void {
        $source = [
            'user' => [
                'name' => '  John Doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        // Variant 1: Single filter
        $result1 = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings())
            ->map();

        // Variant 2: Single filter as array
        $result2 = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', [new TrimStrings()])
            ->map();

        expect($result1->getTarget()['name'])->toBe($result2->getTarget()['name']);
        expect($result1->getTarget()['name'])->toBe('John Doe');
    });

    it('can set filters for multiple properties', function(): void {
        $source = [
            'user' => [
                'firstName' => '  John  ',
                'lastName' => '  Doe  ',
                'email' => '  JOHN@EXAMPLE.COM  ',
            ],
        ];

        $template = [
            'firstName' => '{{ user.firstName }}',
            'lastName' => '{{ user.lastName }}',
            'email' => '{{ user.email }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('firstName', new TrimStrings())
            ->setValueFilters('lastName', new TrimStrings())
            ->setValueFilters('email', new TrimStrings(), new LowercaseStrings())
            ->map();

        expect($result->getTarget()['firstName'])->toBe('John');
        expect($result->getTarget()['lastName'])->toBe('Doe');
        expect($result->getTarget()['email'])->toBe('john@example.com');
    });

    it('supports dot-notation for nested properties', function(): void {
        $source = [
            'user' => [
                'profile' => [
                    'name' => '  John Doe  ',
                ],
            ],
        ];

        $template = [
            'profile' => [
                'name' => '{{ user.profile.name }}',
            ],
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('profile.name', new TrimStrings())
            ->map();

        expect($result->getTarget()['profile']['name'])->toBe('John Doe');
    });

    it('applies filters in the correct order', function(): void {
        $source = [
            'user' => [
                'name' => '  john doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings(), new UppercaseStrings())
            ->map();

        // First trim, then uppercase
        expect($result->getTarget()['name'])->toBe('JOHN DOE');
    });

    it('does not affect properties without filters', function(): void {
        $source = [
            'user' => [
                'name' => '  John Doe  ',
                'email' => '  john@example.com  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->trimValues(false) // Disable global trimming
            ->setValueFilters('name', new TrimStrings())
            ->map();

        expect($result->getTarget()['name'])->toBe('John Doe');
        expect($result->getTarget()['email'])->toBe('  john@example.com  '); // Not trimmed
    });

    it('works with copy()', function(): void {
        $source = [
            'user' => [
                'name' => '  John Doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $mapper = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings());

        $copy = $mapper->copy();

        $result = $copy->map();

        expect($result->getTarget()['name'])->toBe('John Doe');
    });
});

describe('FluentDataMapper - Property-specific Filters - Edge Cases', function(): void {
    it('handles null values in filtered properties', function(): void {
        $source = [
            'user' => [
                'name' => null,
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->skipNull(false) // Don't skip null values
            ->setValueFilters('name', new TrimStrings())
            ->map();

        // TrimStrings should handle null gracefully
        expect($result->getTarget())->toHaveKey('name');
    });

    it('handles empty string values in filtered properties', function(): void {
        $source = [
            'user' => [
                'name' => '',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings())
            ->map();

        expect($result->getTarget()['name'])->toBe('');
    });

    it('handles non-existent properties gracefully', function(): void {
        $source = [
            'user' => [
                'email' => 'john@example.com',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->skipNull(false) // Don't skip null values
            ->setValueFilters('name', new TrimStrings())
            ->map();

        // Non-existent property should result in null
        expect($result->getTarget())->toHaveKey('name');
        expect($result->getTarget()['email'])->toBe('john@example.com');
    });

    it('handles deeply nested properties with dot-notation', function(): void {
        $source = [
            'user' => [
                'profile' => [
                    'address' => [
                        'city' => '  Berlin  ',
                    ],
                ],
            ],
        ];

        $template = [
            'city' => '{{ user.profile.address.city }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('city', new TrimStrings())
            ->map();

        expect($result->getTarget()['city'])->toBe('Berlin');
    });

    it('handles wildcard properties', function(): void {
        $source = [
            'users' => [
                ['name' => '  John  '],
                ['name' => '  Jane  '],
            ],
        ];

        $template = [
            'names' => '{{ users.*.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('names', new TrimStrings())
            ->map();

        // Filter should be applied to the array result
        expect($result->getTarget()['names'])->toBeArray();
    });

    it('handles overwriting filters for the same property', function(): void {
        $source = [
            'user' => [
                'name' => '  john doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->trimValues(false) // Disable global trimming
            ->setValueFilters('name', new TrimStrings())
            ->setValueFilters('name', new UppercaseStrings()) // Overwrites previous
            ->map();

        // Only uppercase should be applied, not trim (because it was overwritten)
        expect($result->getTarget()['name'])->toBe('  JOHN DOE  ');
    });

    it('works with reverseMap()', function(): void {
        $source = [
            'name' => '  John Doe  ',
        ];

        $template = [
            'name' => '{{ name }}',
        ];

        // Test that reverseMap doesn't crash with property filters
        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings())
            ->reverseMap();

        // reverseMap with same source and target should work
        expect($result->getTarget()['name'])->toBe('John Doe');
    });

    it('handles numeric property keys', function(): void {
        $source = [
            'data' => [
                0 => '  value0  ',
                1 => '  value1  ',
            ],
        ];

        $template = [
            'value0' => '{{ data.0 }}',
            'value1' => '{{ data.1 }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->trimValues(false) // Disable global trimming
            ->setValueFilters('value0', new TrimStrings())
            ->map();

        expect($result->getTarget()['value0'])->toBe('value0');
        expect($result->getTarget()['value1'])->toBe('  value1  ');
    });

    it('handles special characters in property names', function(): void {
        $source = [
            'user-data' => [
                'first_name' => '  John  ',
            ],
        ];

        $template = [
            'firstName' => '{{ user-data.first_name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('firstName', new TrimStrings())
            ->map();

        expect($result->getTarget()['firstName'])->toBe('John');
    });

    it('handles unicode characters in values', function(): void {
        $source = [
            'user' => [
                'name' => '  Müller  ',
                'emoji' => '  😀  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
            'emoji' => '{{ user.emoji }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings())
            ->setValueFilters('emoji', new TrimStrings())
            ->map();

        expect($result->getTarget()['name'])->toBe('Müller');
        expect($result->getTarget()['emoji'])->toBe('😀');
    });

    it('handles very long filter chains', function(): void {
        $source = [
            'user' => [
                'name' => '  john doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters(
                'name',
                new TrimStrings(),
                new UppercaseStrings(),
                new LowercaseStrings(),
                new UppercaseStrings()
            )
            ->map();

        expect($result->getTarget()['name'])->toBe('JOHN DOE');
    });

    it('handles empty filter array', function(): void {
        $source = [
            'user' => [
                'name' => '  John Doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->trimValues(false) // Disable global trimming
            ->setValueFilters('name', [])
            ->map();

        expect($result->getTarget()['name'])->toBe('  John Doe  ');
    });

    it('handles mixed nested arrays and objects', function(): void {
        $source = [
            'data' => [
                'items' => [
                    ['value' => '  item1  '],
                    ['value' => '  item2  '],
                ],
            ],
        ];

        $template = [
            'items' => [
                ['value' => '{{ data.items.0.value }}'],
                ['value' => '{{ data.items.1.value }}'],
            ],
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->trimValues(false) // Disable global trimming
            ->setValueFilters('items.0.value', new TrimStrings())
            ->map();

        expect($result->getTarget()['items'][0]['value'])->toBe('item1');
        expect($result->getTarget()['items'][1]['value'])->toBe('  item2  ');
    });

    it('works with skipNull option', function(): void {
        $source = [
            'user' => [
                'name' => null,
                'email' => '  john@example.com  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings())
            ->setValueFilters('email', new TrimStrings())
            ->map();

        expect($result->getTarget())->not->toHaveKey('name');
        expect($result->getTarget()['email'])->toBe('john@example.com');
    });

    it('works with reindexWildcard option', function(): void {
        $source = [
            'users' => [
                0 => ['name' => '  John  '],
                2 => ['name' => '  Jane  '],
            ],
        ];

        $template = [
            'names' => '{{ users.*.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->reindexWildcard(true)
            ->setValueFilters('names', new TrimStrings())
            ->map();

        expect($result->getTarget()['names'])->toBeArray();
    });

    it('handles filters with global pipeline filters', function(): void {
        $source = [
            'user' => [
                'name' => '  john doe  ',
                'email' => '  JOHN@EXAMPLE.COM  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
            'email' => '{{ user.email }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->pipeline([new TrimStrings()]) // Global pipeline
            ->setValueFilters('name', new UppercaseStrings()) // Property-specific
            ->map();

        // Global trim + property-specific uppercase
        expect($result->getTarget()['name'])->toBe('JOHN DOE');
        // Only global trim
        expect($result->getTarget()['email'])->toBe('JOHN@EXAMPLE.COM');
    });

    it('handles filters with hooks', function(): void {
        $source = [
            'user' => [
                'name' => '  John Doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $hookCalled = false;

        $result = DataMapper::source($source)
            ->template($template)
            ->hooks([
                DataMapperHook::BeforeTransform->value => function($value, $context) use (&$hookCalled) {
                    $hookCalled = true;
                    return $value;
                },
            ])
            ->setValueFilters('name', new TrimStrings())
            ->map();

        expect($hookCalled)->toBeTrue();
        expect($result->getTarget()['name'])->toBe('John Doe');
    });

    it('handles boolean values', function(): void {
        $source = [
            'user' => [
                'active' => true,
            ],
        ];

        $template = [
            'active' => '{{ user.active }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('active', new TrimStrings())
            ->map();

        expect($result->getTarget()['active'])->toBeTrue();
    });

    it('handles numeric values', function(): void {
        $source = [
            'user' => [
                'age' => 25,
                'salary' => 50000.50,
            ],
        ];

        $template = [
            'age' => '{{ user.age }}',
            'salary' => '{{ user.salary }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('age', new TrimStrings())
            ->setValueFilters('salary', new TrimStrings())
            ->map();

        expect($result->getTarget()['age'])->toBe(25);
        expect($result->getTarget()['salary'])->toBe(50000.50);
    });

    it('handles array values', function(): void {
        $source = [
            'user' => [
                'tags' => ['php', 'laravel'],
            ],
        ];

        $template = [
            'tags' => '{{ user.tags }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('tags', new TrimStrings())
            ->map();

        expect($result->getTarget()['tags'])->toBe(['php', 'laravel']);
    });

    it('handles object values', function(): void {
        $source = [
            'user' => [
                'profile' => (object)['name' => '  John  '],
            ],
        ];

        $template = [
            'profile' => '{{ user.profile }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('profile', new TrimStrings())
            ->map();

        expect($result->getTarget()['profile'])->toBeObject();
    });

    it('handles multiple calls to setValueFilters for different properties', function(): void {
        $source = [
            'user' => [
                'firstName' => '  John  ',
                'lastName' => '  Doe  ',
                'email' => '  JOHN@EXAMPLE.COM  ',
            ],
        ];

        $template = [
            'firstName' => '{{ user.firstName }}',
            'lastName' => '{{ user.lastName }}',
            'email' => '{{ user.email }}',
        ];

        $mapper = DataMapper::source($source)
            ->template($template);

        $mapper->setValueFilters('firstName', new TrimStrings());
        $mapper->setValueFilters('lastName', new TrimStrings(), new UppercaseStrings());
        $mapper->setValueFilters('email', new TrimStrings(), new LowercaseStrings());

        $result = $mapper->map();

        expect($result->getTarget()['firstName'])->toBe('John');
        expect($result->getTarget()['lastName'])->toBe('DOE');
        expect($result->getTarget()['email'])->toBe('john@example.com');
    });

    it('handles copy() with modified filters', function(): void {
        $source = [
            'user' => [
                'name' => '  john doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $mapper = DataMapper::source($source)
            ->template($template)
            ->trimValues(false) // Disable global trimming
            ->setValueFilters('name', new TrimStrings());

        $copy = $mapper->copy();
        $copy->setValueFilters('name', new UppercaseStrings()); // Overwrite in copy

        $result1 = $mapper->map();
        $result2 = $copy->map();

        expect($result1->getTarget()['name'])->toBe('john doe'); // Original: only trim
        expect($result2->getTarget()['name'])->toBe('  JOHN DOE  '); // Copy: only uppercase
    });

    it('handles whitespace-only values', function(): void {
        $source = [
            'user' => [
                'name' => '     ',
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings())
            ->map();

        expect($result->getTarget()['name'])->toBe('');
    });

    it('handles newlines and tabs in values', function(): void {
        $source = [
            'user' => [
                'name' => "  \n\tJohn Doe\t\n  ",
            ],
        ];

        $template = [
            'name' => '{{ user.name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings())
            ->map();

        expect($result->getTarget()['name'])->toBe("John Doe");
    });

    it('handles case-insensitive property matching', function(): void {
        $source = [
            'user' => [
                'Name' => '  John Doe  ',
            ],
        ];

        $template = [
            'name' => '{{ user.Name }}',
        ];

        $result = DataMapper::source($source)
            ->template($template)
            ->setValueFilters('name', new TrimStrings())
            ->map();

        expect($result->getTarget()['name'])->toBe('John Doe');
    });

    it('handles large number of properties with filters', function(): void {
        $source = [];
        $template = [];

        for ($i = 0; 100 > $i; $i++) {
            $source['field' . $i] = sprintf('  value%d  ', $i);
            $template['field' . $i] = sprintf('{{ field%d }}', $i);
        }

        $mapper = DataMapper::source($source)->template($template);

        for ($i = 0; 100 > $i; $i++) {
            $mapper->setValueFilters('field' . $i, new TrimStrings());
        }

        $result = $mapper->map();

        for ($i = 0; 100 > $i; $i++) {
            expect($result->getTarget()['field' . $i])->toBe('value' . $i);
        }
    });
});
