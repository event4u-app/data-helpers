<?php

declare(strict_types=1);

use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\Support\LogSampler;

describe('LogSampler', function(): void {
    it('always logs when sampling rate is 1.0', function(): void {
        $sampler = new LogSampler(['errors' => 1.0]);

        // Test 100 times to ensure it's always true
        for ($i = 0; 100 > $i; $i++) {
            expect($sampler->shouldLog(LogEvent::MAPPING_ERROR))->toBeTrue();
        }
    });

    it('never logs when sampling rate is 0.0', function(): void {
        $sampler = new LogSampler(['errors' => 0.0]);

        // Test 100 times to ensure it's always false
        for ($i = 0; 100 > $i; $i++) {
            expect($sampler->shouldLog(LogEvent::MAPPING_ERROR))->toBeFalse();
        }
    });

    it('uses default sampling rate when group not configured', function(): void {
        $sampler = new LogSampler([]);

        // Should use default rate (1.0 for errors)
        expect($sampler->shouldLog(LogEvent::MAPPING_ERROR))->toBeTrue();
    });

    it('samples approximately correct percentage', function(): void {
        $sampler = new LogSampler(['success' => 0.5]);

        $logged = 0;
        $total = 1000;

        for ($i = 0; $i < $total; $i++) {
            if ($sampler->shouldLog(LogEvent::MAPPING_SUCCESS)) {
                $logged++;
            }
        }

        // Should be approximately 50% (allow 10% variance)
        $percentage = $logged / $total;
        expect($percentage)->toBeGreaterThan(0.4);
        expect($percentage)->toBeLessThan(0.6);
    });

    it('handles different sampling groups', function(): void {
        $sampler = new LogSampler([
            'errors' => 1.0,
            'success' => 0.0,
            'performance' => 0.5,
        ]);

        // Errors should always log
        expect($sampler->shouldLog(LogEvent::MAPPING_ERROR))->toBeTrue();

        // Success should never log
        expect($sampler->shouldLog(LogEvent::MAPPING_SUCCESS))->toBeFalse();

        // Performance should sometimes log
        $logged = 0;
        for ($i = 0; 100 > $i; $i++) {
            if ($sampler->shouldLog(LogEvent::MAPPING_PERFORMANCE)) {
                $logged++;
            }
        }
        expect($logged)->toBeGreaterThan(0);
        expect($logged)->toBeLessThan(100);
    });
});

