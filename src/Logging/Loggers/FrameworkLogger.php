<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging\Loggers;

use event4u\DataHelpers\Logging\DataHelpersLogger;
use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\LogLevel;
use event4u\DataHelpers\Logging\Support\LogContext;
use event4u\DataHelpers\Logging\Support\LogSampler;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Framework logger implementation.
 *
 * Uses Laravel or Symfony PSR-3 logger.
 */
final class FrameworkLogger implements DataHelpersLogger
{
    private readonly LogSampler $sampler;

    /**
     * @param LoggerInterface $logger PSR-3 logger instance
     * @param LogLevel $minLevel Minimum log level
     * @param array<string, bool> $enabledEvents Enabled events
     * @param array<string, float> $samplingRates Sampling rates per group
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LogLevel $minLevel = LogLevel::INFO,
        private array $enabledEvents = [],
        array $samplingRates = [],
    ) {
        $this->sampler = new LogSampler($samplingRates);
    }

    public function log(LogLevel $level, string $message, array $context = []): void
    {
        if (!$this->isLevelEnabled($level)) {
            return;
        }

        $this->logger->log($level->value, $message, $context);
    }

    public function exception(Throwable $exception, array $context = []): void
    {
        $context = LogContext::create()
            ->withException($exception)
            ->withMany($context)
            ->toArray();

        $this->log(LogLevel::ERROR, $exception->getMessage(), $context);
    }

    public function metric(string $name, float $value, array $tags = []): void
    {
        $context = [
            'metric' => $name,
            'value' => $value,
            'tags' => $tags,
        ];

        $this->log(LogLevel::DEBUG, 'Metric: ' . $name, $context);
    }

    public function event(LogEvent $event, array $context = []): void
    {
        if (!$this->isEventEnabled($event)) {
            return;
        }

        if (!$this->sampler->shouldLog($event)) {
            return;
        }

        $context['event'] = $event->value;
        $context['sampling_rate'] = $this->sampler->getSamplingRate($event);

        $this->log($event->defaultLogLevel(), $event->value, $context);
    }

    public function performance(string $operation, float $durationMs, array $context = []): void
    {
        $recordCount = isset($context['record_count']) && is_int($context['record_count'])
            ? $context['record_count']
            : null;

        $context = LogContext::create()
            ->withPerformance($durationMs, $recordCount)
            ->with('operation', $operation)
            ->withMany($context)
            ->toArray();

        $this->log(LogLevel::DEBUG, 'Performance: ' . $operation, $context);
    }

    public function isEventEnabled(LogEvent $event): bool
    {
        // If no events are configured, all are enabled
        if ([] === $this->enabledEvents) {
            return true;
        }

        return $this->enabledEvents[$event->value] ?? false;
    }

    public function isLevelEnabled(LogLevel $level): bool
    {
        return $level->isAtLeast($this->minLevel);
    }
}

