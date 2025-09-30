<?php

declare(strict_types=1);

namespace event4u\DataHelpers\Enums;

/**
 * Hook names for event4u\\DataHelpers\\DataMapper.
 *
 * Use these enum values (->value) to avoid typos in hook names
 * when building the $hooks array for DataMapper::map()/mapMany().
 */
enum DataMapperHook: string
{
    case BeforeAll = 'beforeAll';
    case AfterAll = 'afterAll';

    case BeforeEntry = 'beforeEntry';
    case AfterEntry = 'afterEntry';

    case BeforePair = 'beforePair';
    case AfterPair = 'afterPair';

    case PreTransform = 'preTransform';
    case PostTransform = 'postTransform';

    case BeforeWrite = 'beforeWrite';
    case AfterWrite = 'afterWrite';
}
