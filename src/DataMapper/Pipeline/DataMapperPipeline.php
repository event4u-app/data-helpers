<?php

declare(strict_types=1);

namespace event4u\DataHelpers\DataMapper\Pipeline;

use event4u\DataHelpers\DataMapper;

final class DataMapperPipeline
{
    /** @var array<string, mixed> */
    private array $additionalHooks = [];

    /** @param array<int, TransformerInterface|class-string<TransformerInterface>> $transformers */
    public function __construct(private array $transformers = [])
    {
    }

    /** @param TransformerInterface|class-string<TransformerInterface> $transformer */
    public function through(TransformerInterface|string $transformer): self
    {
        $this->transformers[] = $transformer;
        return $this;
    }

    /** @param array<string, mixed> $hooks */
    public function withHooks(array $hooks): self
    {
        $this->additionalHooks = array_merge($this->additionalHooks, $hooks);
        return $this;
    }

    public function map(
        mixed $source,
        mixed $target,
        array $mapping,
        bool $skipNull = true,
        bool $reindexWildcard = false,
        bool $trimValues = true,
        bool $caseInsensitiveReplace = false,
    ): mixed {
        $hooks = $this->buildHooks();
        return DataMapper::map(
            $source,
            $target,
            $mapping,
            $skipNull,
            $reindexWildcard,
            $hooks,
            $trimValues,
            $caseInsensitiveReplace
        );
    }

    /** @return array<string, mixed> */
    private function buildHooks(): array
    {
        $hooks = $this->additionalHooks;

        foreach ($this->transformers as $transformer) {
            if (is_string($transformer)) {
                $transformer = new $transformer();
            }

            $hookName = $transformer->getHook();
            $filter = $transformer->getFilter();
            $callback = fn($value, $context) => $transformer->transform($value, $context);

            if (null !== $filter) {
                if (!isset($hooks[$hookName])) {
                    $hooks[$hookName] = [];
                }
                if (!is_array($hooks[$hookName])) {
                    $hooks[$hookName] = [$hooks[$hookName]];
                }
                $hooks[$hookName][$filter] = $callback;
            } elseif (!isset($hooks[$hookName])) {
                $hooks[$hookName] = $callback;
            } elseif (is_callable($hooks[$hookName])) {
                $hooks[$hookName] = [$hooks[$hookName], $callback];
            } elseif (is_array($hooks[$hookName])) {
                $hooks[$hookName][] = $callback;
            }
        }

        return $hooks;
    }
}
