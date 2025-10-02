<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Context;

/**
 * Context for beforeAll/afterAll hooks.
 */
final class AllContext implements HookContext
{
    public function __construct(
        public string $mode,
        /** @var array<int|string,mixed> $mapping */
        public array $mapping,
        public mixed $source,
        public mixed $target,
    ) {}

    public function mode(): string
    {
        return $this->mode;
    }

    public function modeEnum(): Mode
    {
        return Mode::from($this->mode);
    }

    public function srcPath(): ?string
    {
        return null;
    }

    public function tgtPath(): ?string
    {
        return null;
    }


}
