<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging\Jobs;

/**
 * Queue job for sending logs to Slack asynchronously.
 *
 * This job is used when async logging is enabled in Laravel.
 */
final readonly class SendLogToSlackJob
{
    /**
     * @param string $webhookUrl Slack webhook URL
     * @param array<string, mixed> $payload Slack message payload
     */
    public function __construct(
        private string $webhookUrl,
        private array $payload,
    ) {}

    /** Execute the job. */
    public function handle(): void
    {
        $ch = curl_init($this->webhookUrl);
        if (false === $ch) {
            return;
        }

        $jsonPayload = json_encode($this->payload);
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
}

