<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

/**
 * Context for beforeWrite/afterWrite hooks.
 */
use ArrayAccess;

/**
 * @implements ArrayAccess<(int | string), mixed>
 */
final class WriteContext extends PairContext implements ArrayAccess
{
    public function __construct(
        string $mode,
        int $pairIndex,
        string $srcPath,
        string $tgtPath,
        mixed $source,
        mixed $target,
        public ?string $resolvedTargetPath = null,
        ?int $wildcardIndex = null,
    ) {
        parent::__construct($mode, $pairIndex, $srcPath, $tgtPath, $source, $target, $wildcardIndex);
    }

    public function toArray(): array
    {
        $a = parent::toArray();
        $a['resolvedTargetPath'] = $this->resolvedTargetPath;

        return $a;
    }
}
