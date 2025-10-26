<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\Sum;
use event4u\DataHelpers\Enums\DataMapperHook;

/**
 * Helper function to create a PairContext for testing.
 */
function createSumContext(): PairContext
{
    return new PairContext('template', 0, '', '', null, null, null, []);
}

describe('Sum Filter', function(): void {
    beforeEach(function(): void {
        $this->filter = new Sum();
        $this->context = createSumContext();
    });

    it('sums numeric values in an array', function(): void {
        $result = $this->filter->transform([1, 2, 3, 4, 5], $this->context);
        expect($result)->toBe(15);
    });

    it('sums float values', function(): void {
        $result = $this->filter->transform([1.5, 2.5, 3.0], $this->context);
        expect($result)->toBe(7.0);
    });

    it('sums mixed numeric values', function(): void {
        $result = $this->filter->transform([1, 2.5, 3, 4.5], $this->context);
        expect($result)->toBe(11.0);
    });

    it('ignores non-numeric values', function(): void {
        $result = $this->filter->transform([1, 'two', 3, null, 4], $this->context);
        expect($result)->toBe(8);
    });

    it('returns 0 for empty array', function(): void {
        $result = $this->filter->transform([], $this->context);
        expect($result)->toBe(0);
    });

    it('returns 0 for non-array values', function(): void {
        expect($this->filter->transform('not an array', $this->context))->toBe(0);
        expect($this->filter->transform(123, $this->context))->toBe(0);
        expect($this->filter->transform(null, $this->context))->toBe(0);
    });

    it('handles numeric strings', function(): void {
        $result = $this->filter->transform(['10', '20', '30'], $this->context);
        expect($result)->toBe(60);
    });

    it('has correct aliases', function(): void {
        $aliases = $this->filter->getAliases();
        expect($aliases)->toBe(['sum', 'total']);
    });

    it('has correct hook', function(): void {
        expect($this->filter->getHook())->toBe(DataMapperHook::BeforeTransform->value);
    });

    it('has no filter', function(): void {
        expect($this->filter->getFilter())->toBeNull();
    });
});
