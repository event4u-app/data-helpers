<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\FilterRegistry;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;
use Tests\utils\Filters\AlternatingCase;

describe('FilterRegistry', function(): void {
    afterEach(function(): void {
        // Clear registry after each test
        FilterRegistry::clear();
    });

    it('registers built-in filters automatically', function(): void {
        // Clear first to test auto-initialization
        FilterRegistry::clear();

        expect(FilterRegistry::has('trim'))->toBeTrue();
        expect(FilterRegistry::has('lower'))->toBeTrue();
        expect(FilterRegistry::has('lowercase'))->toBeTrue();
        expect(FilterRegistry::has('upper'))->toBeTrue();
        expect(FilterRegistry::has('uppercase'))->toBeTrue();
    });

    it('returns correct filter class for alias', function(): void {
        FilterRegistry::clear();

        expect(FilterRegistry::get('trim'))->toBe(TrimStrings::class);
        expect(FilterRegistry::get('lower'))->toBe(LowercaseStrings::class);
        expect(FilterRegistry::get('lowercase'))->toBe(LowercaseStrings::class);
        expect(FilterRegistry::get('upper'))->toBe(UppercaseStrings::class);
        expect(FilterRegistry::get('uppercase'))->toBe(UppercaseStrings::class);
    });

    it('returns null for unknown alias', function(): void {
        FilterRegistry::clear();

        expect(FilterRegistry::get('unknown'))->toBeNull();
        expect(FilterRegistry::has('unknown'))->toBeFalse();
    });

    it('registers custom filter', function(): void {
        FilterRegistry::register(AlternatingCase::class);

        expect(FilterRegistry::has('alternating'))->toBeTrue();
        expect(FilterRegistry::has('alt_case'))->toBeTrue();
        expect(FilterRegistry::has('zigzag'))->toBeTrue();
        expect(FilterRegistry::get('alternating'))->toBe(AlternatingCase::class);
    });

    it('registers multiple transformers at once', function(): void {
        FilterRegistry::clear();
        FilterRegistry::registerMany([
            TrimStrings::class,
            AlternatingCase::class,
        ]);

        expect(FilterRegistry::has('trim'))->toBeTrue();
        expect(FilterRegistry::has('alternating'))->toBeTrue();
    });

    it('returns all registered aliases', function(): void {
        FilterRegistry::clear();

        $all = FilterRegistry::all();

        expect($all)->toBeArray();
        expect($all)->toHaveKey('trim');
        expect($all)->toHaveKey('lower');
        expect($all)->toHaveKey('upper');
    });

    it('clears all registered aliases and re-initializes on next access', function(): void {
        FilterRegistry::clear();

        // After clear, built-in filters are auto-registered on first access
        expect(FilterRegistry::has('trim'))->toBeTrue();

        // Register a custom filter
        FilterRegistry::register(AlternatingCase::class);
        expect(FilterRegistry::has('alternating'))->toBeTrue();

        // Clear removes all (including custom)
        FilterRegistry::clear();

        // Built-in filters are re-registered automatically
        expect(FilterRegistry::has('trim'))->toBeTrue();

        // But custom filters are not
        expect(FilterRegistry::has('alternating'))->toBeFalse();
    });
});

