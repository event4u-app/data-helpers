<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Context;

use event4u\DataHelpers\Enums\Mode;

/**
 * Context for beforeEntry/afterEntry hooks (structured mode).
 */
final class EntryContext implements HookContext
{
    public function __construct(
        public string $mode,
        /** @var array<string,mixed> $entry */
        public array $entry,
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
