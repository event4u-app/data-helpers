<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging\Support;

use Throwable;

/**
 * Builder for log context data.
 *
 * Provides a fluent interface for building structured log context.
 */
final class LogContext
{
    /** @param array<string, mixed> $data Context data */
    private function __construct(
        private array $data = [],
    ) {}

    /** Create a new context builder. */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Add a key-value pair to the context.
     *
     * @param string $key Context key
     * @param mixed $value Context value
     */
    public function with(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Add multiple key-value pairs to the context.
     *
     * @param array<string, mixed> $data Context data
     */
    public function withMany(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Add exception information to the context.
     *
     * @param Throwable $exception The exception
     */
    public function withException(Throwable $exception): self
    {
        $this->data['exception'] = [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        return $this;
    }

    /**
     * Add performance metrics to the context.
     *
     * @param float $durationMs Duration in milliseconds
     * @param int|null $recordCount Number of records processed
     */
    public function withPerformance(float $durationMs, ?int $recordCount = null): self
    {
        $this->data['performance'] = [
            'duration_ms' => round($durationMs, 2),
        ];

        if (null !== $recordCount) {
            $this->data['performance']['record_count'] = $recordCount;
            $this->data['performance']['records_per_second'] = 0 < $durationMs
                ? round($recordCount / ($durationMs / 1000), 2)
                : 0;
        }

        return $this;
    }

    /** Add timestamp to the context. */
    public function withTimestamp(): self
    {
        $this->data['timestamp'] = date('Y-m-d H:i:s');

        return $this;
    }

    /** Add memory usage to the context. */
    public function withMemoryUsage(): self
    {
        $this->data['memory'] = [
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        return $this;
    }

    /**
     * Get the context data.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}

