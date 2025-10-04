<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Context;

/**
 * Context for beforeWrite/afterWrite hooks.
 */
final class WriteContext extends PairContext
{
    public function __construct(
        string $mode,
        int $pairIndex,
        string $srcPath,
        string $tgtPath,
        mixed $source,
        mixed $target,
        public ?string $resolvedTargetPath = null,
        int|string|null $wildcardIndex = null,
    ) {
        parent::__construct($mode, $pairIndex, $srcPath, $tgtPath, $source, $target, $wildcardIndex);
    }
}
