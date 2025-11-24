<?php

declare(strict_types=1);

namespace event4u\DataHelpers\SimpleDto\Enums;

enum ConvertFormat: string
{
    case RTF = 'rtf';
    case HTML = 'html';
    case TEXT = 'text';
}
