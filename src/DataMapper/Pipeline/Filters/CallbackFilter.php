<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use Closure;
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackParameters;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use event4u\DataHelpers\Enums\DataMapperHook;
use RuntimeException;
use Throwable;

/**
 * Filter that applies a custom callback transformation.
 *
 * The callback receives a CallbackParameters Dto with complete context
 * and returns the transformed value or '__skip__' to skip the value.
 *
 * Example:
 *   DataMapper::source($source)->target([])->template($mapping)->pipeline([
 *       new CallbackFilter(function(CallbackParameters $params) {
 *           return strtoupper($params->value);
 *       }),
 *   ])->map()->getTarget();
 */
final readonly class CallbackFilter implements FilterInterface
{
    /** @param Closure(CallbackParameters): mixed $callback Callback that receives CallbackParameters and returns mixed */
    public function __construct(
        private Closure $callback,
    ) {}

    public function transform(mixed $value, HookContext $context): mixed
    {
        try {
            // Extract context information
            $source = $context instanceof PairContext ? $context->source : null;
            $target = $context instanceof PairContext ? $context->target : null;
            $keyPath = $context->tgtPath() ?? '';
            $key = $this->extractFinalKey($keyPath);

            // Build CallbackParameters
            $params = new CallbackParameters(
                $source,
                [], // mapping - Will be populated by pipeline
                $target,
                $key,
                $keyPath,
                $value,
            );

            // Execute callback
            $result = ($this->callback)($params);

            return $result;
        } catch (Throwable $throwable) {
            // Wrap exception with context and handle via MapperExceptions
            $exception = new RuntimeException(
                'Callback filter failed for path "' . ($context->tgtPath() ?? 'unknown') . '": ' . $throwable->getMessage(),
                0,
                $throwable
            );

            MapperExceptions::handleException($exception);

            // Return original value if exception was collected
            return $value;
        }
    }

    public function getHook(): string
    {
        return DataMapperHook::BeforeTransform->value;
    }

    public function getFilter(): ?string
    {
        return null;
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Extract the final key from a dot-notation path.
     *
     * Examples:
     *   'user.profile.email' => 'email'
     *   'name' => 'name'
     *   '' => ''
     */
    private function extractFinalKey(string $path): string
    {
        if ('' === $path) {
            return '';
        }

        $parts = explode('.', $path);
        return end($parts);
    }
}
