<?php

declare(strict_types=1);

use event4u\DataHelpers\Logging\LogLevel;
use event4u\DataHelpers\Logging\Support\SlackMessageFormatter;

describe('SlackMessageFormatter', function(): void {
    beforeEach(function(): void {
        $this->formatter = new SlackMessageFormatter();
    });

    it('formats simple message', function(): void {
        $payload = $this->formatter->format(
            LogLevel::ERROR,
            'Test error message',
            [],
        );

        expect($payload)->toHaveKey('attachments');
        expect($payload['attachments'])->toBeArray();
        expect($payload['attachments'][0])->toHaveKey('color');
        expect($payload['attachments'][0])->toHaveKey('title');
        expect($payload['attachments'][0]['title'])->toContain('error');
        expect($payload['attachments'][0]['title'])->toContain('Test error message');
    });

    it('uses correct colors for log levels', function(): void {
        $errorPayload = $this->formatter->format(LogLevel::ERROR, 'Error', []);
        $warningPayload = $this->formatter->format(LogLevel::WARNING, 'Warning', []);
        $infoPayload = $this->formatter->format(LogLevel::INFO, 'Info', []);

        expect($errorPayload['attachments'][0]['color'])->toBe('danger');
        expect($warningPayload['attachments'][0]['color'])->toBe('warning');
        expect($infoPayload['attachments'][0]['color'])->toBe('good');
    });

    it('includes context fields', function(): void {
        $payload = $this->formatter->format(
            LogLevel::ERROR,
            'Test message',
            [
                'operation' => 'mapping',
                'performance' => [
                    'duration_ms' => 123.45,
                    'record_count' => 100,
                ],
            ],
        );

        expect($payload['attachments'][0])->toHaveKey('fields');
        $fields = $payload['attachments'][0]['fields'];

        expect($fields)->toBeArray();
        expect(count($fields))->toBeGreaterThan(0);

        // Check that context values are included
        $fieldValues = array_column($fields, 'value');
        $allFieldsText = implode(' ', $fieldValues);

        expect($allFieldsText)->toContain('mapping');
    });

    it('includes exception details', function(): void {
        $exception = new RuntimeException('Test exception', 500);
        $payload = $this->formatter->format(
            LogLevel::ERROR,
            'Exception occurred',
            [
                'exception' => [
                    'class' => $exception::class,
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
            ],
        );

        $fields = $payload['attachments'][0]['fields'];
        $allFieldsText = implode(' ', array_column($fields, 'value'));

        expect($allFieldsText)->toContain('Test exception');
    });

    it('includes timestamp', function(): void {
        $payload = $this->formatter->format(LogLevel::INFO, 'Test', []);

        expect($payload['attachments'][0])->toHaveKey('ts');
        expect($payload['attachments'][0]['ts'])->toBeInt();
    });

    it('includes footer', function(): void {
        $payload = $this->formatter->format(LogLevel::INFO, 'Test', []);

        expect($payload['attachments'][0])->toHaveKey('footer');
        expect($payload['attachments'][0]['footer'])->toBe('Data Helpers');
    });
});
