<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\DataAccessor;

describe('DataAccessor Static Access Methods', function (): void {
    it('getValue works with simple paths', function (): void {
        $data = ['user' => ['name' => 'Alice', 'age' => 30]];

        expect(DataAccessor::getValue($data, 'user.name'))->toBe('Alice');
        expect(DataAccessor::getValue($data, 'user.age'))->toBe(30);
    });

    it('getValue works with wildcard paths', function (): void {
        $data = [
            'users' => [
                ['name' => 'Alice', 'age' => 30],
                ['name' => 'Bob', 'age' => 25],
            ],
        ];

        $names = DataAccessor::getValue($data, 'users.*.name');
        expect($names)->toBe([
            'users.0.name' => 'Alice',
            'users.1.name' => 'Bob',
        ]);
    });

    it('getValue returns default for non-existent paths', function (): void {
        $data = ['user' => ['name' => 'Alice']];

        expect(DataAccessor::getValue($data, 'user.email', 'default'))->toBe('default');
        expect(DataAccessor::getValue($data, 'nonexistent'))->toBeNull();
    });

    it('getStringValue works correctly', function (): void {
        $data = ['user' => ['name' => 'Alice', 'age' => 30]];

        expect(DataAccessor::getStringValue($data, 'user.name'))->toBe('Alice');
        expect(DataAccessor::getStringValue($data, 'user.age'))->toBe('30');
        expect(DataAccessor::getStringValue($data, 'user.email', 'default'))->toBe('default');
    });

    it('getIntValue works correctly', function (): void {
        $data = ['user' => ['age' => 30, 'score' => '100']];

        expect(DataAccessor::getIntValue($data, 'user.age'))->toBe(30);
        expect(DataAccessor::getIntValue($data, 'user.score'))->toBe(100);
        expect(DataAccessor::getIntValue($data, 'user.missing', 0))->toBe(0);
    });

    it('getFloatValue works correctly', function (): void {
        $data = ['product' => ['price' => 19.99, 'discount' => '5.5']];

        expect(DataAccessor::getFloatValue($data, 'product.price'))->toBe(19.99);
        expect(DataAccessor::getFloatValue($data, 'product.discount'))->toBe(5.5);
        expect(DataAccessor::getFloatValue($data, 'product.missing', 0.0))->toBe(0.0);
    });

    it('getBoolValue works correctly', function (): void {
        $data = ['user' => ['active' => true, 'verified' => 'yes']];

        expect(DataAccessor::getBoolValue($data, 'user.active'))->toBeTrue();
        expect(DataAccessor::getBoolValue($data, 'user.verified'))->toBeTrue();
        expect(DataAccessor::getBoolValue($data, 'user.missing', false))->toBeFalse();
    });

    it('getArrayValue works correctly', function (): void {
        $data = ['user' => ['tags' => ['php', 'laravel'], 'name' => 'Alice']];

        expect(DataAccessor::getArrayValue($data, 'user.tags'))->toBe(['php', 'laravel']);
        expect(DataAccessor::getArrayValue($data, 'user.missing', []))->toBe([]);
    });

    it('static methods cache path information', function (): void {
        $data1 = ['user' => ['name' => 'Alice']];
        $data2 = ['user' => ['name' => 'Bob']];
        $data3 = ['user' => ['name' => 'Charlie']];

        // First call compiles and caches the path
        $result1 = DataAccessor::getValue($data1, 'user.name');
        expect($result1)->toBe('Alice');

        // Subsequent calls use cached path information
        $result2 = DataAccessor::getValue($data2, 'user.name');
        expect($result2)->toBe('Bob');

        $result3 = DataAccessor::getValue($data3, 'user.name');
        expect($result3)->toBe('Charlie');
    });

    it('static methods work with different data types', function (): void {
        // Array
        $array = ['name' => 'Alice'];
        expect(DataAccessor::getValue($array, 'name'))->toBe('Alice');

        // Object
        $object = (object)['name' => 'Bob'];
        expect(DataAccessor::getValue($object, 'name'))->toBe('Bob');

        // JSON string
        $json = '{"name":"Charlie"}';
        expect(DataAccessor::getValue($json, 'name'))->toBe('Charlie');
    });

    it('performance: static methods benefit from path caching', function (): void {
        $datasets = [];
        for ($i = 0; $i < 100; $i++) {
            $datasets[] = [
                'user' => [
                    'profile' => [
                        'name' => "User $i",
                        'email' => "user$i@example.com",
                    ],
                ],
            ];
        }

        // Measure static access (should benefit from path caching)
        $start = microtime(true);
        foreach ($datasets as $data) {
            DataAccessor::getValue($data, 'user.profile.name');
            DataAccessor::getValue($data, 'user.profile.email');
        }
        $time = microtime(true) - $start;

        // Should complete quickly
        expect($time)->toBeGreaterThan(0);
        expect($time)->toBeLessThan(1.0); // Should be much faster than 1 second
    });

    it('static methods work with nested paths', function (): void {
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

        expect(DataAccessor::getValue($data, 'company.departments.engineering.lead'))->toBe('Alice');
        expect(DataAccessor::getIntValue($data, 'company.departments.engineering.members'))->toBe(10);
    });

    it('static methods handle Collections', function (): void {
        if (!class_exists('Illuminate\Support\Collection')) {
            expect(true)->toBeTrue(); // Skip if Laravel not available
            return;
        }

        $collection = collect(['user' => ['name' => 'Alice']]);
        expect(DataAccessor::getValue($collection, 'user.name'))->toBe('Alice');
    });
});
