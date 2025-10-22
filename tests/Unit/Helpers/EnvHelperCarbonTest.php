<?php

declare(strict_types=1);

// Skip this file entirely if Carbon is not installed
if (!class_exists('Carbon\Carbon')) {
    return;
}

use Carbon\Carbon;
use event4u\DataHelpers\Helpers\EnvHelper;

describe('EnvHelper Carbon Support', function(): void {
    it('detects Carbon availability', function(): void {
        expect(EnvHelper::hasCarbonSupport())->toBeTrue();
    });

    it('parses Carbon from environment variable', function(): void {
        $_ENV['TEST_CARBON_DATE'] = '2024-01-15 10:30:00';

        $carbon = EnvHelper::carbon('TEST_CARBON_DATE');

        expect($carbon)->toBeInstanceOf(Carbon::class);
        expect($carbon->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');

        unset($_ENV['TEST_CARBON_DATE']);
    });

    it('uses default value when env variable is not set', function(): void {
        $carbon = EnvHelper::carbon('NON_EXISTENT_DATE', '2024-12-25 00:00:00');

        expect($carbon)->toBeInstanceOf(Carbon::class);
        expect($carbon->format('Y-m-d'))->toBe('2024-12-25');
    });

    it('throws exception for invalid date format', function(): void {
        $_ENV['TEST_INVALID_DATE'] = 'not-a-date';

        expect(fn(): mixed => EnvHelper::carbon('TEST_INVALID_DATE'))
            ->toThrow(InvalidArgumentException::class);

        unset($_ENV['TEST_INVALID_DATE']);
    });

    it('parses relative dates', function(): void {
        $_ENV['TEST_RELATIVE_DATE'] = 'tomorrow';

        $carbon = EnvHelper::carbon('TEST_RELATIVE_DATE');

        expect($carbon)->toBeInstanceOf(Carbon::class);
        expect($carbon->isToday())->toBeFalse();
        expect($carbon->isTomorrow())->toBeTrue();

        unset($_ENV['TEST_RELATIVE_DATE']);
    });

    it('parses ISO 8601 dates', function(): void {
        $_ENV['TEST_ISO_DATE'] = '2024-01-15T10:30:00+00:00';

        $carbon = EnvHelper::carbon('TEST_ISO_DATE');

        expect($carbon)->toBeInstanceOf(Carbon::class);
        expect($carbon->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');

        unset($_ENV['TEST_ISO_DATE']);
    });
})->group('carbon');
