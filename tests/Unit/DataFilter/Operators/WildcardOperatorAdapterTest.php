<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\OperatorContext;
use event4u\DataHelpers\DataFilter\Operators\OperatorInterface;
use event4u\DataHelpers\DataFilter\Operators\WhereOperator;
use event4u\DataHelpers\DataFilter\Operators\WildcardOperatorAdapter;

describe('WildcardOperatorAdapter', function(): void {
    it('adapts operator to wildcard signature', function(): void {
        $operator = new WhereOperator();
        $adapted = WildcardOperatorAdapter::adapt($operator);

        expect($adapted)->toBeInstanceOf(Closure::class);
    });

    it('converts wildcard signature to DataFilter signature', function(): void {
        $operator = new class implements OperatorInterface {
            public OperatorContext|null $capturedContext = null;

            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                $this->capturedContext = $context;

                return $items;
            }

            public function getName(): string
            {
                return 'TEST';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $adapted = WildcardOperatorAdapter::adapt($operator);
        $items = [['id' => 1], ['id' => 2]];
        $config = ['id' => 1];
        $sources = ['source' => 'data'];
        $aliases = ['alias' => 'value'];

        $result = $adapted($items, $config, $sources, $aliases);

        expect($result)->toBe($items);
        expect($operator->capturedContext)->toBeInstanceOf(OperatorContext::class);
        expect($operator->capturedContext?->source)->toBe($sources);
        expect($operator->capturedContext?->target)->toBe($aliases);
        expect($operator->capturedContext?->isWildcardMode)->toBeTrue();
        expect($operator->capturedContext?->originalItems)->toBe($items);
    });

    it('passes items through operator', function(): void {
        // Use a simple test operator that doesn't require template evaluation
        $operator = new class implements OperatorInterface {
            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                if (!is_array($config) || !isset($config['status'])) {
                    return $items;
                }

                return array_filter(
                    $items,
                    fn(mixed $item): bool => is_array($item) && ($item['status'] ?? null) === $config['status']
                );
            }

            public function getName(): string
            {
                return 'TEST_FILTER';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $adapted = WildcardOperatorAdapter::adapt($operator);
        $items = [
            ['status' => 'active', 'price' => 10],
            ['status' => 'inactive', 'price' => 20],
            ['status' => 'active', 'price' => 30],
        ];
        $config = ['status' => 'active'];
        $sources = null;
        $aliases = [];

        $result = $adapted($items, $config, $sources, $aliases);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'status'))->toBe(['active', 'active']);
    });

    it('creates context with wildcard mode enabled', function(): void {
        $operator = new class implements OperatorInterface {
            public OperatorContext|null $capturedContext = null;

            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                $this->capturedContext = $context;

                return $items;
            }

            public function getName(): string
            {
                return 'TEST';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $adapted = WildcardOperatorAdapter::adapt($operator);
        $adapted([], null, null, []);

        expect($operator->capturedContext?->isWildcardMode)->toBeTrue();
    });

    it('passes sources to context', function(): void {
        $operator = new class implements OperatorInterface {
            public OperatorContext|null $capturedContext = null;

            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                $this->capturedContext = $context;

                return $items;
            }

            public function getName(): string
            {
                return 'TEST';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $adapted = WildcardOperatorAdapter::adapt($operator);
        $sources = ['key' => 'value', 'nested' => ['data' => 123]];
        $adapted([], null, $sources, []);

        expect($operator->capturedContext?->source)->toBe($sources);
    });

    it('passes aliases to context as target', function(): void {
        $operator = new class implements OperatorInterface {
            public OperatorContext|null $capturedContext = null;

            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                $this->capturedContext = $context;

                return $items;
            }

            public function getName(): string
            {
                return 'TEST';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $adapted = WildcardOperatorAdapter::adapt($operator);
        $aliases = ['alias1' => 'value1', 'alias2' => 'value2'];
        $adapted([], null, null, $aliases);

        expect($operator->capturedContext?->target)->toBe($aliases);
    });

    it('passes original items to context', function(): void {
        $operator = new class implements OperatorInterface {
            public OperatorContext|null $capturedContext = null;

            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                $this->capturedContext = $context;

                return $items;
            }

            public function getName(): string
            {
                return 'TEST';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $adapted = WildcardOperatorAdapter::adapt($operator);
        $items = [['id' => 1], ['id' => 2], ['id' => 3]];
        $adapted($items, null, null, []);

        expect($operator->capturedContext?->originalItems)->toBe($items);
    });

    it('handles empty items array', function(): void {
        $operator = new class implements OperatorInterface {
            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                return $items;
            }

            public function getName(): string
            {
                return 'TEST';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $adapted = WildcardOperatorAdapter::adapt($operator);

        $result = $adapted([], ['status' => 'active'], null, []);

        expect($result)->toBe([]);
    });

    it('handles null config', function(): void {
        $operator = new class implements OperatorInterface {
            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                return $items;
            }

            public function getName(): string
            {
                return 'TEST';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $adapted = WildcardOperatorAdapter::adapt($operator);
        $items = [['id' => 1]];

        $result = $adapted($items, null, null, []);

        expect($result)->toBe($items);
    });

    it('handles null sources', function(): void {
        $operator = new class implements OperatorInterface {
            public OperatorContext|null $capturedContext = null;

            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                $this->capturedContext = $context;

                return $items;
            }

            public function getName(): string
            {
                return 'TEST';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $adapted = WildcardOperatorAdapter::adapt($operator);
        $adapted([], null, null, []);

        expect($operator->capturedContext?->source)->toBeNull();
    });

    it('handles empty aliases array', function(): void {
        $operator = new class implements OperatorInterface {
            public OperatorContext|null $capturedContext = null;

            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                $this->capturedContext = $context;

                return $items;
            }

            public function getName(): string
            {
                return 'TEST';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $adapted = WildcardOperatorAdapter::adapt($operator);
        $adapted([], null, null, []);

        expect($operator->capturedContext?->target)->toBe([]);
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(WildcardOperatorAdapter::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('returns closure that preserves array keys', function(): void {
        $operator = new class implements OperatorInterface {
            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                if (!is_array($config) || !isset($config['status'])) {
                    return $items;
                }

                return array_filter(
                    $items,
                    fn(mixed $item): bool => is_array($item) && ($item['status'] ?? null) === $config['status']
                );
            }

            public function getName(): string
            {
                return 'TEST_FILTER';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $adapted = WildcardOperatorAdapter::adapt($operator);
        $items = [
            'a' => ['status' => 'active'],
            'b' => ['status' => 'inactive'],
            'c' => ['status' => 'active'],
        ];
        $config = ['status' => 'active'];

        $result = $adapted($items, $config, null, []);

        expect(array_keys($result))->toBe(['a', 'c']);
    });
});
