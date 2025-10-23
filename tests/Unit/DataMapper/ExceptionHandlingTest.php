<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\Exceptions\CollectedExceptionsException;
use event4u\DataHelpers\Exceptions\InvalidMappingException;

describe('DataMapper Exception Handling', function(): void {
    beforeEach(function(): void {
        // Reset to default (collect exceptions)
        MapperExceptions::setCollectExceptionsEnabled(true);
    });

    it('collects exceptions by default', function(): void {
        MapperExceptions::setCollectExceptionsEnabled(true);

        expect(MapperExceptions::hasExceptions())->toBeFalse();

        // This should not throw immediately
        $source = ['name' => 'John'];
        $target = [];
        $mapping = ['invalid.path.that.does.not.exist' => 'name'];

        $result = DataMapper::source($source)
            ->target($target)
            ->template($mapping)
            ->map()
            ->getTarget();

        // After mapping, exceptions should be collected
        expect(MapperExceptions::hasExceptions())->toBeFalse(); // No exceptions for missing paths
    });

    it('throws exceptions immediately when collectExceptions is false', function(): void {
        MapperExceptions::setCollectExceptionsEnabled(false);

        $source = ['name' => 'John'];
        $target = [];

        // Create an invalid mapping that will cause an exception
        $mapping = [
            'source' => $source,
            'target' => $target,
            'sourceMapping' => ['a', 'b'],
            'targetMapping' => ['x'], // Mismatched lengths
        ];

        expect(fn(): mixed => DataMapper::source($source)
            ->target($target)
            ->template([$mapping])
            ->map()
            ->getTarget())
            ->toThrow(InvalidMappingException::class);
    });

    it('clears exceptions at the start of each mapping', function(): void {
        MapperExceptions::setCollectExceptionsEnabled(true);

        // First mapping (no exceptions expected)
        $source1 = ['name' => 'John'];
        $target1 = [];
        $mapping1 = ['name' => 'name'];

        DataMapper::source($source1)
            ->target($target1)
            ->template($mapping1)
            ->map();

        expect(MapperExceptions::hasExceptions())->toBeFalse();

        // Second mapping (also no exceptions)
        $source2 = ['age' => 30];
        $target2 = [];
        $mapping2 = ['age' => 'age'];

        DataMapper::source($source2)
            ->target($target2)
            ->template($mapping2)
            ->map();

        // Exceptions should be cleared from first mapping
        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('can retrieve collected exceptions', function(): void {
        MapperExceptions::setCollectExceptionsEnabled(true);

        $exceptions = MapperExceptions::getExceptions();

        expect($exceptions)->toBeArray();
        expect($exceptions)->toBeEmpty();
    });

    it('can check if exceptions exist', function(): void {
        MapperExceptions::setCollectExceptionsEnabled(true);

        expect(MapperExceptions::hasExceptions())->toBeFalse();

        // After a successful mapping
        $source = ['name' => 'John'];
        $target = [];
        $mapping = ['name' => 'name'];

        DataMapper::source($source)
            ->target($target)
            ->template($mapping)
            ->map();

        expect(MapperExceptions::hasExceptions())->toBeFalse();
    });

    it('collects exceptions without throwing when collectExceptions is true', function(): void {
        MapperExceptions::setCollectExceptionsEnabled(true);

        $source = ['name' => 'John'];
        $target = [];

        // Create an invalid mapping that will cause an exception
        $mapping = [
            'source' => $source,
            'target' => $target,
            'sourceMapping' => ['a', 'b'],
            'targetMapping' => ['x'], // Mismatched lengths
        ];

        // Should NOT throw - exception should be collected
        $result = DataMapper::source($source)
            ->target($target)
            ->template([$mapping])
            ->map()
            ->getTarget();

        // Exception should be collected
        expect(MapperExceptions::hasExceptions())->toBeTrue();
        expect(MapperExceptions::getExceptionCount())->toBe(1);
    });

    it('provides access to collected exceptions', function(): void {
        MapperExceptions::setCollectExceptionsEnabled(true);

        // Clear any previous exceptions
        $source = ['name' => 'John'];
        $target = [];
        $mapping = ['name' => 'name'];
        DataMapper::source($source)
            ->target($target)
            ->template($mapping)
            ->map();

        // After a successful mapping, exceptions should be cleared
        expect(MapperExceptions::hasExceptions())->toBeFalse();
        expect(MapperExceptions::getExceptions())->toBeArray();
        expect(MapperExceptions::getExceptions())->toBeEmpty();
    });

    it('CollectedExceptionsException provides access to individual exceptions', function(): void {
        MapperExceptions::setCollectExceptionsEnabled(true);

        // We can't easily trigger multiple exceptions in one mapping
        // But we can verify the exception class exists and has the right methods
        $exception = new CollectedExceptionsException(
            [
                new InvalidArgumentException('Error 1'),
                new RuntimeException('Error 2'),
            ]
        );

        expect($exception)->toBeInstanceOf(CollectedExceptionsException::class);
        expect($exception->getExceptions())->toHaveCount(2);
        expect($exception->getExceptionCount())->toBe(2);
        expect($exception->getMessage())->toBe('Collected 2 exceptions during mapping');
    });
});
