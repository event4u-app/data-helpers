<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Logging\Symfony;

/**
 * Symfony Messenger handler for sending logs to Slack.
 *
 * Register this handler in your Symfony services.yaml:
 *
 * event4u\DataHelpers\Logging\Symfony\SendLogToSlackMessageHandler:
 *     tags: [messenger.message_handler]
 */
final class SendLogToSlackMessageHandler
{
    public function __invoke(SendLogToSlackMessage $message): void
    {
        $ch = curl_init($message->getWebhookUrl());
        if (false === $ch) {
            return;
        }

        $jsonPayload = json_encode($message->getPayload());
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

