<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging\Support;

use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\LogLevel;

/**
 * Formats log messages for Slack.
 *
 * Creates rich Slack messages with attachments and formatting.
 */
final class SlackMessageFormatter
{
    /**
     * Format a log message for Slack.
     *
     * @param LogLevel $level Log level
     * @param string $message Log message
     * @param array<string, mixed> $context Context data
     * @param string|null $logFilePath Path to log file (optional)
     * @return array<string, mixed> Slack message payload
     */
    public function format(
        LogLevel $level,
        string $message,
        array $context = [],
        ?string $logFilePath = null,
    ): array {
        $color = $this->getColorForLevel($level);
        $emoji = $this->getEmojiForLevel($level);

        $fields = $this->buildFields($context);

        $attachment = [
            'color' => $color,
            'title' => sprintf('%s %s: %s', $emoji, $level->value, $message),
            'fields' => $fields,
            'footer' => 'Data Helpers',
            'ts' => time(),
        ];

        // Add log file info if available
        if (null !== $logFilePath && file_exists($logFilePath)) {
            $attachment['fields'][] = [
                'title' => 'Log File',
                'value' => basename($logFilePath),
                'short' => true,
            ];
        }

        return [
            'attachments' => [$attachment],
        ];
    }

    /**
     * Format an event message for Slack.
     *
     * @param LogEvent $event Log event
     * @param array<string, mixed> $context Context data
     * @param string|null $logFilePath Path to log file (optional)
     * @return array<string, mixed> Slack message payload
     */
    public function formatEvent(
        LogEvent $event,
        array $context = [],
        ?string $logFilePath = null,
    ): array {
        $level = $event->defaultLogLevel();

        return $this->format($level, $event->value, $context, $logFilePath);
    }

    /**
     * Build Slack message fields from context.
     *
     * @param array<string, mixed> $context Context data
     * @return array<int, array<string, mixed>> Slack fields
     */
    private function buildFields(array $context): array
    {
        $fields = [];

        // Performance metrics
        if (isset($context['performance']) && is_array($context['performance'])) {
            $perf = $context['performance'];
            $durationMs = is_numeric($perf['duration_ms'] ?? 0) ? (float)$perf['duration_ms'] : 0.0;
            $fields[] = [
                'title' => 'Duration',
                'value' => $durationMs . ' ms',
                'short' => true,
            ];

            if (isset($perf['record_count'])) {
                $fields[] = [
                    'title' => 'Records',
                    'value' => (string)$perf['record_count'],
                    'short' => true,
                ];
            }
        }

        // Exception info
        if (isset($context['exception']) && is_array($context['exception'])) {
            $exc = $context['exception'];
            $excClass = is_string($exc['class'] ?? null) ? $exc['class'] : 'Unknown';
            $excMessage = is_string($exc['message'] ?? null) ? $exc['message'] : '';
            $excFile = is_string($exc['file'] ?? null) ? $exc['file'] : 'Unknown';
            $excLine = is_int($exc['line'] ?? null) ? $exc['line'] : 0;

            $fields[] = [
                'title' => 'Exception',
                'value' => sprintf('%s: %s', $excClass, $excMessage),
                'short' => false,
            ];
            $fields[] = [
                'title' => 'Location',
                'value' => sprintf('%s:%d', $excFile, $excLine),
                'short' => false,
            ];
        }

        // Event info
        if (isset($context['event'])) {
            $fields[] = [
                'title' => 'Event',
                'value' => $context['event'],
                'short' => true,
            ];
        }

        // Operation info
        if (isset($context['operation'])) {
            $fields[] = [
                'title' => 'Operation',
                'value' => $context['operation'],
                'short' => true,
            ];
        }

        // Memory usage
        if (isset($context['memory']) && is_array($context['memory'])) {
            $mem = $context['memory'];
            $currentMb = is_numeric($mem['current_mb'] ?? 0) ? (float)$mem['current_mb'] : 0.0;
            $peakMb = is_numeric($mem['peak_mb'] ?? 0) ? (float)$mem['peak_mb'] : 0.0;

            $fields[] = [
                'title' => 'Memory',
                'value' => sprintf('Current: %s MB, Peak: %s MB', $currentMb, $peakMb),
                'short' => false,
            ];
        }

        return $fields;
    }

    /** Get Slack color for log level. */
    private function getColorForLevel(LogLevel $level): string
    {
        return match ($level) {
            LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR => 'danger',
            LogLevel::WARNING => 'warning',
            LogLevel::NOTICE, LogLevel::INFO => 'good',
            LogLevel::DEBUG => '#439FE0',
        };
    }

    /** Get emoji for log level. */
    private function getEmojiForLevel(LogLevel $level): string
    {
        return match ($level) {
            LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL => '🚨',
            LogLevel::ERROR => '❌',
            LogLevel::WARNING => '⚠️',
            LogLevel::NOTICE => '📢',
            LogLevel::INFO => 'ℹ️',
            LogLevel::DEBUG => '🔍',
        };
    }
}

