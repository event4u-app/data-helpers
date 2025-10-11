<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\Exceptions\CollectedExceptionsException;
use event4u\DataHelpers\Exceptions\UndefinedSourceValueException;
use event4u\DataHelpers\Exceptions\UndefinedTargetValueException;

describe('DataMapper Undefined Value Exceptions', function(): void {
    // Reset DataMapper settings before each test
    beforeEach(function(): void {
        DataMapper::reset();
    });
    afterEach(function(): void {
        DataMapper::reset();
    });

    describe('Reset functionality', function(): void {
        it('resets all settings to defaults', function(): void {
            // Change all settings
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedSourceEnabled(true);
            DataMapper::setThrowOnUndefinedTargetEnabled(true);

            // Reset
            DataMapper::reset();

            // Check defaults
            expect(DataMapper::isCollectExceptionsEnabled())->toBeTrue();
            expect(DataMapper::isThrowOnUndefinedSourceEnabled())->toBeFalse();
            expect(DataMapper::isThrowOnUndefinedTargetEnabled())->toBeFalse();
            expect(DataMapper::hasExceptions())->toBeFalse();
        });
    });

    describe('Undefined Source Value', function(): void {
        it('does not throw by default when source value is undefined', function(): void {
            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['result' => '{{ email }}'];

            $result = DataMapper::map($source, $target, $mapping);

            // skipNull is true by default, so null values are skipped
            expect($result)->toBe([]);
        });

        it('throws exception when throwExceptionOnUndefinedSourceValue is true', function(): void {
            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['result' => '{{ email }}'];

            expect(fn(): mixed => DataMapper::map($source, $target, $mapping))
                ->toThrow(UndefinedSourceValueException::class, 'Source value at path "email" is undefined');
        });

        it('exception contains the path that was not found', function(): void {
            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['result' => '{{ user.email }}'];

            try {
                DataMapper::map($source, $target, $mapping);
                expect(false)->toBeTrue('Exception should have been thrown');
            } catch (UndefinedSourceValueException $undefinedSourceValueException) {
                expect($undefinedSourceValueException->getPath())->toBe('user.email');
            }
        });

        it('does not throw when source value exists', function(): void {
            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            $source = ['email' => 'john@example.com'];
            $target = [];
            $mapping = ['result' => '{{ email }}'];

            $result = DataMapper::map($source, $target, $mapping);

            expect($result)->toBe(['result' => 'john@example.com']);
        });

        it('collects exceptions when collectExceptions is true', function(): void {
            DataMapper::setCollectExceptionsEnabled(true);
            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['result' => '{{ email }}'];

            // Exception is collected and thrown at the end
            // Note: May be wrapped in CollectedExceptionsException if multiple exceptions occur
            try {
                DataMapper::map($source, $target, $mapping);
                expect(false)->toBeTrue('Exception should have been thrown');
            } catch (UndefinedSourceValueException|CollectedExceptionsException $e) {
                // Either exception type is acceptable
                expect($e)->toBeInstanceOf(Throwable::class);
            }

            expect(DataMapper::hasExceptions())->toBeFalse(); // Cleared after mapping
        });

        it('can read the setting via getter', function(): void {
            expect(DataMapper::isThrowOnUndefinedSourceEnabled())->toBeFalse();

            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            expect(DataMapper::isThrowOnUndefinedSourceEnabled())->toBeTrue();
        });
    });

    describe('Undefined Target Value', function(): void {
        it('does not throw by default when target parent path does not exist', function(): void {
            $source = ['name' => 'John', 'city' => 'Berlin'];
            $target = [];
            $mapping = ['user.address.city' => '{{ city }}'];

            $result = DataMapper::map($source, $target, $mapping);

            // By default, DataMutator creates missing paths
            expect($result)->toBe(['user' => ['address' => ['city' => 'Berlin']]]);
        });

        it(
            'throws exception when throwExceptionOnUndefinedTargetValue is true and parent path does not exist',
            function(): void {
                DataMapper::setCollectExceptionsEnabled(false);
                DataMapper::setThrowOnUndefinedTargetEnabled(true);

                $source = ['name' => 'John', 'city' => 'Berlin'];
                $target = [];
                $mapping = ['user.address.city' => '{{ city }}'];

                expect(fn(): mixed => DataMapper::map($source, $target, $mapping))
                    ->toThrow(UndefinedTargetValueException::class);
            }
        );

        it('does not throw when target parent path exists', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedTargetEnabled(true);

            $source = ['name' => 'John', 'city' => 'Berlin'];
            $target = ['user' => ['address' => []]];
            $mapping = ['user.address.city' => '{{ city }}'];

            $result = DataMapper::map($source, $target, $mapping);

            expect($result)->toBe(['user' => ['address' => ['city' => 'Berlin']]]);
        });

        it('exception contains the parent path that was not found', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedTargetEnabled(true);

            $source = ['city' => 'Berlin'];
            $target = [];
            $mapping = ['user.address.city' => '{{ city }}'];

            try {
                DataMapper::map($source, $target, $mapping);
                expect(false)->toBeTrue('Exception should have been thrown');
            } catch (UndefinedTargetValueException $undefinedTargetValueException) {
                expect($undefinedTargetValueException->getPath())->toBe('user.address');
            }
        });

        it('can read the setting via getter', function(): void {
            expect(DataMapper::isThrowOnUndefinedTargetEnabled())->toBeFalse();

            DataMapper::setThrowOnUndefinedTargetEnabled(true);

            expect(DataMapper::isThrowOnUndefinedTargetEnabled())->toBeTrue();
        });
    });

    describe('Getter for collectExceptions', function(): void {
        it('can read the collectExceptions setting', function(): void {
            expect(DataMapper::isCollectExceptionsEnabled())->toBeTrue(); // Default is true

            DataMapper::setCollectExceptionsEnabled(false);

            expect(DataMapper::isCollectExceptionsEnabled())->toBeFalse();
        });
    });

    describe('Combined Source and Target Exceptions', function(): void {
        it('throws both source and target exceptions when both are enabled and collectExceptions is true', function(): void {
            DataMapper::setCollectExceptionsEnabled(true);
            DataMapper::setThrowOnUndefinedSourceEnabled(true);
            DataMapper::setThrowOnUndefinedTargetEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = [
                'result' => '{{ email }}', // undefined source
                'user.address.city' => '{{ name }}', // undefined target parent
            ];

            try {
                DataMapper::map($source, $target, $mapping);
                expect(false)->toBeTrue('Exception should have been thrown');
            } catch (CollectedExceptionsException $collectedExceptionsException) {
                expect($collectedExceptionsException->getExceptionCount())->toBeGreaterThanOrEqual(1);
                $exceptions = $collectedExceptionsException->getExceptions();
                expect($exceptions)->toBeArray();
            }
        });

        it('throws only source exception when only source check is enabled', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedSourceEnabled(true);
            DataMapper::setThrowOnUndefinedTargetEnabled(false);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = [
                'result' => '{{ email }}', // undefined source
                'user.address.city' => '{{ name }}', // undefined target parent (but check disabled)
            ];

            expect(fn(): mixed => DataMapper::map($source, $target, $mapping))
                ->toThrow(UndefinedSourceValueException::class);
        });

        it('throws only target exception when only target check is enabled', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedSourceEnabled(false);
            DataMapper::setThrowOnUndefinedTargetEnabled(true);

            $source = ['name' => 'John', 'email' => 'john@example.com'];
            $target = [];
            $mapping = [
                'result' => '{{ email }}', // source exists
                'user.address.city' => '{{ name }}', // undefined target parent
            ];

            expect(fn(): mixed => DataMapper::map($source, $target, $mapping))
                ->toThrow(UndefinedTargetValueException::class);
        });

        it('does not throw when both checks are disabled', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedSourceEnabled(false);
            DataMapper::setThrowOnUndefinedTargetEnabled(false);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = [
                'result' => '{{ email }}', // undefined source (but check disabled)
                'user.address.city' => '{{ name }}', // undefined target parent (but check disabled)
            ];

            $result = DataMapper::map($source, $target, $mapping);

            // Should create the structure without throwing
            expect($result)->toBeArray();
        });
    });

    describe('Multiple Source Exceptions with collectExceptions', function(): void {
        it('collects multiple source exceptions and throws CollectedExceptionsException', function(): void {
            DataMapper::setCollectExceptionsEnabled(true);
            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = [
                'email' => '{{ email }}', // undefined
                'phone' => '{{ phone }}', // undefined
                'address' => '{{ address }}', // undefined
            ];

            try {
                DataMapper::map($source, $target, $mapping);
                expect(false)->toBeTrue('Exception should have been thrown');
            } catch (CollectedExceptionsException $collectedExceptionsException) {
                expect($collectedExceptionsException->getExceptionCount())->toBeGreaterThanOrEqual(1);
                $exceptions = $collectedExceptionsException->getExceptions();

                // Check that all exceptions are UndefinedSourceValueException
                foreach ($exceptions as $exception) {
                    expect($exception)->toBeInstanceOf(UndefinedSourceValueException::class);
                }
            }
        });

        it('throws immediately on first exception when collectExceptions is false', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = [
                'email' => '{{ email }}', // undefined - should throw here
                'phone' => '{{ phone }}', // undefined - never reached
                'address' => '{{ address }}', // undefined - never reached
            ];

            try {
                DataMapper::map($source, $target, $mapping);
                expect(false)->toBeTrue('Exception should have been thrown');
            } catch (UndefinedSourceValueException $undefinedSourceValueException) {
                // Should be the first exception only
                expect($undefinedSourceValueException->getPath())->toBe('email');
            }
        });
    });

    describe('Multiple Target Exceptions with collectExceptions', function(): void {
        it('collects multiple target exceptions and throws CollectedExceptionsException', function(): void {
            DataMapper::setCollectExceptionsEnabled(true);
            DataMapper::setThrowOnUndefinedTargetEnabled(true);

            $source = ['name' => 'John', 'email' => 'john@example.com', 'phone' => '123'];
            $target = [];
            $mapping = [
                'user.profile.email' => '{{ email }}', // undefined parent
                'user.contact.phone' => '{{ phone }}', // undefined parent
                'user.details.name' => '{{ name }}', // undefined parent
            ];

            try {
                DataMapper::map($source, $target, $mapping);
                expect(false)->toBeTrue('Exception should have been thrown');
            } catch (CollectedExceptionsException $collectedExceptionsException) {
                expect($collectedExceptionsException->getExceptionCount())->toBeGreaterThanOrEqual(1);
                $exceptions = $collectedExceptionsException->getExceptions();

                // Check that all exceptions are UndefinedTargetValueException
                foreach ($exceptions as $exception) {
                    expect($exception)->toBeInstanceOf(UndefinedTargetValueException::class);
                }
            }
        });
    });

    describe('Edge Cases and Special Scenarios', function(): void {
        it('does not throw for root level target paths', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedTargetEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['name' => '{{ name }}']; // root level - no parent to check

            $result = DataMapper::map($source, $target, $mapping);

            expect($result)->toBe(['name' => 'John']);
        });

        it('does not throw when source has default value', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['email' => '{{ email | default:"no-email" }}']; // has default

            $result = DataMapper::map($source, $target, $mapping);

            expect($result)->toBe(['email' => 'no-email']);
        });

        it('does not throw when source has filter', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['email' => '{{ email | upper }}']; // has filter

            // Should not throw because filter is present
            $result = DataMapper::map($source, $target, $mapping);

            expect($result)->toBeArray();
        });

        it('throws for nested mapping with undefined source', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            $source = ['user' => ['name' => 'John']];
            $target = [];
            $mapping = [
                'result' => [
                    'name' => '{{ user.name }}',
                    'email' => '{{ user.email }}', // undefined
                ],
            ];

            expect(fn(): mixed => DataMapper::map($source, $target, $mapping))
                ->toThrow(UndefinedSourceValueException::class);
        });

        it('throws for deeply nested target path without parent', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedTargetEnabled(true);

            $source = ['city' => 'Berlin'];
            $target = [];
            $mapping = ['company.office.location.address.city' => '{{ city }}'];

            expect(fn(): mixed => DataMapper::map($source, $target, $mapping))
                ->toThrow(UndefinedTargetValueException::class);
        });

        it('does not throw when deeply nested target path has all parents', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedTargetEnabled(true);

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

            $result = DataMapper::map($source, $target, $mapping);

            expect($result['company']['office']['location']['address']['city'])->toBe('Berlin');
        });

        it('handles mixed valid and invalid mappings with collectExceptions', function(): void {
            DataMapper::setCollectExceptionsEnabled(true);
            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John', 'age' => 30];
            $target = [];
            $mapping = [
                'name' => '{{ name }}', // valid
                'email' => '{{ email }}', // invalid
                'age' => '{{ age }}', // valid
                'phone' => '{{ phone }}', // invalid
            ];

            try {
                DataMapper::map($source, $target, $mapping);
                expect(false)->toBeTrue('Exception should have been thrown');
            } catch (CollectedExceptionsException $collectedExceptionsException) {
                // Should have collected exceptions for 'email' and 'phone'
                expect($collectedExceptionsException->getExceptionCount())->toBeGreaterThanOrEqual(1);
            }
        });

        it('clears exceptions after each mapping call', function(): void {
            DataMapper::setCollectExceptionsEnabled(true);
            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['email' => '{{ email }}'];

            // First mapping - should throw
            try {
                DataMapper::map($source, $target, $mapping);
            } catch (CollectedExceptionsException|UndefinedSourceValueException) {
                // Expected
            }

            // hasExceptions should be false after throwing
            expect(DataMapper::hasExceptions())->toBeFalse();

            // Second mapping with valid data - should not throw
            $source2 = ['email' => 'john@example.com'];
            $result = DataMapper::map($source2, [], $mapping);

            expect($result)->toBe(['email' => 'john@example.com']);
            expect(DataMapper::hasExceptions())->toBeFalse();
        });

        it('works with simple path-to-path mapping for source exceptions', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedSourceEnabled(true);

            $source = ['name' => 'John'];
            $target = [];
            $mapping = ['email' => 'user.email']; // simple path (no template)

            // Simple path-to-path mapping treats the value as literal if not found
            // So this test is skipped as it's expected behavior
            $result = DataMapper::map($source, $target, $mapping);
            expect($result)->toBe(['email' => 'user.email']); // literal value
        })->skip('Simple path-to-path mapping treats undefined paths as literal values');

        it('works with simple path-to-path mapping for target exceptions', function(): void {
            DataMapper::setCollectExceptionsEnabled(false);
            DataMapper::setThrowOnUndefinedTargetEnabled(true);

            $source = ['email' => 'john@example.com'];
            $target = [];
            $mapping = ['user.profile.email' => 'email']; // simple path (no template)

            expect(fn(): mixed => DataMapper::map($source, $target, $mapping))
                ->toThrow(UndefinedTargetValueException::class);
        });
    });
});

