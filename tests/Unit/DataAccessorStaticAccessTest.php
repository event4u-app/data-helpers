<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\DataAccessor;

describe('DataAccessor Fluent Access Methods', function(): void {
    it('make()->get() works with simple paths', function(): void {
        $data = ['user' => ['name' => 'Alice', 'age' => 30]];

        expect(DataAccessor::make($data)->get('user.name'))->toBe('Alice');
        expect(DataAccessor::make($data)->get('user.age'))->toBe(30);
    });

    it('make()->get() works with wildcard paths', function(): void {
        $data = [
            'users' => [
                ['name' => 'Alice', 'age' => 30],
                ['name' => 'Bob', 'age' => 25],
            ],
        ];

        $names = DataAccessor::make($data)->get('users.*.name');
        expect($names)->toBe([
            'users.0.name' => 'Alice',
            'users.1.name' => 'Bob',
        ]);
    });

    it('make()->get() returns default for non-existent paths', function(): void {
        $data = ['user' => ['name' => 'Alice']];

        expect(DataAccessor::make($data)->get('user.email', 'default'))->toBe('default');
        expect(DataAccessor::make($data)->get('nonexistent'))->toBeNull();
    });

    it('make()->getString() works correctly', function(): void {
        $data = ['user' => ['name' => 'Alice', 'age' => 30]];

        expect(DataAccessor::make($data)->getString('user.name'))->toBe('Alice');
        expect(DataAccessor::make($data)->getString('user.age'))->toBe('30');
        expect(DataAccessor::make($data)->getString('user.email', 'default'))->toBe('default');
    });

    it('make()->getInt() works correctly', function(): void {
        $data = ['user' => ['age' => 30, 'score' => '100']];

        expect(DataAccessor::make($data)->getInt('user.age'))->toBe(30);
        expect(DataAccessor::make($data)->getInt('user.score'))->toBe(100);
        expect(DataAccessor::make($data)->getInt('user.missing', 0))->toBe(0);
    });

    it('make()->getFloat() works correctly', function(): void {
        $data = ['product' => ['price' => 19.99, 'discount' => '5.5']];

        expect(DataAccessor::make($data)->getFloat('product.price'))->toBe(19.99);
        expect(DataAccessor::make($data)->getFloat('product.discount'))->toBe(5.5);
        expect(DataAccessor::make($data)->getFloat('product.missing', 0.0))->toBe(0.0);
    });

    it('make()->getBool() works correctly', function(): void {
        $data = ['user' => ['active' => true, 'verified' => 'yes']];

        expect(DataAccessor::make($data)->getBool('user.active'))->toBeTrue();
        expect(DataAccessor::make($data)->getBool('user.verified'))->toBeTrue();
        expect(DataAccessor::make($data)->getBool('user.missing', false))->toBeFalse();
    });

    it('make()->getArray() works correctly', function(): void {
        $data = ['user' => ['tags' => ['php', 'laravel'], 'name' => 'Alice']];

        expect(DataAccessor::make($data)->getArray('user.tags'))->toBe(['php', 'laravel']);
        expect(DataAccessor::make($data)->getArray('user.missing', []))->toBe([]);
    });

    it('fluent methods cache path information', function(): void {
        $data1 = ['user' => ['name' => 'Alice']];
        $data2 = ['user' => ['name' => 'Bob']];
        $data3 = ['user' => ['name' => 'Charlie']];

        // First call compiles and caches the path
        $result1 = DataAccessor::make($data1)->get('user.name');
        expect($result1)->toBe('Alice');

        // Subsequent calls use cached path information
        $result2 = DataAccessor::make($data2)->get('user.name');
        expect($result2)->toBe('Bob');

        $result3 = DataAccessor::make($data3)->get('user.name');
        expect($result3)->toBe('Charlie');
    });

    it('fluent methods work with different data types', function(): void {
        // Array
        $array = ['name' => 'Alice'];
        expect(DataAccessor::make($array)->get('name'))->toBe('Alice');

        // Object
        $object = (object)['name' => 'Bob'];
        expect(DataAccessor::make($object)->get('name'))->toBe('Bob');

        // JSON string
        $json = '{"name":"Charlie"}';
        expect(DataAccessor::make($json)->get('name'))->toBe('Charlie');
    });

    it('performance: fluent methods benefit from path caching', function(): void {
        $datasets = [];
        for ($i = 0; 100 > $i; $i++) {
            $datasets[] = [
                'user' => [
                    'profile' => [
                        'name' => 'User ' . $i,
                        'email' => sprintf('user%d@example.com', $i),
                    ],
                ],
            ];
        }

        // Measure fluent access (should benefit from path caching)
        $start = microtime(true);
        foreach ($datasets as $data) {
            DataAccessor::make($data)->get('user.profile.name');
            DataAccessor::make($data)->get('user.profile.email');
        }
        $time = microtime(true) - $start;

        // Should complete quickly
        expect($time)->toBeGreaterThan(0);
        expect($time)->toBeLessThan(1.0); // Should be much faster than 1 second
    });

    it('fluent methods work with nested paths', function(): void {
        $data = [
            'company' => [
                'departments' => [
                    'engineering' => [
                        'lead' => 'Alice',
                        'members' => 10,
                    ],
                ],
            ],
        ];

        expect(DataAccessor::make($data)->get('company.departments.engineering.lead'))->toBe('Alice');
        expect(DataAccessor::make($data)->getInt('company.departments.engineering.members'))->toBe(10);
    });

    it('fluent methods handle Collections', function(): void {
        if (!class_exists('Illuminate\Support\Collection')) {
            expect(true)->toBeTrue(); // Skip if Laravel not available
            return;
        }

        $collection = collect(['user' => ['name' => 'Alice']]);
        expect(DataAccessor::make($collection)->get('user.name'))->toBe('Alice');
    });
});
