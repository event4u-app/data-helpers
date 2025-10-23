<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging\Symfony;

/**
 * Symfony Messenger message for sending logs to Slack asynchronously.
 */
final readonly class SendLogToSlackMessage
{
    /**
     * @param string $webhookUrl Slack webhook URL
     * @param array<string, mixed> $payload Slack message payload
     */
    public function __construct(
        private string $webhookUrl,
        private array $payload,
    ) {}

    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
