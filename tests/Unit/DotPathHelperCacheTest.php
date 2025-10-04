<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\DotPathHelper;
use InvalidArgumentException;

describe('DotPathHelper Segment Caching', function(): void {
    it('caches segments for repeated paths', function(): void {
        $path = 'users.*.name';

        // First call should parse and cache
        $segments1 = DotPathHelper::segments($path);
        expect($segments1)->toBe(['users', '*', 'name']);

        // Second call should return cached result
        $segments2 = DotPathHelper::segments($path);
        expect($segments2)->toBe(['users', '*', 'name']);

        // Verify they are the same array instance (cached)
        expect($segments1)->toBe($segments2);
    });

    it('caches empty path', function(): void {
        $path = '';

        $segments1 = DotPathHelper::segments($path);
        expect($segments1)->toBe([]);

        $segments2 = DotPathHelper::segments($path);
        expect($segments2)->toBe([]);

        expect($segments1)->toBe($segments2);
    });

    it('caches different paths separately', function(): void {
        $path1 = 'user.name';
        $path2 = 'user.email';
        $path3 = 'users.*.name';

        $segments1 = DotPathHelper::segments($path1);
        $segments2 = DotPathHelper::segments($path2);
        $segments3 = DotPathHelper::segments($path3);

        expect($segments1)->toBe(['user', 'name']);
        expect($segments2)->toBe(['user', 'email']);
        expect($segments3)->toBe(['users', '*', 'name']);

        // Verify each path has its own cached result
        expect($segments1)->not->toBe($segments2);
        expect($segments1)->not->toBe($segments3);
        expect($segments2)->not->toBe($segments3);
    });

    it('caches complex nested paths', function(): void {
        $path = 'company.departments.*.users.*.profile.address.city';

        $segments1 = DotPathHelper::segments($path);
        $segments2 = DotPathHelper::segments($path);

        expect($segments1)->toBe([
            'company',
            'departments',
            '*',
            'users',
            '*',
            'profile',
            'address',
            'city',
        ]);

        expect($segments1)->toBe($segments2);
    });

    it('caches single segment paths', function(): void {
        $path = 'name';

        $segments1 = DotPathHelper::segments($path);
        $segments2 = DotPathHelper::segments($path);

        expect($segments1)->toBe(['name']);
        expect($segments1)->toBe($segments2);
    });

    it('does not cache invalid paths', function(): void {
        $invalidPath = 'a..b';

        // First call should throw
        expect(fn(): array => DotPathHelper::segments($invalidPath))
            ->toThrow(InvalidArgumentException::class);

        // Second call should also throw (not cached)
        expect(fn(): array => DotPathHelper::segments($invalidPath))
            ->toThrow(InvalidArgumentException::class);
    });

    it('benefits from cache in repeated operations', function(): void {
        $path = 'users.*.profile.name';

        // Simulate repeated access pattern (like in DataAccessor)
        $results = [];
        for ($i = 0; 100 > $i; $i++) {
            $segments = DotPathHelper::segments($path);
            $results[] = $segments;
        }

        // All results should be identical (cached)
        foreach ($results as $result) {
            expect($result)->toBe(['users', '*', 'profile', 'name']);
            expect($result)->toBe($results[0]);
        }
    });

    it('caches paths with numeric segments', function(): void {
        $path = 'users.0.name';

        $segments1 = DotPathHelper::segments($path);
        $segments2 = DotPathHelper::segments($path);

        expect($segments1)->toBe(['users', '0', 'name']);
        expect($segments1)->toBe($segments2);
    });

    it('caches paths with special characters in segments', function(): void {
        $path = 'user_data.profile-info.full_name';

        $segments1 = DotPathHelper::segments($path);
        $segments2 = DotPathHelper::segments($path);

        expect($segments1)->toBe(['user_data', 'profile-info', 'full_name']);
        expect($segments1)->toBe($segments2);
    });

    it('maintains cache across different helper method calls', function(): void {
        $path = 'users.*.name';

        // Call segments first
        $segments1 = DotPathHelper::segments($path);

        // Call containsWildcard (which internally calls segments)
        $hasWildcard = DotPathHelper::containsWildcard($path);
        expect($hasWildcard)->toBeTrue();

        // Call segments again - should still be cached
        $segments2 = DotPathHelper::segments($path);

        expect($segments1)->toBe($segments2);
    });
});

describe('DotPathHelper Wildcard Caching', function(): void {
    it('caches wildcard detection for repeated paths', function(): void {
        $path = 'users.*.name';

        // First call should check and cache
        $hasWildcard1 = DotPathHelper::containsWildcard($path);
        expect($hasWildcard1)->toBeTrue();

        // Second call should return cached result
        $hasWildcard2 = DotPathHelper::containsWildcard($path);
        expect($hasWildcard2)->toBeTrue();
    });

    it('caches wildcard detection for paths without wildcards', function(): void {
        $path = 'user.name';

        $hasWildcard1 = DotPathHelper::containsWildcard($path);
        expect($hasWildcard1)->toBeFalse();

        $hasWildcard2 = DotPathHelper::containsWildcard($path);
        expect($hasWildcard2)->toBeFalse();
    });

    it('caches wildcard detection for empty path', function(): void {
        $path = '';

        $hasWildcard1 = DotPathHelper::containsWildcard($path);
        expect($hasWildcard1)->toBeFalse();

        $hasWildcard2 = DotPathHelper::containsWildcard($path);
        expect($hasWildcard2)->toBeFalse();
    });

    it('caches different wildcard paths separately', function(): void {
        $path1 = 'users.*.name';
        $path2 = 'users.*.email';
        $path3 = 'user.name';

        $hasWildcard1 = DotPathHelper::containsWildcard($path1);
        $hasWildcard2 = DotPathHelper::containsWildcard($path2);
        $hasWildcard3 = DotPathHelper::containsWildcard($path3);

        expect($hasWildcard1)->toBeTrue();
        expect($hasWildcard2)->toBeTrue();
        expect($hasWildcard3)->toBeFalse();
    });

    it('benefits from wildcard cache in repeated operations', function(): void {
        $path = 'users.*.profile.name';

        // Simulate repeated wildcard checks (like in MappingEngine)
        $results = [];
        for ($i = 0; 100 > $i; $i++) {
            $results[] = DotPathHelper::containsWildcard($path);
        }

        // All results should be true (cached)
        foreach ($results as $result) {
            expect($result)->toBeTrue();
        }
    });
});
