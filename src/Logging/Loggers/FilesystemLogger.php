<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging\Loggers;

use event4u\DataHelpers\Logging\DataHelpersLogger;
use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\LogLevel;
use event4u\DataHelpers\Logging\Support\LogContext;
use event4u\DataHelpers\Logging\Support\LogSampler;
use Throwable;

/**
 * Filesystem logger implementation.
 *
 * Writes logs to files in JSON format (compatible with Loki/Promtail).
 */
final class FilesystemLogger implements DataHelpersLogger
{
    private readonly LogSampler $sampler;

    /**
     * @param string $logPath Path to log directory
     * @param string $filenamePattern Filename pattern (supports date() format)
     * @param LogLevel $minLevel Minimum log level
     * @param array<string, bool> $enabledEvents Enabled events
     * @param array<string, float> $samplingRates Sampling rates per group
     * @param bool $jsonFormat Use JSON format (for Loki)
     */
    public function __construct(
        private readonly string $logPath = './storage/logs/',
        private readonly string $filenamePattern = 'data-helper-Y-m-d-H-i-s.log',
        private readonly LogLevel $minLevel = LogLevel::INFO,
        private array $enabledEvents = [],
        array $samplingRates = [],
        private readonly bool $jsonFormat = true,
    ) {
        $this->sampler = new LogSampler($samplingRates);
        $this->ensureLogDirectoryExists();
    }

    public function log(LogLevel $level, string $message, array $context = []): void
    {
        if (!$this->isLevelEnabled($level)) {
            return;
        }

        $this->writeLog($level, $message, $context);
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

    /**
     * Write log entry to file.
     *
     * @param LogLevel $level Log level
     * @param string $message Log message
     * @param array<string, mixed> $context Context data
     */
    private function writeLog(LogLevel $level, string $message, array $context): void
    {
        $filename = $this->getLogFilename();
        $logEntry = $this->formatLogEntry($level, $message, $context);

        file_put_contents($filename, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Format log entry.
     *
     * @param LogLevel $level Log level
     * @param string $message Log message
     * @param array<string, mixed> $context Context data
     */
    private function formatLogEntry(LogLevel $level, string $message, array $context): string
    {
        if ($this->jsonFormat) {
            return $this->formatJsonEntry($level, $message, $context);
        }

        return $this->formatTextEntry($level, $message, $context);
    }

    /**
     * Format log entry as JSON (for Loki).
     *
     * @param LogLevel $level Log level
     * @param string $message Log message
     * @param array<string, mixed> $context Context data
     */
    private function formatJsonEntry(LogLevel $level, string $message, array $context): string
    {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level->value,
            'message' => $message,
            'context' => $context,
        ];

        $json = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return false !== $json ? $json : '{}';
    }

    /**
     * Format log entry as text.
     *
     * @param LogLevel $level Log level
     * @param string $message Log message
     * @param array<string, mixed> $context Context data
     */
    private function formatTextEntry(LogLevel $level, string $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = [] !== $context ? ' ' . json_encode($context) : '';

        return sprintf('[%s] %s: %s%s', $timestamp, $level->value, $message, $contextStr);
    }

    /** Get log filename based on pattern. */
    private function getLogFilename(): string
    {
        $filename = date($this->filenamePattern);

        return rtrim($this->logPath, '/') . '/' . $filename;
    }

    /** Ensure log directory exists. */
    private function ensureLogDirectoryExists(): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
}
