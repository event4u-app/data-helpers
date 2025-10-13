<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\Exceptions\CollectedExceptionsException;
use event4u\DataHelpers\Exceptions\UndefinedSourceValueException;
use event4u\DataHelpers\Exceptions\UndefinedTargetValueException;

describe('MapperExceptions', function(): void {
//    beforeEach(function(): void {
//        MapperExceptions::reset();
//    });
//    afterEach(function(): void {
//        MapperExceptions::reset();
//    });

    describe('Default Settings', function(): void {
//        beforeEach(function(): void {
//            MapperExceptions::reset();
//        });
//        afterEach(function(): void {
//            MapperExceptions::reset();
//        });

        it('has exceptions enabled by default', function(): void {
            expect(MapperExceptions::isExceptionsEnabled())->toBeTrue();
        });

        it('has collectExceptions enabled by default', function(): void {
            expect(MapperExceptions::isCollectExceptionsEnabled())->toBeTrue();
        });

        it('has throwExceptionOnUndefinedSourceValue disabled by default', function(): void {
            expect(MapperExceptions::isThrowOnUndefinedSourceEnabled())->toBeFalse();
        });

        it('has throwExceptionOnUndefinedTargetValue disabled by default', function(): void {
            expect(MapperExceptions::isThrowOnUndefinedTargetEnabled())->toBeFalse();
        });

        it('has no exceptions by default', function(): void {
            expect(MapperExceptions::hasExceptions())->toBeFalse();
            expect(MapperExceptions::getExceptions())->toBe([]);
            expect(MapperExceptions::getExceptionCount())->toBe(0);
        });
    });

    describe('Collect Exceptions Setting', function(): void {
//        beforeEach(function (): void {
//            MapperExceptions::reset();
//        });
//        afterEach(function (): void {
//            MapperExceptions::reset();
//        });

        it('can enable collectExceptions', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(true);
            expect(MapperExceptions::isCollectExceptionsEnabled())->toBeTrue();
        });

        it('can disable collectExceptions', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            expect(MapperExceptions::isCollectExceptionsEnabled())->toBeFalse();
        });
    });

    describe('Undefined Source Value Setting', function(): void {
//        beforeEach(function (): void {
//            MapperExceptions::reset();
//        });
//        afterEach(function (): void {
//            MapperExceptions::reset();
//        });

        it('can enable throwExceptionOnUndefinedSourceValue', function(): void {
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);
            expect(MapperExceptions::isThrowOnUndefinedSourceEnabled())->toBeTrue();
        });

        it('can disable throwExceptionOnUndefinedSourceValue', function(): void {
            MapperExceptions::setThrowOnUndefinedSourceEnabled(false);
            expect(MapperExceptions::isThrowOnUndefinedSourceEnabled())->toBeFalse();
        });
    });

    describe('Undefined Target Value Setting', function(): void {
//        beforeEach(function (): void {
//            MapperExceptions::reset();
//        });
//        afterEach(function (): void {
//            MapperExceptions::reset();
//        });

        it('can enable throwExceptionOnUndefinedTargetValue', function(): void {
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);
            expect(MapperExceptions::isThrowOnUndefinedTargetEnabled())->toBeTrue();
        });

        it('can disable throwExceptionOnUndefinedTargetValue', function(): void {
            MapperExceptions::setThrowOnUndefinedTargetEnabled(false);
            expect(MapperExceptions::isThrowOnUndefinedTargetEnabled())->toBeFalse();
        });
    });

    describe('Exception Collection', function(): void {
//        beforeEach(function (): void {
//            MapperExceptions::reset();
//        });
//        afterEach(function (): void {
//            MapperExceptions::reset();
//        });

        it('can add exceptions', function(): void {
            $exception = new RuntimeException('Test exception');
            MapperExceptions::addException($exception);

            expect(MapperExceptions::hasExceptions())->toBeTrue();
            expect(MapperExceptions::getExceptionCount())->toBe(1);
            expect(MapperExceptions::getExceptions())->toBe([$exception]);
        });

        it('can add multiple exceptions', function(): void {
            $exception1 = new RuntimeException('Exception 1');
            $exception2 = new RuntimeException('Exception 2');

            MapperExceptions::addException($exception1);
            MapperExceptions::addException($exception2);

            expect(MapperExceptions::getExceptionCount())->toBe(2);
            expect(MapperExceptions::getExceptions())->toBe([$exception1, $exception2]);
        });

        it('can get last exception', function(): void {
            $exception1 = new RuntimeException('Exception 1');
            $exception2 = new RuntimeException('Exception 2');

            MapperExceptions::addException($exception1);
            MapperExceptions::addException($exception2);

            expect(MapperExceptions::getLastException())->toBe($exception2);
        });

        it('returns null for last exception when no exceptions', function(): void {
            expect(MapperExceptions::getLastException())->toBeNull();
        });

        it('can clear exceptions', function(): void {
            MapperExceptions::addException(new RuntimeException('Test'));
            expect(MapperExceptions::hasExceptions())->toBeTrue();

            MapperExceptions::clearExceptions();

            expect(MapperExceptions::hasExceptions())->toBeFalse();
            expect(MapperExceptions::getExceptionCount())->toBe(0);
        });
    });

    describe('Exception Handling', function(): void {
//        beforeEach(function (): void {
//            MapperExceptions::reset();
//        });
//        afterEach(function (): void {
//            MapperExceptions::reset();
//        });

        it('collects exceptions when collectExceptions is true', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(true);
            $exception = new RuntimeException('Test exception');

            MapperExceptions::handleException($exception);

            expect(MapperExceptions::hasExceptions())->toBeTrue();
            expect(MapperExceptions::getExceptions())->toBe([$exception]);
        });

        it('throws exceptions when collectExceptions is false', function(): void {
            MapperExceptions::setCollectExceptionsEnabled(false);
            $exception = new RuntimeException('Test exception');

            expect(fn() => MapperExceptions::handleException($exception))
                ->toThrow(RuntimeException::class, 'Test exception');
        });
    });

    describe('Undefined Source Value Handling', function(): void {
//        beforeEach(function (): void {
//            MapperExceptions::reset();
//        });
//        afterEach(function (): void {
//            MapperExceptions::reset();
//        });

        it('does nothing when throwExceptionOnUndefinedSourceValue is false', function(): void {
            MapperExceptions::setThrowOnUndefinedSourceEnabled(false);

            MapperExceptions::handleUndefinedSourceValue('test.path', ['data' => 'value']);

            expect(MapperExceptions::hasExceptions())->toBeFalse();
        });

        it(
            'collects exception when throwExceptionOnUndefinedSourceValue is true and collectExceptions is true',
            function(): void {
                MapperExceptions::setThrowOnUndefinedSourceEnabled(true);
                MapperExceptions::setCollectExceptionsEnabled(true);

                MapperExceptions::handleUndefinedSourceValue('test.path', ['data' => 'value']);

                expect(MapperExceptions::hasExceptions())->toBeTrue();
                expect(MapperExceptions::getExceptionCount())->toBe(1);
                expect(MapperExceptions::getLastException())->toBeInstanceOf(UndefinedSourceValueException::class);
            }
        );

        it(
            'throws exception when throwExceptionOnUndefinedSourceValue is true and collectExceptions is false',
            function(): void {
                MapperExceptions::setThrowOnUndefinedSourceEnabled(true);
                MapperExceptions::setCollectExceptionsEnabled(false);

                expect(fn() => MapperExceptions::handleUndefinedSourceValue('test.path', ['data' => 'value']))
                    ->toThrow(UndefinedSourceValueException::class);
            }
        );
    });

    describe('Undefined Target Value Handling', function(): void {
//        beforeEach(function (): void {
//            MapperExceptions::reset();
//        });
//        afterEach(function (): void {
//            MapperExceptions::reset();
//        });

        it('does nothing when throwExceptionOnUndefinedTargetValue is false', function(): void {
            MapperExceptions::setThrowOnUndefinedTargetEnabled(false);

            MapperExceptions::handleUndefinedTargetValue('test.path', ['data' => 'value']);

            expect(MapperExceptions::hasExceptions())->toBeFalse();
        });

        it(
            'collects exception when throwExceptionOnUndefinedTargetValue is true and collectExceptions is true',
            function(): void {
                MapperExceptions::setThrowOnUndefinedTargetEnabled(true);
                MapperExceptions::setCollectExceptionsEnabled(true);

                MapperExceptions::handleUndefinedTargetValue('test.path', ['data' => 'value']);

                expect(MapperExceptions::hasExceptions())->toBeTrue();
                expect(MapperExceptions::getExceptionCount())->toBe(1);
                expect(MapperExceptions::getLastException())->toBeInstanceOf(UndefinedTargetValueException::class);
            }
        );

        it(
            'throws exception when throwExceptionOnUndefinedTargetValue is true and collectExceptions is false',
            function(): void {
                MapperExceptions::setThrowOnUndefinedTargetEnabled(true);
                MapperExceptions::setCollectExceptionsEnabled(false);

                expect(fn() => MapperExceptions::handleUndefinedTargetValue('test.path', ['data' => 'value']))
                    ->toThrow(UndefinedTargetValueException::class);
            }
        );
    });

    describe('Throw Collected Exceptions', function(): void {
//        beforeEach(function (): void {
//            MapperExceptions::reset();
//        });
//        afterEach(function (): void {
//            MapperExceptions::reset();
//        });

        it('does nothing when no exceptions collected', function(): void {
            MapperExceptions::throwCollectedExceptions();

            expect(true)->toBeTrue(); // No exception thrown
        });

        it('throws CollectedExceptionsException with all collected exceptions', function(): void {
            $exception1 = new RuntimeException('Exception 1');
            $exception2 = new RuntimeException('Exception 2');

            MapperExceptions::addException($exception1);
            MapperExceptions::addException($exception2);

            try {
                MapperExceptions::throwCollectedExceptions();
                $this->fail('Expected CollectedExceptionsException to be thrown');
            } catch (CollectedExceptionsException $collectedExceptionsException) {
                expect($collectedExceptionsException->getExceptionCount())->toBe(2);
                expect($collectedExceptionsException->getExceptions())->toBe([$exception1, $exception2]);
                expect($collectedExceptionsException->getMessage())->toBe('Collected 2 exceptions during mapping');
            }
        });

        it('throws single exception directly when only one exception collected', function(): void {
            $exception = new RuntimeException('Test exception');
            MapperExceptions::addException($exception);

            try {
                MapperExceptions::throwCollectedExceptions();
                $this->fail('Expected RuntimeException to be thrown');
            } catch (RuntimeException $runtimeException) {
                expect($runtimeException)->toBe($exception);
                expect($runtimeException->getMessage())->toBe('Test exception');
            }
        });
    });

    describe('Reset', function(): void {
//        beforeEach(function (): void {
//            MapperExceptions::reset();
//        });
//        afterEach(function (): void {
//            MapperExceptions::reset();
//        });

        it('resets all settings to defaults', function(): void {
            // Change all settings
            MapperExceptions::setCollectExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);
            MapperExceptions::addException(new RuntimeException('Test'));

            // Reset
            MapperExceptions::reset();

            // Verify defaults
            expect(MapperExceptions::isExceptionsEnabled())->toBeTrue();
            expect(MapperExceptions::isCollectExceptionsEnabled())->toBeTrue();
            expect(MapperExceptions::isThrowOnUndefinedSourceEnabled())->toBeFalse();
            expect(MapperExceptions::isThrowOnUndefinedTargetEnabled())->toBeFalse();
            expect(MapperExceptions::hasExceptions())->toBeFalse();
        });
    });

    describe('Master Exception Switch', function(): void {
//        beforeEach(function (): void {
//            MapperExceptions::reset();
//        });
//        afterEach(function (): void {
//            MapperExceptions::reset();
//        });

        it('can disable all exceptions globally', function(): void {
            MapperExceptions::setExceptionsEnabled(false);
            expect(MapperExceptions::isExceptionsEnabled())->toBeFalse();
        });

        it('can enable all exceptions globally', function(): void {
            MapperExceptions::setExceptionsEnabled(false);
            MapperExceptions::setExceptionsEnabled(true);
            expect(MapperExceptions::isExceptionsEnabled())->toBeTrue();
        });

        it('silently ignores exceptions when disabled', function(): void {
            MapperExceptions::setExceptionsEnabled(false);
            MapperExceptions::setCollectExceptionsEnabled(true);

            $exception = new UndefinedSourceValueException('test.path', []);

            // Should not throw or collect
            MapperExceptions::handleException($exception);

            expect(MapperExceptions::hasExceptions())->toBeFalse();
        });

        it('does not throw exceptions when disabled even with collectExceptions=false', function(): void {
            MapperExceptions::setExceptionsEnabled(false);
            MapperExceptions::setCollectExceptionsEnabled(false);

            $exception = new UndefinedSourceValueException('test.path', []);

            // Should not throw
            MapperExceptions::handleException($exception);

            expect(MapperExceptions::hasExceptions())->toBeFalse();
        });

        it('does not handle undefined source values when disabled', function(): void {
            MapperExceptions::setExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedSourceEnabled(true);

            // Should not throw or collect
            MapperExceptions::handleUndefinedSourceValue('test.path', []);

            expect(MapperExceptions::hasExceptions())->toBeFalse();
        });

        it('does not handle undefined target values when disabled', function(): void {
            MapperExceptions::setExceptionsEnabled(false);
            MapperExceptions::setThrowOnUndefinedTargetEnabled(true);

            // Should not throw or collect
            MapperExceptions::handleUndefinedTargetValue('test.path', []);

            expect(MapperExceptions::hasExceptions())->toBeFalse();
        });

        it('resets exceptions enabled to true', function(): void {
            MapperExceptions::setExceptionsEnabled(false);
            expect(MapperExceptions::isExceptionsEnabled())->toBeFalse();

            MapperExceptions::reset();
            expect(MapperExceptions::isExceptionsEnabled())->toBeTrue();
        });
    });
});

