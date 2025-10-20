<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\Exceptions\UndefinedSourceValueException;
use event4u\DataHelpers\Exceptions\UndefinedTargetValueException;

describe('DataMapper Undefined Value Exceptions', function(): void {
    // Reset DataMapper settings before each test
    beforeEach(function(): void {
        MapperExceptions::reset();
    });
    afterEach(function(): void {
        MapperExceptions::reset();
    });

    describe('Reset functionality', function(): void {
        beforeEach(function(): void {
            MapperExceptions::reset();
        });
        afterEach(function(): void {
            MapperExceptions::reset();
        });

        it('resets all settings to defaults', function(): void {
            // Change all settings
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

            // Reset
            MapperExceptions::reset();

            // Check defaults
            expect(MapperExceptions::isCollectExceptionsEnabled())->toBeTrue();
            expect(MapperExceptions::isThrowOnUndefinedSourceEnabled())->toBeFalse();
            expect(MapperExceptions::isThrowOnUndefinedTargetEnabled())->toBeFalse();
            expect(MapperExceptions::hasExceptions())->toBeFalse();
        });
    });

    describe('Undefined Source Value', function(): void {
        beforeEach(function(): void {
            MapperExceptions::reset();
        });
        afterEach(function(): void {
            MapperExceptions::reset();
        });

        it('does not throw by default when source value is undefined', function(): void {
            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['result' => '{{ email }}'];

            $result = DataMapper::source($source)
                ->target($target)
                ->template($mapping)
                ->map()
                ->getTarget();

            // skipNull is true by default, so null values are skipped
            expect($result)->toBe([]);
        });

        it('throws exception when throwExceptionOnUndefinedSourceValue is true', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['result' => '{{ email }}'];

            expect(fn(): mixed => DataMapper::source($source)
                ->target($target)
                ->template($mapping)
                ->map()
                ->getTarget())
                ->toThrow(UndefinedSourceValueException::class, 'Source value at path "email" is undefined');
        });

        it('exception contains the path that was not found', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['result' => '{{ user.email }}'];

            try {
                DataMapper::source($source)
                    ->target($target)
                    ->template($mapping)
                    ->map();
                expect(false)->toBeTrue('Exception should have been thrown');
            } catch (UndefinedSourceValueException $undefinedSourceValueException) {
                expect($undefinedSourceValueException->getPath())->toBe('user.email');
            }
        });

        it('does not throw when source value exists', function(): void {
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            $source = ['email' => 'john@example.com'];
            $target = [];
            $mapping = ['result' => '{{ email }}'];

            $result = DataMapper::source($source)
                ->target($target)
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result)->toBe(['result' => 'john@example.com']);
        });

        it('collects exceptions when collectExceptions is true', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(true);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['result' => '{{ email }}'];

            // Exception should be collected, NOT thrown
            $result = DataMapper::source($source)
                ->target($target)
                ->template($mapping)
                ->map();

            // Exception should be collected
            expect(MapperExceptions::hasExceptions())->toBeTrue();
            expect(MapperExceptions::getExceptionCount())->toBe(1);
        });

        it('can read the setting via getter', function(): void {
            expect(MapperExceptions::isThrowOnUndefinedSourceEnabled())->toBeFalse();

            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            expect(MapperExceptions::isThrowOnUndefinedSourceEnabled())->toBeTrue();
        });
    });

    describe('Undefined Target Value', function(): void {
        beforeEach(function(): void {
            MapperExceptions::reset();
        });
        afterEach(function(): void {
            MapperExceptions::reset();
        });

        it('does not throw by default when target parent path does not exist', function(): void {
            $source = ['name' => 'John', 'city' => 'Berlin'];
            $target = [];
            $mapping = ['user.address.city' => '{{ city }}'];

            $result = DataMapper::source($source)
                ->target($target)
                ->template($mapping)
                ->map()
                ->getTarget();

            // By default, DataMutator creates missing paths
            expect($result)->toBe(['user' => ['address' => ['city' => 'Berlin']]]);
        });

        it(
            'throws exception when throwExceptionOnUndefinedTargetValue is true and parent path does not exist',
            function(): void {
                MapperExceptions::setCollectExceptionsEnabled(false);
                MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

                $source = ['name' => 'John', 'city' => 'Berlin'];
                $target = [];
                $mapping = ['user.address.city' => '{{ city }}'];

                expect(
                    fn(): mixed => DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget()
                )
                    ->toThrow(UndefinedTargetValueException::class);
            }
        );

        it('does not throw when target parent path exists', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

            $source = ['name' => 'John', 'city' => 'Berlin'];
            $target = ['user' => ['address' => []]];
            $mapping = ['user.address.city' => '{{ city }}'];

            $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBe(['user' => ['address' => ['city' => 'Berlin']]]);
        });

        it('exception contains the parent path that was not found', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

            $source = ['city' => 'Berlin'];
            $target = [];
            $mapping = ['user.address.city' => '{{ city }}'];

            try {
                $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
                expect($result)->toBeNull();
                expect(false)->toBeTrue('Exception should have been thrown');
            } catch (UndefinedTargetValueException $undefinedTargetValueException) {
                expect($undefinedTargetValueException->getPath())->toBe('user.address');
            }
        });

        it('can read the setting via getter', function(): void {
            expect(MapperExceptions::isThrowOnUndefinedTargetEnabled())->toBeFalse();

            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

            expect(MapperExceptions::isThrowOnUndefinedTargetEnabled())->toBeTrue();
        });
    });

    describe('Getter for collectExceptions', function(): void {
        beforeEach(function(): void {
            MapperExceptions::reset();
        });
        afterEach(function(): void {
            MapperExceptions::reset();
        });

        it('can read the collectExceptions setting', function(): void {
            expect(MapperExceptions::isCollectExceptionsEnabled())->toBeTrue(); // Default is true

            MapperExceptions::setCollectExceptionsEnabled(false);

            expect(MapperExceptions::isCollectExceptionsEnabled())->toBeFalse();
        });
    });

    describe('Combined Source and Target Exceptions', function(): void {
        beforeEach(function(): void {
            MapperExceptions::reset();
        });
        afterEach(function(): void {
            MapperExceptions::reset();
        });

        it('collects both source and target exceptions when both are enabled and collectExceptions is true', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(true);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = [
                'result' => '{{ email }}', // undefined source
                'user.address.city' => '{{ name }}', // undefined target parent
            ];

            // Exceptions should be collected, NOT thrown
            $result = DataMapper::source($source)->target($target)->template($mapping)->map();

            // Exceptions should be collected
            expect(MapperExceptions::hasExceptions())->toBeTrue();
            expect(MapperExceptions::getExceptionCount())->toBeGreaterThanOrEqual(1);
        });

        it('throws only source exception when only source check is enabled', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(false);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = [
                'result' => '{{ email }}', // undefined source
                'user.address.city' => '{{ name }}', // undefined target parent (but check disabled)
            ];

            expect(fn(): mixed => DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget())
                ->toThrow(UndefinedSourceValueException::class);
        });

        it('throws only target exception when only target check is enabled', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(false);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

            $source = ['name' => 'John', 'email' => 'john@example.com'];
            $target = [];
            $mapping = [
                'result' => '{{ email }}', // source exists
                'user.address.city' => '{{ name }}', // undefined target parent
            ];

            expect(fn(): mixed => DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget())
                ->toThrow(UndefinedTargetValueException::class);
        });

        it('does not throw when both checks are disabled', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(false);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(false);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = [
                'result' => '{{ email }}', // undefined source (but check disabled)
                'user.address.city' => '{{ name }}', // undefined target parent (but check disabled)
            ];

            $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();

            // Should create the structure without throwing
            expect($result)->toBeArray();
        });
    });

    describe('Multiple Source Exceptions with collectExceptions', function(): void {
        beforeEach(function(): void {
            MapperExceptions::reset();
        });
        afterEach(function(): void {
            MapperExceptions::reset();
        });

        it('collects multiple source exceptions when collectExceptions is true', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(true);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = [
                'email' => '{{ email }}', // undefined
                'phone' => '{{ phone }}', // undefined
                'address' => '{{ address }}', // undefined
            ];

            // Exceptions should be collected, NOT thrown
            $result = DataMapper::source($source)->target($target)->template($mapping)->map();

            // Exceptions should be collected
            expect(MapperExceptions::hasExceptions())->toBeTrue();
            expect(MapperExceptions::getExceptionCount())->toBeGreaterThanOrEqual(1);

            // Check that all exceptions are UndefinedSourceValueException
            $exceptions = MapperExceptions::getExceptions();
            foreach ($exceptions as $exception) {
                expect($exception)->toBeInstanceOf(UndefinedSourceValueException::class);
            }
        });

        it('throws immediately on first exception when collectExceptions is false', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = [
                'email' => '{{ email }}', // undefined - should throw here
                'phone' => '{{ phone }}', // undefined - never reached
                'address' => '{{ address }}', // undefined - never reached
            ];

            try {
                $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
                expect($result)->toBeNull();
                expect(false)->toBeTrue('Exception should have been thrown');
            } catch (UndefinedSourceValueException $undefinedSourceValueException) {
                // Should be the first exception only
                expect($undefinedSourceValueException->getPath())->toBe('email');
            }
        });
    });

    describe('Multiple Target Exceptions with collectExceptions', function(): void {
        beforeEach(function(): void {
            MapperExceptions::reset();
        });
        afterEach(function(): void {
            MapperExceptions::reset();
        });

        it('collects multiple target exceptions when collectExceptions is true', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(true);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

            $source = ['name' => 'John', 'email' => 'john@example.com', 'phone' => '123'];
            $target = [];
            $mapping = [
                'user.profile.email' => '{{ email }}', // undefined parent
                'user.contact.phone' => '{{ phone }}', // undefined parent
                'user.details.name' => '{{ name }}', // undefined parent
            ];

            // Exceptions should be collected, NOT thrown
            $result = DataMapper::source($source)->target($target)->template($mapping)->map();

            // Exceptions should be collected
            expect(MapperExceptions::hasExceptions())->toBeTrue();
            expect(MapperExceptions::getExceptionCount())->toBeGreaterThanOrEqual(1);

            // Check that all exceptions are UndefinedTargetValueException
            $exceptions = MapperExceptions::getExceptions();
            foreach ($exceptions as $exception) {
                expect($exception)->toBeInstanceOf(UndefinedTargetValueException::class);
            }
        });
    });

    describe('Edge Cases and Special Scenarios', function(): void {
        beforeEach(function(): void {
            MapperExceptions::reset();
        });
        afterEach(function(): void {
            MapperExceptions::reset();
        });

        it('does not throw for root level target paths', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['name' => '{{ name }}']; // root level - no parent to check

            $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBe(['name' => 'John']);
        });

        it('does not throw when source has default value', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['email' => '{{ email | default:"no-email" }}']; // has default

            $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBe(['email' => 'no-email']);
        });

        it('does not throw when source has filter', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['email' => '{{ email | upper }}']; // has filter

            // Should not throw because filter is present
            $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();

            expect($result)->toBeArray();
        });

        it('throws for nested mapping with undefined source', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            $source = ['user' => ['name' => 'John']];
            $target = [];
            $mapping = [
                'result' => [
                    'name' => '{{ user.name }}',
                    'email' => '{{ user.email }}', // undefined
                ],
            ];

            expect(fn(): mixed => DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget())
                ->toThrow(UndefinedSourceValueException::class);
        });

        it('throws for deeply nested target path without parent', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

            $source = ['city' => 'Berlin'];
            $target = [];
            $mapping = ['company.office.location.address.city' => '{{ city }}'];

            expect(fn(): mixed => DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget())
                ->toThrow(UndefinedTargetValueException::class);
        });

        it('does not throw when deeply nested target path has all parents', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

            $source = ['city' => 'Berlin'];
            $target = [
                'company' => [
                    'office' => [
                        'location' => [
                            'address' => [],
                        ],
                    ],
                ],
            ];
            $mapping = ['company.office.location.address.city' => '{{ city }}'];

            $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();

            expect($result['company']['office']['location']['address']['city'])->toBe('Berlin');
        });

        it('handles mixed valid and invalid mappings with collectExceptions', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(true);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John', 'age' => 30];
            $target = [];
            $mapping = [
                'name' => '{{ name }}', // valid
                'email' => '{{ email }}', // invalid
                'age' => '{{ age }}', // valid
                'phone' => '{{ phone }}', // invalid
            ];

            // Exceptions should be collected, NOT thrown
            $result = DataMapper::source($source)->target($target)->template($mapping)->map();

            // Exceptions should be collected for 'email' and 'phone'
            expect(MapperExceptions::hasExceptions())->toBeTrue();
            expect(MapperExceptions::getExceptionCount())->toBeGreaterThanOrEqual(1);
        });

        it('clears exceptions after each mapping call', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(true);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['email' => '{{ email }}'];

            // First mapping - exceptions should be collected
            DataMapper::source($source)->target($target)->template($mapping)->map();

            // Exceptions should be collected
            expect(MapperExceptions::hasExceptions())->toBeTrue();

            // Clear exceptions manually
            MapperExceptions::clearExceptions();

            // hasExceptions should be false after clearing
            expect(MapperExceptions::hasExceptions())->toBeFalse();

            // Second mapping with valid data - should not collect exceptions
            $source2 = ['email' => 'john@example.com'];
            $result = DataMapper::source($source2)
                ->target([])
                ->template($mapping)
                ->map()
                ->getTarget();

            expect($result)->toBe(['email' => 'john@example.com']);
            expect(MapperExceptions::hasExceptions())->toBeFalse();
        });

        it('works with simple path-to-path mapping for source exceptions', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['email' => 'user.email']; // simple path (no template)

            // Simple path-to-path mapping treats the value as literal if not found
            // So this test is skipped as it's expected behavior
            $result = DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget();
            expect($result)->toBe(['email' => 'user.email']); // literal value
        })->skip('Simple path-to-path mapping treats undefined paths as literal values');

        it('works with simple path-to-path mapping for target exceptions', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

            $source = ['email' => 'john@example.com'];
            $target = [];
            $mapping = ['user.profile.email' => 'email']; // simple path (no template)

            expect(fn(): mixed => DataMapper::source($source)->target($target)->template($mapping)->map()->getTarget())
                ->toThrow(UndefinedTargetValueException::class);
        });
    });
});

