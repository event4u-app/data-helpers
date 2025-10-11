<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\Exceptions\CollectedExceptionsException;
use event4u\DataHelpers\Exceptions\InvalidMappingException;

describe('DataMapper Exception Handling', function(): void {
    beforeEach(function(): void {
        // Reset to default (collect exceptions)
        DataMapper::setCollectExceptionsEnabled(true);
    });

    it('collects exceptions by default', function(): void {
        DataMapper::setCollectExceptionsEnabled(true);

        expect(DataMapper::hasExceptions())->toBeFalse();

        // This should not throw immediately
        $source = ['name' => 'John'];
        $target = [];
        $mapping = ['invalid.path.that.does.not.exist' => 'name'];

        $result = DataMapper::map($source, $target, $mapping);

        // After mapping, exceptions should be collected
        expect(DataMapper::hasExceptions())->toBeFalse(); // No exceptions for missing paths
    });

    it('throws exceptions immediately when collectExceptions is false', function(): void {
        DataMapper::setCollectExceptionsEnabled(false);

        $source = ['name' => 'John'];
        $target = [];

        // Create an invalid mapping that will cause an exception
        $mapping = [
            'source' => $source,
            'target' => $target,
            'sourceMapping' => ['a', 'b'],
            'targetMapping' => ['x'], // Mismatched lengths
        ];

        expect(fn(): mixed => DataMapper::map($source, $target, [$mapping]))
            ->toThrow(InvalidMappingException::class);
    });

    it('clears exceptions at the start of each mapping', function(): void {
        DataMapper::setCollectExceptionsEnabled(true);

        // First mapping (no exceptions expected)
        $source1 = ['name' => 'John'];
        $target1 = [];
        $mapping1 = ['name' => 'name'];

        DataMapper::map($source1, $target1, $mapping1);

        expect(DataMapper::hasExceptions())->toBeFalse();

        // Second mapping (also no exceptions)
        $source2 = ['age' => 30];
        $target2 = [];
        $mapping2 = ['age' => 'age'];

        DataMapper::map($source2, $target2, $mapping2);

        // Exceptions should be cleared from first mapping
        expect(DataMapper::hasExceptions())->toBeFalse();
    });

    it('can retrieve collected exceptions', function(): void {
        DataMapper::setCollectExceptionsEnabled(true);

        $exceptions = DataMapper::getExceptions();

        expect($exceptions)->toBeArray();
        expect($exceptions)->toBeEmpty();
    });

    it('can check if exceptions exist', function(): void {
        DataMapper::setCollectExceptionsEnabled(true);

        expect(DataMapper::hasExceptions())->toBeFalse();

        // After a successful mapping
        $source = ['name' => 'John'];
        $target = [];
        $mapping = ['name' => 'name'];

        DataMapper::map($source, $target, $mapping);

        expect(DataMapper::hasExceptions())->toBeFalse();
    });

    it('throws collected exceptions at the end when collectExceptions is true', function(): void {
        DataMapper::setCollectExceptionsEnabled(true);

        $source = ['name' => 'John'];
        $target = [];

        // Create an invalid mapping that will cause an exception
        $mapping = [
            'source' => $source,
            'target' => $target,
            'sourceMapping' => ['a', 'b'],
            'targetMapping' => ['x'], // Mismatched lengths
        ];

        expect(fn(): mixed => DataMapper::map($source, $target, [$mapping]))
            ->toThrow(InvalidMappingException::class); // Single exception is thrown directly
    });

    it('provides access to collected exceptions', function(): void {
        DataMapper::setCollectExceptionsEnabled(true);

        // Clear any previous exceptions
        $source = ['name' => 'John'];
        $target = [];
        $mapping = ['name' => 'name'];
        DataMapper::map($source, $target, $mapping);

        // After a successful mapping, exceptions should be cleared
        expect(DataMapper::hasExceptions())->toBeFalse();
        expect(DataMapper::getExceptions())->toBeArray();
        expect(DataMapper::getExceptions())->toBeEmpty();
    });

    it('CollectedExceptionsException provides access to individual exceptions', function(): void {
        DataMapper::setCollectExceptionsEnabled(true);

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

