<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging\Loggers;

use event4u\DataHelpers\Logging\DataHelpersLogger;
use event4u\DataHelpers\Logging\Jobs\SendLogToSlackJob;
use event4u\DataHelpers\Logging\LogEvent;
use event4u\DataHelpers\Logging\LogLevel;
use event4u\DataHelpers\Logging\Support\SlackMessageFormatter;
use event4u\DataHelpers\Logging\Symfony\SendLogToSlackMessage;
use Throwable;

/**
 * Slack logger decorator.
 *
 * Sends log messages to Slack webhook in addition to the base logger.
 */
final readonly class SlackLogger implements DataHelpersLogger
{
    private SlackMessageFormatter $formatter;

    /**
     * @param DataHelpersLogger $baseLogger Base logger to decorate
     * @param string $webhookUrl Slack webhook URL
     * @param string $channel Slack channel
     * @param string $username Bot username
     * @param LogLevel $minLevel Minimum log level for Slack
     * @param array<string> $enabledEvents Events to send to Slack
     * @param string|null $queueName Queue name for async sending (null = sync)
     * @param object|null $messageBus Symfony Messenger bus (optional, \Symfony\Component\Messenger\MessageBusInterface)
     */
    public function __construct(
        private DataHelpersLogger $baseLogger,
        private string $webhookUrl,
        private string $channel = '#data-helpers',
        private string $username = 'Data Helpers Bot',
        private LogLevel $minLevel = LogLevel::ERROR,
        private array $enabledEvents = [],
        private ?string $queueName = null,
        private ?object $messageBus = null,
    ) {
        $this->formatter = new SlackMessageFormatter();
    }

    public function log(LogLevel $level, string $message, array $context = []): void
    {
        // Always log to base logger
        $this->baseLogger->log($level, $message, $context);

        // Send to Slack if level is high enough
        if ($level->isAtLeast($this->minLevel)) {
            $this->sendToSlack($level, $message, $context);
        }
    }

    public function exception(Throwable $exception, array $context = []): void
    {
        // Always log to base logger
        $this->baseLogger->exception($exception, $context);

        // Exceptions are always sent to Slack (if ERROR level is enabled)
        if (LogLevel::ERROR->isAtLeast($this->minLevel)) {
            $context['exception'] = [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];

            $this->sendToSlack(LogLevel::ERROR, $exception->getMessage(), $context);
        }
    }

    public function metric(string $name, float $value, array $tags = []): void
    {
        // Only log to base logger (metrics are not sent to Slack)
        $this->baseLogger->metric($name, $value, $tags);
    }

    public function event(LogEvent $event, array $context = []): void
    {
        // Always log to base logger
        $this->baseLogger->event($event, $context);

        // Send to Slack if event is enabled
        if ($this->isEventEnabledForSlack($event)) {
            $payload = $this->formatter->formatEvent($event, $context);
            $this->send($payload);
        }
    }

    public function performance(string $operation, float $durationMs, array $context = []): void
    {
        // Only log to base logger (performance is not sent to Slack)
        $this->baseLogger->performance($operation, $durationMs, $context);
    }

    public function isEventEnabled(LogEvent $event): bool
    {
        return $this->baseLogger->isEventEnabled($event);
    }

    public function isLevelEnabled(LogLevel $level): bool
    {
        return $this->baseLogger->isLevelEnabled($level);
    }

    /** Check if event should be sent to Slack. */
    private function isEventEnabledForSlack(LogEvent $event): bool
    {
        // If no events are configured, none are sent to Slack
        if ([] === $this->enabledEvents) {
            return false;
        }

        return in_array($event->value, $this->enabledEvents, true);
    }

    /**
     * Send log message to Slack.
     *
     * @param LogLevel $level Log level
     * @param string $message Log message
     * @param array<string, mixed> $context Context data
     */
    private function sendToSlack(LogLevel $level, string $message, array $context): void
    {
        $payload = $this->formatter->format($level, $message, $context);
        $this->send($payload);
    }

    /**
     * Send payload to Slack.
     *
     * @param array<string, mixed> $payload Slack payload
     */
    private function send(array $payload): void
    {
        // Add channel and username
        $payload['channel'] = $this->channel;
        $payload['username'] = $this->username;

        if (null !== $this->queueName) {
            $this->sendAsync($payload);
        } else {
            $this->sendSync($payload);
        }
    }

    /**
     * Send payload synchronously.
     *
     * @param array<string, mixed> $payload Slack payload
     */
    private function sendSync(array $payload): void
    {
        $ch = curl_init($this->webhookUrl);
        if (false === $ch) {
            return;
        }

        $jsonPayload = json_encode($payload);
        if (false === $jsonPayload) {
            curl_close($ch);

            return;
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Send payload asynchronously via queue.
     *
     * @param array<string, mixed> $payload Slack payload
     */
    private function sendAsync(array $payload): void
    {
        // Check if Laravel is available
        if (function_exists('dispatch')) {
            // Laravel Queue
            dispatch(new SendLogToSlackJob(
                $this->webhookUrl,
                $payload,
            ))->onQueue($this->queueName);

            return;
        }

        // Check if Symfony Messenger is available
        if (null !== $this->messageBus && method_exists($this->messageBus, 'dispatch')) {
            // Symfony Messenger
            $this->messageBus->dispatch(
                new SendLogToSlackMessage(
                    $this->webhookUrl,
                    $payload,
                ),
            );

            return;
        }

        // No queue available, send sync
        $this->sendSync($payload);
    }
}

