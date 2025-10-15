<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\DataMapperExceptionHandler;
use event4u\DataHelpers\Exceptions\CollectedExceptionsException;

/**
 * Tests for DataMapperExceptionHandler and exception handling in DataMapperResult.
 *
 * @internal
 */
describe('DataMapper Exception Handling', function (): void {
    describe('DataMapperExceptionHandler', function (): void {
        it('collects exceptions when collectExceptions is true', function (): void {
            $handler = new DataMapperExceptionHandler(collectExceptions: true);

            expect($handler->hasExceptions())->toBeFalse();

            $exception = new RuntimeException('Test exception');
            $handler->handleException($exception);

            expect($handler->hasExceptions())->toBeTrue();
            expect($handler->getExceptions())->toBe([$exception]);
            expect($handler->getExceptionCount())->toBe(1);
        });

        it('throws exceptions immediately when collectExceptions is false', function (): void {
            $handler = new DataMapperExceptionHandler(collectExceptions: false);

            $exception = new RuntimeException('Test exception');

            expect(fn () => $handler->handleException($exception))
                ->toThrow(RuntimeException::class, 'Test exception');
        });

        it('returns last exception', function (): void {
            $handler = new DataMapperExceptionHandler();

            $exception1 = new RuntimeException('Exception 1');
            $exception2 = new RuntimeException('Exception 2');

            $handler->addException($exception1);
            $handler->addException($exception2);

            expect($handler->getLastException())->toBe($exception2);
        });

        it('returns null when no exceptions', function (): void {
            $handler = new DataMapperExceptionHandler();

            expect($handler->getLastException())->toBeNull();
        });

        it('clears exceptions', function (): void {
            $handler = new DataMapperExceptionHandler();

            $handler->addException(new RuntimeException('Test'));

            expect($handler->hasExceptions())->toBeTrue();

            $handler->clearExceptions();

            expect($handler->hasExceptions())->toBeFalse();
            expect($handler->getExceptionCount())->toBe(0);
        });

        it('throws single exception directly', function (): void {
            $handler = new DataMapperExceptionHandler();

            $exception = new RuntimeException('Test exception');
            $handler->addException($exception);

            expect(fn () => $handler->throwCollectedExceptions())
                ->toThrow(RuntimeException::class, 'Test exception');
        });

        it('wraps multiple exceptions in CollectedExceptionsException', function (): void {
            $handler = new DataMapperExceptionHandler();

            $exception1 = new RuntimeException('Exception 1');
            $exception2 = new RuntimeException('Exception 2');

            $handler->addException($exception1);
            $handler->addException($exception2);

            try {
                $handler->throwCollectedExceptions();
                expect(true)->toBeFalse('Should have thrown exception');
            } catch (CollectedExceptionsException $e) {
                expect($e->getExceptionCount())->toBe(2);
                expect($e->getExceptions())->toBe([$exception1, $exception2]);
            }
        });

        it('throws last exception only', function (): void {
            $handler = new DataMapperExceptionHandler();

            $exception1 = new RuntimeException('Exception 1');
            $exception2 = new RuntimeException('Exception 2');

            $handler->addException($exception1);
            $handler->addException($exception2);

            expect(fn () => $handler->throwLastException())
                ->toThrow(RuntimeException::class, 'Exception 2');
        });

        it('does not throw when no exceptions', function (): void {
            $handler = new DataMapperExceptionHandler();

            // Should not throw
            $handler->throwCollectedExceptions();
            $handler->throwLastException();

            expect(true)->toBeTrue();
        });
    });

    describe('DataMapperResult Exception Methods', function (): void {
        it('has no exceptions by default', function (): void {
            $result = DataMapper::source(['name' => 'Alice'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->hasExceptions())->toBeFalse();
            expect($result->getExceptionCount())->toBe(0);
            expect($result->getExceptions())->toBe([]);
            expect($result->getLastException())->toBeNull();
        });

        it('provides access to exception handler methods', function (): void {
            $result = DataMapper::source(['name' => 'Alice'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            // These methods should not throw
            $result->throwLastException();
            $result->throwCollectedExceptions();

            expect(true)->toBeTrue();
        });

        it('tracks exceptions per result', function (): void {
            // Each result should have its own exception handler
            $result1 = DataMapper::source(['name' => 'Alice'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            $result2 = DataMapper::source(['name' => 'Bob'])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            // Both should have no exceptions
            expect($result1->hasExceptions())->toBeFalse();
            expect($result2->hasExceptions())->toBeFalse();
        });
    });

    describe('Exception Handling in mapMany()', function (): void {
        it('each result has its own exception handler', function (): void {
            $results = DataMapper::source([])
                ->template(['name' => '{{ name }}'])
                ->mapMany([
                    ['source' => ['name' => 'Alice'], 'target' => []],
                    ['source' => ['name' => 'Bob'], 'target' => []],
                ]);

            expect($results)->toHaveCount(2);

            // Each result should have its own exception handler
            expect($results[0]->hasExceptions())->toBeFalse();
            expect($results[1]->hasExceptions())->toBeFalse();
        });
    });
});

