<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\UppercaseStrings;

/**
 * Tests for FluentDataMapper copy() method.
 *
 * Ensures that copy() creates a true deep copy of all embedded objects,
 * not just references to the original objects.
 *
 * @internal
 */
describe('DataMapper copy()', function(): void {
    it('creates independent copies of exception handlers', function(): void {
        $original = DataMapper::source(['name' => 'Alice'])
            ->target([])
            ->template(['name' => '{{ name }}']);

        $copy = $original->copy();

        // Map with both mappers
        $result1 = $original->map();
        $result2 = $copy->map();

        // Each result should have its own exception handler
        expect($result1->hasExceptions())->toBeFalse();
        expect($result2->hasExceptions())->toBeFalse();

        // They should be independent (not the same object)
        expect($result1)->not->toBe($result2);
    });

    it('creates independent copies of pipeline filters', function(): void {
        $original = DataMapper::source(['name' => 'alice'])
            ->target([])
            ->template(['name' => '{{ name }}'])
            ->trimValues(false); // Disable default trimming

        $copy = $original->copy();

        // Add filter to original AFTER copy
        $original->pipeline([new UppercaseStrings()]);

        // Original should use UppercaseStrings
        $result1 = $original->map();
        expect($result1->toArray())->toBe(['name' => 'ALICE']);

        // Copy should not have the filter
        $result2 = $copy->map();
        expect($result2->toArray())->toBe(['name' => 'alice']);
    });

    it('creates independent copies of property filters', function(): void {
        $original = DataMapper::source(['name' => 'alice'])
            ->target([])
            ->template(['name' => '{{ name }}']);

        $copy = $original->copy();

        // Add filter to original AFTER copy
        $original->property('name')->setFilter(new UppercaseStrings());

        // Original should use UppercaseStrings
        $result1 = $original->map();
        expect($result1->toArray())->toBe(['name' => 'ALICE']);

        // Copy should not have the filter (was copied before filter was added)
        $result2 = $copy->map();
        expect($result2->toArray())->toBe(['name' => 'alice']);
    });

    it('creates independent copies of template', function(): void {
        $original = DataMapper::source(['name' => 'Alice', 'email' => 'alice@example.com'])
            ->target([])
            ->template(['name' => '{{ name }}']);

        $copy = $original->copy();

        // Modify the copy's template
        $copy->template(['email' => '{{ email }}']);

        // Original should still use original template
        $result1 = $original->map();
        expect($result1->toArray())->toBe(['name' => 'Alice']);

        // Copy should use new template
        $result2 = $copy->map();
        expect($result2->toArray())->toBe(['email' => 'alice@example.com']);
    });

    it('creates independent copies that can be modified separately', function(): void {
        $original = DataMapper::source(['name' => '  alice  '])
            ->target([])
            ->template(['name' => '{{ name }}'])
            ->trimValues(true);

        $copy = $original->copy();

        // Modify copy's settings
        $copy->skipNull(false)->trimValues(false);

        // Original should still use original settings (trimValues = true)
        $result1 = $original->map();
        expect($result1->toArray())->toBe(['name' => 'alice']);

        // Copy should use new settings (trimValues = false)
        $result2 = $copy->map();
        expect($result2->toArray())->toBe(['name' => '  alice  ']);
    });

    it('copy() is used by mapMany() to create independent mappers', function(): void {
        $results = DataMapper::source([])
            ->template(['name' => '{{ name }}'])
            ->trimValues(true)
            ->mapMany([
                ['source' => ['name' => '  Alice  '], 'target' => []],
                ['source' => ['name' => '  Bob  '], 'target' => []],
            ]);

        // Each result should be independent
        expect($results)->toHaveCount(2);
        expect($results[0]->toArray())->toBe(['name' => 'Alice']);
        expect($results[1]->toArray())->toBe(['name' => 'Bob']);

        // Each result should have its own exception handler
        expect($results[0]->hasExceptions())->toBeFalse();
        expect($results[1]->hasExceptions())->toBeFalse();
    });
});
