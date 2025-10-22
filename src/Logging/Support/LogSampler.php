<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging\Support;

use event4u\DataHelpers\Logging\LogEvent;

/**
 * Handles sampling logic for log events.
 *
 * Allows configuring different sampling rates for different event groups
 * to reduce log volume while maintaining visibility into errors.
 */
final class LogSampler
{
    /** @param array<string, float> $samplingRates Sampling rates per group (0.0 - 1.0) */
    public function __construct(
        private array $samplingRates = [],
    ) {}

    /**
     * Check if an event should be logged based on sampling rate.
     *
     * @param LogEvent $event The event to check
     */
    public function shouldLog(LogEvent $event): bool
    {
        $group = $event->samplingGroup();
        $rate = $this->samplingRates[$group] ?? $event->defaultSamplingRate();

        // Always log if rate is 1.0 (100%)
        if (1.0 <= $rate) {
            return true;
        }

        // Never log if rate is 0.0 (0%)
        if (0.0 >= $rate) {
            return false;
        }

        // Sample based on rate
        return (random_int(0, PHP_INT_MAX) / PHP_INT_MAX) < $rate;
    }

    /**
     * Get the sampling rate for an event.
     *
     * @param LogEvent $event The event
     */
    public function getSamplingRate(LogEvent $event): float
    {
        $group = $event->samplingGroup();

        return $this->samplingRates[$group] ?? $event->defaultSamplingRate();
    }

    /**
     * Set sampling rate for a group.
     *
     * @param string $group Group name
     * @param float $rate Sampling rate (0.0 - 1.0)
     */
    public function setSamplingRate(string $group, float $rate): void
    {
        $this->samplingRates[$group] = max(0.0, min(1.0, $rate));
    }
}
