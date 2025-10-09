<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Pipeline\TransformerRegistry;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\LowercaseStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\TrimStrings;
use event4u\DataHelpers\DataMapper\Pipeline\Transformers\UppercaseStrings;
use Tests\utils\Transformers\AlternatingCase;

describe('TransformerRegistry', function(): void {
    afterEach(function(): void {
        // Clear registry after each test
        TransformerRegistry::clear();
    });

    it('registers built-in transformers automatically', function(): void {
        // Clear first to test auto-initialization
        TransformerRegistry::clear();

        expect(TransformerRegistry::has('trim'))->toBeTrue();
        expect(TransformerRegistry::has('lower'))->toBeTrue();
        expect(TransformerRegistry::has('lowercase'))->toBeTrue();
        expect(TransformerRegistry::has('upper'))->toBeTrue();
        expect(TransformerRegistry::has('uppercase'))->toBeTrue();
    });

    it('returns correct transformer class for alias', function(): void {
        TransformerRegistry::clear();

        expect(TransformerRegistry::get('trim'))->toBe(TrimStrings::class);
        expect(TransformerRegistry::get('lower'))->toBe(LowercaseStrings::class);
        expect(TransformerRegistry::get('lowercase'))->toBe(LowercaseStrings::class);
        expect(TransformerRegistry::get('upper'))->toBe(UppercaseStrings::class);
        expect(TransformerRegistry::get('uppercase'))->toBe(UppercaseStrings::class);
    });

    it('returns null for unknown alias', function(): void {
        TransformerRegistry::clear();

        expect(TransformerRegistry::get('unknown'))->toBeNull();
        expect(TransformerRegistry::has('unknown'))->toBeFalse();
    });

    it('registers custom transformer', function(): void {
        TransformerRegistry::register(AlternatingCase::class);

        expect(TransformerRegistry::has('alternating'))->toBeTrue();
        expect(TransformerRegistry::has('alt_case'))->toBeTrue();
        expect(TransformerRegistry::has('zigzag'))->toBeTrue();
        expect(TransformerRegistry::get('alternating'))->toBe(AlternatingCase::class);
    });

    it('registers multiple transformers at once', function(): void {
        TransformerRegistry::clear();
        TransformerRegistry::registerMany([
            TrimStrings::class,
            AlternatingCase::class,
        ]);

        expect(TransformerRegistry::has('trim'))->toBeTrue();
        expect(TransformerRegistry::has('alternating'))->toBeTrue();
    });

    it('returns all registered aliases', function(): void {
        TransformerRegistry::clear();

        $all = TransformerRegistry::all();

        expect($all)->toBeArray();
        expect($all)->toHaveKey('trim');
        expect($all)->toHaveKey('lower');
        expect($all)->toHaveKey('upper');
    });

    it('clears all registered aliases and re-initializes on next access', function(): void {
        TransformerRegistry::clear();

        // After clear, built-in transformers are auto-registered on first access
        expect(TransformerRegistry::has('trim'))->toBeTrue();

        // Register a custom transformer
        TransformerRegistry::register(AlternatingCase::class);
        expect(TransformerRegistry::has('alternating'))->toBeTrue();

        // Clear removes all (including custom)
        TransformerRegistry::clear();

        // Built-in transformers are re-registered automatically
        expect(TransformerRegistry::has('trim'))->toBeTrue();

        // But custom transformers are not
        expect(TransformerRegistry::has('alternating'))->toBeFalse();
    });
});

