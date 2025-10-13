<?php

declare(strict_types=1);

use event4u\DataHelpers\Logging\LogLevel;

describe('LogLevel', function(): void {
    it('has correct severity values', function(): void {
        expect(LogLevel::EMERGENCY->severity())->toBe(800);
        expect(LogLevel::ALERT->severity())->toBe(700);
        expect(LogLevel::CRITICAL->severity())->toBe(600);
        expect(LogLevel::ERROR->severity())->toBe(500);
        expect(LogLevel::WARNING->severity())->toBe(400);
        expect(LogLevel::NOTICE->severity())->toBe(300);
        expect(LogLevel::INFO->severity())->toBe(200);
        expect(LogLevel::DEBUG->severity())->toBe(100);
    });

    it('compares severity correctly', function(): void {
        expect(LogLevel::ERROR->isAtLeast(LogLevel::WARNING))->toBeTrue();
        expect(LogLevel::ERROR->isAtLeast(LogLevel::ERROR))->toBeTrue();
        expect(LogLevel::ERROR->isAtLeast(LogLevel::CRITICAL))->toBeFalse();
        expect(LogLevel::WARNING->isAtLeast(LogLevel::DEBUG))->toBeTrue();
        expect(LogLevel::DEBUG->isAtLeast(LogLevel::ERROR))->toBeFalse();
    });

    it('has correct PSR-3 values', function(): void {
        expect(LogLevel::EMERGENCY->value)->toBe('emergency');
        expect(LogLevel::ALERT->value)->toBe('alert');
        expect(LogLevel::CRITICAL->value)->toBe('critical');
        expect(LogLevel::ERROR->value)->toBe('error');
        expect(LogLevel::WARNING->value)->toBe('warning');
        expect(LogLevel::NOTICE->value)->toBe('notice');
        expect(LogLevel::INFO->value)->toBe('info');
        expect(LogLevel::DEBUG->value)->toBe('debug');
    });
});

