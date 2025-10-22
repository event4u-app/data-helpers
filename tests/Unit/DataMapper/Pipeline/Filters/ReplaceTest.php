<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Replace;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Helper function to create a PairContext for testing.
 *
 * @param array<int, mixed> $extra
 */
function createContext(mixed $source = null, array $extra = []): PairContext
{
    return new PairContext('template', 0, '', '', $source, null, null, $extra);
}

describe('Replace Filter', function(): void {
    it('replaces simple string', function(): void {
        $filter = new Replace('Mr', 'Herr');
        $context = createContext();

        $result = $filter->transform('Hello Mr Smith', $context);
        expect($result)->toBe('Hello Herr Smith');
    });

    it('replaces multiple occurrences', function(): void {
        $filter = new Replace('test', 'demo');
        $context = createContext();

        $result = $filter->transform('test test test', $context);
        expect($result)->toBe('demo demo demo');
    });

    it('replaces with filter syntax (args)', function(): void {
        $filter = new Replace();
        $context = createContext(null, ['Mr', 'Herr']);

        $result = $filter->transform('Hello Mr Smith', $context);
        expect($result)->toBe('Hello Herr Smith');
    });

    it('replaces multiple searches with single replacement', function(): void {
        $filter = new Replace(['Mr', 'Mrs'], 'Person');
        $context = createContext();

        $result = $filter->transform('Mr and Mrs Smith', $context);
        expect($result)->toBe('Person and Person Smith');
    });

    it('replaces multiple searches with multiple replacements', function(): void {
        $filter = new Replace(['Mr', 'Mrs'], ['Herr', 'Frau']);
        $context = createContext();

        $result = $filter->transform('Mr and Mrs Smith', $context);
        expect($result)->toBe('Herr and Frau Smith');
    });

    it('handles case insensitive replacement', function(): void {
        $filter = new Replace('mr', 'Herr', true);
        $context = createContext();

        $result = $filter->transform('Hello Mr Smith', $context);
        expect($result)->toBe('Hello Herr Smith');
    });

    it('returns value unchanged if search is null', function(): void {
        $filter = new Replace();
        $context = createContext();

        $result = $filter->transform('Hello World', $context);
        expect($result)->toBe('Hello World');
    });

    it('returns value unchanged if replacement is null', function(): void {
        $filter = new Replace('test', null);
        $context = createContext();

        $result = $filter->transform('Hello test', $context);
        expect($result)->toBe('Hello test');
    });

    it('returns non-string values unchanged', function(): void {
        $filter = new Replace('test', 'demo');
        $context = createContext();

        expect($filter->transform(123, $context))->toBe(123);
        expect($filter->transform(['test'], $context))->toBe(['test']);
        expect($filter->transform(null, $context))->toBeNull();
    });

    it('has correct aliases', function(): void {
        $filter = new Replace();
        expect($filter->getAliases())->toBe(['replace']);
    });

    it('uses beforeTransform hook', function(): void {
        $filter = new Replace();
        expect($filter->getHook())->toBe(DataMapperHook::BeforeTransform->value);
    });
});
