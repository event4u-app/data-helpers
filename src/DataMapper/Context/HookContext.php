<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Context;

use event4u\DataHelpers\Enums\Mode;

interface HookContext
{
    public function mode(): string;

    public function modeEnum(): Mode;

    public function srcPath(): ?string;

    public function tgtPath(): ?string;
}
