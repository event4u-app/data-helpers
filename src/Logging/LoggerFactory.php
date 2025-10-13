<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging;

use event4u\DataHelpers\DataHelpersConfig;
use event4u\DataHelpers\Logging\Loggers\FilesystemLogger;
use event4u\DataHelpers\Logging\Loggers\FrameworkLogger;
use event4u\DataHelpers\Logging\Loggers\NullLogger;
use event4u\DataHelpers\Logging\Loggers\PrometheusLogger;
use event4u\DataHelpers\Logging\Loggers\SlackLogger;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating logger instances based on configuration.
 */
final class LoggerFactory
{
    /**
     * Create a logger instance from configuration.
     *
     * @param LoggerInterface|null $frameworkLogger Framework logger instance (optional)
     * @param object|null $messageBus Symfony Messenger bus (optional, \Symfony\Component\Messenger\MessageBusInterface)
     */
    public static function create(
        ?LoggerInterface $frameworkLogger = null,
        ?object $messageBus = null,
    ): DataHelpersLogger
    {
        $config = DataHelpersConfig::get('logging', []);
        if (!is_array($config)) {
            $config = [];
        }

        // Get base logger
        $logger = self::createBaseLogger($config, $frameworkLogger);

        // Wrap with Slack logger if enabled
        $slackConfig = is_array($config['slack'] ?? null) ? $config['slack'] : [];
        if (($slackConfig['enabled'] ?? false) && !empty($slackConfig['webhook_url'])) {
            $logger = self::wrapWithSlackLogger($logger, $slackConfig, $messageBus);
        }

        // Wrap with Prometheus logger if enabled
        $grafanaConfig = is_array($config['grafana'] ?? null) ? $config['grafana'] : [];
        $prometheusConfig = is_array($grafanaConfig['prometheus'] ?? null) ? $grafanaConfig['prometheus'] : [];
        if ($prometheusConfig['enabled'] ?? false) {
            return self::wrapWithPrometheusLogger($logger, $prometheusConfig);
        }

        return $logger;
    }

    /**
     * Create base logger based on driver.
     *
     * @param array<string, mixed> $config Logging configuration
     * @param LoggerInterface|null $frameworkLogger Framework logger instance
     */
    private static function createBaseLogger(array $config, ?LoggerInterface $frameworkLogger): DataHelpersLogger
    {
        $driver = $config['driver'] ?? 'filesystem';

        return match ($driver) {
            'filesystem' => self::createFilesystemLogger($config),
            'framework' => self::createFrameworkLogger($config, $frameworkLogger),
            'none' => new NullLogger(),
            default => new NullLogger(),
        };
    }

    /**
     * Create filesystem logger.
     *
     * @param array<string, mixed> $config Logging configuration
     */
    private static function createFilesystemLogger(array $config): FilesystemLogger
    {
        $path = is_string($config['path'] ?? null) ? $config['path'] : './storage/logs/';
        $filenamePattern = is_string(
            $config['filename_pattern'] ?? null
        ) ? $config['filename_pattern'] : 'data-helper-Y-m-d-H-i-s.log';
        $level = is_string($config['level'] ?? null) ? $config['level'] : 'info';

        /** @var array<string, bool> $events */
        $events = is_array($config['events'] ?? null) ? $config['events'] : [];

        /** @var array<string, float> $sampling */
        $sampling = is_array($config['sampling'] ?? null) ? $config['sampling'] : [];

        $grafanaConfig = is_array($config['grafana'] ?? null) ? $config['grafana'] : [];
        $format = is_string($grafanaConfig['format'] ?? null) ? $grafanaConfig['format'] : 'json';

        return new FilesystemLogger(
            $path,
            $filenamePattern,
            self::parseLogLevel($level),
            $events,
            $sampling,
            'json' === $format,
        );
    }

    /**
     * Create framework logger.
     *
     * @param array<string, mixed> $config Logging configuration
     * @param LoggerInterface|null $frameworkLogger Framework logger instance
     */
    private static function createFrameworkLogger(
        array $config,
        ?LoggerInterface $frameworkLogger,
    ): DataHelpersLogger {
        if (!$frameworkLogger instanceof LoggerInterface) {
            // Try to auto-detect framework logger
            $frameworkLogger = self::detectFrameworkLogger();
        }

        if (!$frameworkLogger instanceof LoggerInterface) {
            // Fall back to filesystem logger
            return self::createFilesystemLogger($config);
        }

        $level = is_string($config['level'] ?? null) ? $config['level'] : 'info';

        /** @var array<string, bool> $events */
        $events = is_array($config['events'] ?? null) ? $config['events'] : [];

        /** @var array<string, float> $sampling */
        $sampling = is_array($config['sampling'] ?? null) ? $config['sampling'] : [];

        return new FrameworkLogger(
            $frameworkLogger,
            self::parseLogLevel($level),
            $events,
            $sampling,
        );
    }

    /**
     * Wrap logger with Slack logger.
     *
     * @param DataHelpersLogger $baseLogger Base logger
     * @param array<string, mixed> $slackConfig Slack configuration
     * @param object|null $messageBus Symfony Messenger bus (optional, \Symfony\Component\Messenger\MessageBusInterface)
     */
    private static function wrapWithSlackLogger(
        DataHelpersLogger $baseLogger,
        array $slackConfig,
        ?object $messageBus = null,
    ): SlackLogger {
        $webhookUrl = is_string($slackConfig['webhook_url'] ?? null) ? $slackConfig['webhook_url'] : '';
        $channel = is_string($slackConfig['channel'] ?? null) ? $slackConfig['channel'] : '#data-helpers';
        $username = is_string($slackConfig['username'] ?? null) ? $slackConfig['username'] : 'Data Helpers Bot';
        $level = is_string($slackConfig['level'] ?? null) ? $slackConfig['level'] : 'error';

        /** @var array<string> $events */
        $events = is_array($slackConfig['events'] ?? null) ? $slackConfig['events'] : [];

        $queue = is_string($slackConfig['queue'] ?? null) ? $slackConfig['queue'] : null;

        return new SlackLogger(
            $baseLogger,
            $webhookUrl,
            $channel,
            $username,
            self::parseLogLevel($level),
            $events,
            $queue,
            $messageBus,
        );
    }

    /**
     * Wrap logger with Prometheus logger.
     *
     * @param DataHelpersLogger $baseLogger Base logger
     * @param array<string, mixed> $prometheusConfig Prometheus configuration
     */
    private static function wrapWithPrometheusLogger(
        DataHelpersLogger $baseLogger,
        array $prometheusConfig,
    ): PrometheusLogger {
        $metricsFile = is_string($prometheusConfig['metrics_file'] ?? null)
            ? $prometheusConfig['metrics_file']
            : './storage/metrics/data-helpers.prom';

        return new PrometheusLogger(
            $baseLogger,
            $metricsFile,
        );
    }

    /** Try to detect framework logger. */
    private static function detectFrameworkLogger(): ?LoggerInterface
    {
        // Try Laravel
        if (function_exists('app') && app()->bound('log')) {
            return app('log');
        }

        // Try Symfony
        if (class_exists('Symfony\Component\DependencyInjection\ContainerInterface')) {
            // Would need container instance - not available here
            return null;
        }

        return null;
    }

    /** Parse log level string to enum. */
    private static function parseLogLevel(string $level): LogLevel
    {
        return LogLevel::tryFrom(strtolower($level)) ?? LogLevel::INFO;
    }
}

