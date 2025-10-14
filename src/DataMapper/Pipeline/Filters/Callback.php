<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline\Filters;

use Closure;
use event4u\DataHelpers\DataMapper\Context\HookContext;
use event4u\DataHelpers\DataMapper\Context\PairContext;
use event4u\DataHelpers\DataMapper\MapperExceptions;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackParameters;
use event4u\DataHelpers\DataMapper\Pipeline\CallbackRegistry;
use event4u\DataHelpers\DataMapper\Pipeline\FilterInterface;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Filter that applies a registered callback from CallbackRegistry.
 *
 * Used in template expressions with the 'callback' alias.
 *
 * Example:
 *   // Register callback
 *   CallbackRegistry::register('upper', fn($p) => strtoupper($p->value));
 *
 *   // Use in template
 *   $template = ['name' => '{{ user.name | callback:upper }}'];
 */
final class Callback implements FilterInterface
{
    public function transform(mixed $value, HookContext $context): mixed
    {
        // Get callback name from filter arguments
        $args = $context->extra();

        if (count($args) < 1) {
            $exception = new InvalidArgumentException(
                'Callback filter requires a callback name as argument. Usage: {{ value | callback:callbackName }}'
            );
            MapperExceptions::handleException($exception);
            return $value;
        }

        $callbackName = (string)$args[0];

        // Get callback from registry
        $callback = CallbackRegistry::get($callbackName);

        if (!$callback instanceof Closure) {
            $exception = new InvalidArgumentException(
                sprintf(
                    'Callback "%s" is not registered. Available callbacks: %s',
                    $callbackName,
                    implode(', ', CallbackRegistry::getRegisteredNames()) ?: 'none'
                )
            );
            MapperExceptions::handleException($exception);
            return $value;
        }

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
            $result = $callback($params);

            return $result;
        } catch (Throwable $throwable) {
            // Wrap exception with context and handle via MapperExceptions
            $exception = new RuntimeException(
                sprintf(
                    'Callback "%s" failed for path "%s": %s',
                    $callbackName,
                    $context->tgtPath() ?? 'unknown',
                    $throwable->getMessage()
                ),
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
        return 'preTransform';
    }

    public function getFilter(): string
    {
        return 'callback';
    }

    /** @return array<int, string> */
    public function getAliases(): array
    {
        return ['callback'];
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

