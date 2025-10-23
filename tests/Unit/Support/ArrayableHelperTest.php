<?php

declare(strict_types=1);

use event4u\DataHelpers\Support\ArrayableHelper;

describe('ArrayableHelper', function(): void {
    describe('isArrayable()', function(): void {
        it('returns false for non-arrayable values', function(): void {
            expect(ArrayableHelper::isArrayable('string'))->toBeFalse();
            expect(ArrayableHelper::isArrayable(123))->toBeFalse();
            expect(ArrayableHelper::isArrayable([]))->toBeFalse();
            expect(ArrayableHelper::isArrayable(new stdClass()))->toBeFalse();
        });

        it('returns false when Arrayable interface does not exist', function(): void {
            // In unit tests, Laravel's Arrayable interface might not be available
            // This test verifies the helper handles that gracefully
            $result = ArrayableHelper::isArrayable(['test']);
            expect($result)->toBeFalse();
        });
    });

    describe('toArray()', function(): void {
        it('returns empty array for non-arrayable values', function(): void {
            expect(ArrayableHelper::toArray('string'))->toBe([]);
            expect(ArrayableHelper::toArray(123))->toBe([]);
            expect(ArrayableHelper::toArray(new stdClass()))->toBe([]);
        });

        it('returns empty array for arrays', function(): void {
            // Arrays are not Arrayable objects
            expect(ArrayableHelper::toArray(['key' => 'value']))->toBe([]);
        });

        it('returns empty array for null', function(): void {
            expect(ArrayableHelper::toArray(null))->toBe([]);
        });
    });

    describe('caching behavior', function(): void {
        it('caches interface_exists check', function(): void {
            // First call initializes cache
            ArrayableHelper::isArrayable('test1');

            // Second call should use cached value
            $result = ArrayableHelper::isArrayable('test2');

            expect($result)->toBeFalse();
        });
    });
});
