<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper;

/**
 * Mapping/Hook execution mode for DataMapper.
 */
enum Mode: string
{
    case Simple = 'simple';
    case Structured = 'structured';
    case StructuredAssoc = 'structured-assoc';
    case StructuredPairs = 'structured-pairs';
}
