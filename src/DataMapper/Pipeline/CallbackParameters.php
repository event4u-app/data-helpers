<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline;

/**
 * Parameters passed to callback filters.
 *
 * Provides complete context about the current mapping operation,
 * allowing callbacks to make informed transformation decisions.
 *
 * Example:
 *   $callback = function(CallbackParameters $params) {
 *       // Access all context
 *       if ($params->keyPath === 'user.email') {
 *           return strtolower($params->value);
 *       }
 *       return $params->value;
 *   };
 */
final readonly class CallbackParameters
{
    /**
     * @param mixed $source Complete source data
     * @param array<int|string, mixed> $mapping Complete mapping array
     * @param mixed $target Complete target data
     * @param string $key Final individual key (e.g., 'email')
     * @param string $keyPath Full dot notation path (e.g., 'user.profile.email')
     * @param mixed $value Current value being transformed
     */
    public function __construct(
        public mixed $source,
        public array $mapping,
        public mixed $target,
        public string $key,
        public string $keyPath,
        public mixed $value,
    ) {}
}
