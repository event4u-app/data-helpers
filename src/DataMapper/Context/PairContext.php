<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Context;

/**
 * Context for beforePair/afterPair and pre/post transform hooks.
 */
class PairContext implements HookContext
{
    public function __construct(
        public string $mode,
        public int $pairIndex,
        public string $srcPath,
        public string $tgtPath,
        public mixed $source,
        public mixed $target,
        public int|string|null $wildcardIndex = null,
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
        return $this->srcPath;
    }

    public function tgtPath(): ?string
    {
        return $this->tgtPath;
    }
}
