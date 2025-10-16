<?php

declare(strict_types=1);
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;
use event4u\DataHelpers\DataFilter\Operators\OperatorInterface;
use event4u\DataHelpers\DataFilter\Operators\OperatorRegistry;
use event4u\DataHelpers\DataFilter\Operators\OrderByOperator;
use event4u\DataHelpers\DataFilter\Operators\WhereOperator;

describe('OperatorRegistry', function(): void {
    afterEach(function(): void {
        // Clear registry after each test to avoid affecting other tests
        OperatorRegistry::clear();
    });

    it('registers built-in operators automatically', function(): void {
        expect(OperatorRegistry::has('WHERE'))->toBeTrue();
        expect(OperatorRegistry::has('ORDER BY'))->toBeTrue();
        expect(OperatorRegistry::has('LIMIT'))->toBeTrue();
        expect(OperatorRegistry::has('OFFSET'))->toBeTrue();
        expect(OperatorRegistry::has('DISTINCT'))->toBeTrue();
        expect(OperatorRegistry::has('LIKE'))->toBeTrue();
        expect(OperatorRegistry::has('BETWEEN'))->toBeTrue();
        expect(OperatorRegistry::has('NOT BETWEEN'))->toBeTrue();
        expect(OperatorRegistry::has('WHERE IN'))->toBeTrue();
        expect(OperatorRegistry::has('WHERE NOT IN'))->toBeTrue();
        expect(OperatorRegistry::has('WHERE NULL'))->toBeTrue();
        expect(OperatorRegistry::has('WHERE NOT NULL'))->toBeTrue();
    });

    it('registers operator aliases', function(): void {
        expect(OperatorRegistry::has('ORDER'))->toBeTrue(); // Alias for ORDER BY
        expect(OperatorRegistry::has('IN'))->toBeTrue(); // Alias for WHERE IN
        expect(OperatorRegistry::has('NOT IN'))->toBeTrue(); // Alias for WHERE NOT IN
        expect(OperatorRegistry::has('IS NULL'))->toBeTrue(); // Alias for WHERE NULL
        expect(OperatorRegistry::has('IS NOT NULL'))->toBeTrue(); // Alias for WHERE NOT NULL
        expect(OperatorRegistry::has('NOT NULL'))->toBeTrue(); // Alias for WHERE NOT NULL
    });

    it('normalizes operator names to uppercase', function(): void {
        expect(OperatorRegistry::has('where'))->toBeTrue();
        expect(OperatorRegistry::has('Where'))->toBeTrue();
        expect(OperatorRegistry::has('WHERE'))->toBeTrue();
        expect(OperatorRegistry::has('  where  '))->toBeTrue();
    });

    it('gets registered operator', function(): void {
        $operator = OperatorRegistry::get('WHERE');

        expect($operator)->toBeInstanceOf(WhereOperator::class);
        expect($operator->getName())->toBe('WHERE');
    });

    it('gets operator by alias', function(): void {
        $operator = OperatorRegistry::get('ORDER');

        expect($operator)->toBeInstanceOf(OrderByOperator::class);
        expect($operator->getName())->toBe('ORDER BY');
    });

    it('throws exception when getting non-existent operator', function(): void {
        OperatorRegistry::get('NON_EXISTENT');
    })->throws(InvalidArgumentException::class, "Operator 'NON_EXISTENT' is not registered");

    it('returns false for non-existent operator', function(): void {
        expect(OperatorRegistry::has('NON_EXISTENT'))->toBeFalse();
    });

    it('registers custom operator', function(): void {
        $customOperator = new class implements OperatorInterface {
            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                return $items;
            }

            public function getName(): string
            {
                return 'CUSTOM';
            }

            public function getAliases(): array
            {
                return ['CUSTOM_ALIAS'];
            }
        };

        OperatorRegistry::register($customOperator);

        expect(OperatorRegistry::has('CUSTOM'))->toBeTrue();
        expect(OperatorRegistry::has('CUSTOM_ALIAS'))->toBeTrue();
        expect(OperatorRegistry::get('CUSTOM'))->toBe($customOperator);
        expect(OperatorRegistry::get('CUSTOM_ALIAS'))->toBe($customOperator);
    });

    it('registers multiple operators at once', function(): void {
        $operator1 = new class implements OperatorInterface {
            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                return $items;
            }

            public function getName(): string
            {
                return 'CUSTOM1';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $operator2 = new class implements OperatorInterface {
            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                return $items;
            }

            public function getName(): string
            {
                return 'CUSTOM2';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        OperatorRegistry::registerMany([$operator1, $operator2]);

        expect(OperatorRegistry::has('CUSTOM1'))->toBeTrue();
        expect(OperatorRegistry::has('CUSTOM2'))->toBeTrue();
    });

    it('overwrites operator with same name', function(): void {
        $operator1 = new class implements OperatorInterface {
            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                return ['first'];
            }

            public function getName(): string
            {
                return 'CUSTOM';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        $operator2 = new class implements OperatorInterface {
            public function apply(array $items, mixed $config, OperatorContext $context): array
            {
                return ['second'];
            }

            public function getName(): string
            {
                return 'CUSTOM';
            }

            public function getAliases(): array
            {
                return [];
            }
        };

        OperatorRegistry::register($operator1);
        OperatorRegistry::register($operator2);

        $result = OperatorRegistry::get('CUSTOM')->apply([], null, new OperatorContext());

        expect($result)->toBe(['second']);
    });

    it('returns all registered operators', function(): void {
        $all = OperatorRegistry::all();

        expect($all)->toBeArray();
        expect($all)->toHaveKey('WHERE');
        expect($all)->toHaveKey('ORDER BY');
        expect($all)->toHaveKey('ORDER'); // Alias
        expect($all['WHERE'])->toBeInstanceOf(WhereOperator::class);
        expect($all['ORDER BY'])->toBeInstanceOf(OrderByOperator::class);
        expect($all['ORDER'])->toBeInstanceOf(OrderByOperator::class);
    });

    it('clears all operators', function(): void {
        // Ensure operators are registered first
        OperatorRegistry::has('WHERE');
        expect(OperatorRegistry::has('WHERE'))->toBeTrue();

        OperatorRegistry::clear();

        // After clear, has() will auto-register again, so we check the internal state
        $reflection = new ReflectionClass(OperatorRegistry::class);
        $operatorsProperty = $reflection->getProperty('operators');

        $builtInProperty = $reflection->getProperty('builtInRegistered');

        expect($operatorsProperty->getValue())->toBe([]);
        expect($builtInProperty->getValue())->toBeFalse();
    });

    it('re-registers built-in operators after clear', function(): void {
        // Ensure operators are registered first
        OperatorRegistry::has('WHERE');
        expect(OperatorRegistry::has('WHERE'))->toBeTrue();

        OperatorRegistry::clear();

        // Check internal state after clear
        $reflection = new ReflectionClass(OperatorRegistry::class);
        $builtInProperty = $reflection->getProperty('builtInRegistered');

        expect($builtInProperty->getValue())->toBeFalse();

        // Trigger re-registration by calling has()
        $result = OperatorRegistry::has('WHERE');

        expect($result)->toBeTrue();
        expect($builtInProperty->getValue())->toBeTrue();
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(OperatorRegistry::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('registers all 12 built-in operators', function(): void {
        $all = OperatorRegistry::all();
        $uniqueOperators = [];

        foreach ($all as $operator) {
            $uniqueOperators[$operator->getName()] = $operator;
        }

        expect($uniqueOperators)->toHaveCount(12);
        expect($uniqueOperators)->toHaveKey('WHERE');
        expect($uniqueOperators)->toHaveKey('ORDER BY');
        expect($uniqueOperators)->toHaveKey('LIMIT');
        expect($uniqueOperators)->toHaveKey('OFFSET');
        expect($uniqueOperators)->toHaveKey('DISTINCT');
        expect($uniqueOperators)->toHaveKey('LIKE');
        expect($uniqueOperators)->toHaveKey('BETWEEN');
        expect($uniqueOperators)->toHaveKey('NOT BETWEEN');
        expect($uniqueOperators)->toHaveKey('WHERE IN');
        expect($uniqueOperators)->toHaveKey('WHERE NOT IN');
        expect($uniqueOperators)->toHaveKey('WHERE NULL');
        expect($uniqueOperators)->toHaveKey('WHERE NOT NULL');
    });
});

