<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDTO\Pipeline;

use Closure;

/**
 * Pipeline stage that executes a callback.
 *
 * This allows for custom processing logic without creating a dedicated stage class.
 */
class CallbackStage implements PipelineStageInterface
{
    /**
     * @param Closure(array<string, mixed>): array<string, mixed> $callback
     */
    public function __construct(
        private readonly Closure $callback,
        private readonly string $name = 'callback'
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function process(array $data): array
    {
        return ($this->callback)($data);
    }

    public function getName(): string
    {
        return $this->name;
    }
}

