<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Symfony;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SymfonyDataHelpersBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__, 2);
    }
}

