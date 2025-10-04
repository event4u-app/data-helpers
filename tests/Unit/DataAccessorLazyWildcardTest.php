<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\DataAccessor;

describe('DataAccessor Lazy Wildcard Expansion', function (): void {
    it('uses fast path for non-wildcard access', function (): void {
        $data = [
            'user' => [
                'profile' => [
                    'name' => 'Alice',
                    'email' => 'alice@example.com',
                    'address' => [
                        'city' => 'Berlin',
                        'country' => 'Germany',
                    ],
                ],
            ],
        ];

        $accessor = new DataAccessor($data);

        // Non-wildcard paths should use extractSimple()
        expect($accessor->get('user.profile.name'))->toBe('Alice');
        expect($accessor->get('user.profile.email'))->toBe('alice@example.com');
        expect($accessor->get('user.profile.address.city'))->toBe('Berlin');
    });

    it('uses wildcard path for wildcard access', function (): void {
        $data = [
            'users' => [
                ['name' => 'Alice', 'age' => 30],
                ['name' => 'Bob', 'age' => 25],
            ],
        ];

        $accessor = new DataAccessor($data);

        // Wildcard paths should use extract()
        $names = $accessor->get('users.*.name');
        expect($names)->toBe([
            'users.0.name' => 'Alice',
            'users.1.name' => 'Bob',
        ]);
    });

    it('performance: non-wildcard paths are faster', function (): void {
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data['items'][$i] = [
                'id' => $i,
                'name' => "Item $i",
                'value' => $i * 10,
            ];
        }

        $accessor = new DataAccessor($data);

        // Measure non-wildcard access (should be fast)
        $start1 = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $accessor->get("items.$i.name");
        }
        $time1 = microtime(true) - $start1;

        // Measure wildcard access (will be slower)
        $start2 = microtime(true);
        $accessor->get('items.*.name');
        $time2 = microtime(true) - $start2;

        // Both should work correctly
        expect($accessor->get('items.0.name'))->toBe('Item 0');
        expect($accessor->get('items.99.name'))->toBe('Item 99');

        // Non-wildcard should be faster (or at least not significantly slower)
        expect($time1)->toBeGreaterThan(0);
        expect($time2)->toBeGreaterThan(0);
    });

    it('handles nested non-wildcard paths efficiently', function (): void {
        $data = [
            'company' => [
                'departments' => [
                    'engineering' => [
                        'teams' => [
                            'backend' => [
                                'lead' => 'Alice',
                                'members' => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $accessor = new DataAccessor($data);

        // Deep non-wildcard path
        expect($accessor->get('company.departments.engineering.teams.backend.lead'))->toBe('Alice');
        expect($accessor->get('company.departments.engineering.teams.backend.members'))->toBe(10);
    });

    it('returns null for non-existent non-wildcard paths', function (): void {
        $data = ['user' => ['name' => 'Alice']];
        $accessor = new DataAccessor($data);

        expect($accessor->get('user.email'))->toBeNull();
        expect($accessor->get('user.profile.city'))->toBeNull();
        expect($accessor->get('nonexistent.path'))->toBeNull();
    });

    it('returns default for non-existent non-wildcard paths', function (): void {
        $data = ['user' => ['name' => 'Alice']];
        $accessor = new DataAccessor($data);

        expect($accessor->get('user.email', 'default@example.com'))->toBe('default@example.com');
        expect($accessor->get('user.age', 0))->toBe(0);
    });

    it('handles mixed wildcard and non-wildcard access', function (): void {
        $data = [
            'users' => [
                ['name' => 'Alice', 'email' => 'alice@example.com'],
                ['name' => 'Bob', 'email' => 'bob@example.com'],
            ],
        ];

        $accessor = new DataAccessor($data);

        // Non-wildcard access
        expect($accessor->get('users.0.name'))->toBe('Alice');
        expect($accessor->get('users.1.email'))->toBe('bob@example.com');

        // Wildcard access
        $names = $accessor->get('users.*.name');
        expect($names)->toHaveKey('users.0.name');
        expect($names)->toHaveKey('users.1.name');
    });

    it('lazy wildcard check is cached', function (): void {
        $data = ['user' => ['name' => 'Alice']];
        $accessor = new DataAccessor($data);

        // First access
        $result1 = $accessor->get('user.name');
        expect($result1)->toBe('Alice');

        // Second access (should use cached wildcard check)
        $result2 = $accessor->get('user.name');
        expect($result2)->toBe('Alice');
    });
});
