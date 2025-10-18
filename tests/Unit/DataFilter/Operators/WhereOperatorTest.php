<?php

declare(strict_types=1);

use event4u\DataHelpers\DataFilter\Operators\AbstractOperator;
use event4u\DataHelpers\DataFilter\Operators\OperatorContext;
use event4u\DataHelpers\DataFilter\Operators\WhereOperator;

describe('WhereOperator', function(): void {
    it('returns correct name', function(): void {
        $operator = new WhereOperator();

        expect($operator->getName())->toBe('WHERE');
    });

    it('filters with simple equality', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['id' => 1, 'status' => 'active'],
            ['id' => 2, 'status' => 'inactive'],
            ['id' => 3, 'status' => 'active'],
        ];
        $config = ['status' => 'active'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'id'))->toBe([1, 3]);
    });

    it('filters with equals operator', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['price' => 10],
            ['price' => 20],
            ['price' => 30],
        ];
        $config = ['price' => ['=', 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[1]['price'])->toBe(20);
    });

    it('filters with not equals operator', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['status' => 'active'],
            ['status' => 'inactive'],
            ['status' => 'pending'],
        ];
        $config = ['status' => ['!=', 'active']];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'status'))->toBe(['inactive', 'pending']);
    });

    it('filters with greater than operator', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['price' => 10],
            ['price' => 20],
            ['price' => 30],
        ];
        $config = ['price' => ['>', 15]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'price'))->toBe([20, 30]);
    });

    it('filters with greater than or equal operator', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['price' => 10],
            ['price' => 20],
            ['price' => 30],
        ];
        $config = ['price' => ['>=', 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'price'))->toBe([20, 30]);
    });

    it('filters with less than operator', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['price' => 10],
            ['price' => 20],
            ['price' => 30],
        ];
        $config = ['price' => ['<', 25]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'price'))->toBe([10, 20]);
    });

    it('filters with less than or equal operator', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['price' => 10],
            ['price' => 20],
            ['price' => 30],
        ];
        $config = ['price' => ['<=', 20]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'price'))->toBe([10, 20]);
    });

    it('filters with IN operator', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
        ];
        $config = ['id' => ['IN', [1, 3]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'id'))->toBe([1, 3]);
    });

    it('filters with NOT IN operator', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
        ];
        $config = ['id' => ['NOT IN', [1, 3]]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'id'))->toBe([2, 4]);
    });

    it('filters with implicit AND condition', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['status' => 'active', 'price' => 10],
            ['status' => 'active', 'price' => 20],
            ['status' => 'inactive', 'price' => 10],
        ];
        $config = ['status' => 'active', 'price' => 10];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0])->toBe(['status' => 'active', 'price' => 10]);
    });

    it('filters with explicit AND condition', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['status' => 'active', 'price' => 10],
            ['status' => 'active', 'price' => 20],
            ['status' => 'inactive', 'price' => 10],
        ];
        $config = ['AND' => ['status' => 'active', 'price' => 10]];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(1);
        expect($result[0])->toBe(['status' => 'active', 'price' => 10]);
    });

    it('filters with OR condition', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['status' => 'active', 'price' => 10],
            ['status' => 'inactive', 'price' => 20],
            ['status' => 'pending', 'price' => 30],
        ];
        $config = [
            'OR' => [
                ['status' => 'active'],
                ['status' => 'pending'],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_column($result, 'status'))->toBe(['active', 'pending']);
    });

    it('filters with nested AND within OR', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['status' => 'active', 'price' => 10],
            ['status' => 'active', 'price' => 20],
            ['status' => 'inactive', 'price' => 10],
            ['status' => 'inactive', 'price' => 20],
        ];
        $config = [
            'OR' => [
                ['status' => 'active', 'price' => 10],
                ['status' => 'inactive', 'price' => 20],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('filters with complex nested conditions', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['status' => 'active', 'price' => 10],
            ['status' => 'inactive', 'price' => 20],
            ['status' => 'pending', 'price' => 30],
        ];
        $config = [
            'OR' => [
                ['status' => 'active', 'price' => ['>', 5]],
                ['status' => 'inactive', 'price' => ['<', 25]],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        // (status='active' AND price>5) OR (status='inactive' AND price<25)
        expect($result)->toHaveCount(2);
    });

    it('filters with AND and nested OR correctly', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['status' => 'active', 'price' => 10],
            ['status' => 'active', 'price' => 20],
            ['status' => 'active', 'price' => 30],
            ['status' => 'inactive', 'price' => 10],
        ];
        $config = [
            'status' => 'active',
            'OR' => [
                ['price' => 10],
                ['price' => 20],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        // status='active' AND (price=10 OR price=20)
        expect($result)->toHaveCount(2);
        expect(array_column($result, 'price'))->toBe([10, 20]);
    });

    it('handles empty array', function(): void {
        $operator = new WhereOperator();
        $items = [];
        $config = ['status' => 'active'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('returns empty when no items match', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['status' => 'active'],
            ['status' => 'active'],
        ];
        $config = ['status' => 'inactive'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toBeEmpty();
    });

    it('preserves array keys', function(): void {
        $operator = new WhereOperator();
        $items = [
            10 => ['status' => 'active'],
            20 => ['status' => 'inactive'],
            30 => ['status' => 'active'],
        ];
        $config = ['status' => 'active'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
        expect(array_keys($result))->toBe([10, 30]);
    });

    it('returns original items when config is not array', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['status' => 'active'],
            ['status' => 'inactive'],
        ];
        $config = 'invalid';
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('handles nested field paths', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['user' => ['status' => 'active']],
            ['user' => ['status' => 'inactive']],
            ['user' => ['status' => 'active']],
        ];
        $config = ['user.status' => 'active'];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(2);
    });

    it('is final class', function(): void {
        $reflection = new ReflectionClass(WhereOperator::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    it('extends AbstractOperator', function(): void {
        $operator = new WhereOperator();

        expect($operator)->toBeInstanceOf(AbstractOperator::class);
    });

    it('handles deep nesting level 3 - OR(AND(OR))', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['a' => 1, 'b' => 2, 'c' => 4],
            ['a' => 1, 'b' => 3, 'c' => 3],
            ['a' => 2, 'b' => 2, 'c' => 3],
        ];
        $config = [
            'OR' => [
                [
                    'a' => 1,
                    'OR' => [
                        ['b' => 2, 'c' => 3],
                        ['b' => 3, 'c' => 3],
                    ],
                ],
                ['a' => 2, 'b' => 2],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        // (a=1 AND ((b=2 AND c=3) OR (b=3 AND c=3))) OR (a=2 AND b=2)
        expect($result)->toHaveCount(3);
    });

    it('handles deep nesting level 4 - AND(OR(AND(OR)))', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4],
            ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 5],
            ['a' => 1, 'b' => 3, 'c' => 4, 'd' => 4],
            ['a' => 1, 'b' => 3, 'c' => 4, 'd' => 5],
            ['a' => 2, 'b' => 2, 'c' => 3, 'd' => 4],
        ];
        $config = [
            'a' => 1,
            'OR' => [
                [
                    'b' => 2,
                    'OR' => [
                        ['c' => 3, 'd' => 4],
                        ['c' => 3, 'd' => 5],
                    ],
                ],
                [
                    'b' => 3,
                    'c' => 4,
                ],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        // a=1 AND ((b=2 AND ((c=3 AND d=4) OR (c=3 AND d=5))) OR (b=3 AND c=4))
        expect($result)->toHaveCount(4);
    });

    it('handles deep nesting level 5 - OR(AND(OR(AND(OR))))', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5],
            ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 6],
            ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 5, 'e' => 5],
            ['a' => 1, 'b' => 3, 'c' => 4, 'd' => 4, 'e' => 5],
            ['a' => 2, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5],
        ];
        $config = [
            'OR' => [
                [
                    'a' => 1,
                    'OR' => [
                        [
                            'b' => 2,
                            'c' => 3,
                            'OR' => [
                                ['d' => 4, 'e' => 5],
                                ['d' => 4, 'e' => 6],
                            ],
                        ],
                        ['b' => 3, 'c' => 4],
                    ],
                ],
                ['a' => 2, 'b' => 2],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(4);
    });

    it('handles deep nesting level 6', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6],
            ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 7],
            ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 6, 'f' => 6],
            ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 5, 'e' => 5, 'f' => 6],
            ['a' => 2, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6],
        ];
        $config = [
            'a' => 1,
            'OR' => [
                [
                    'b' => 2,
                    'OR' => [
                        [
                            'c' => 3,
                            'd' => 4,
                            'OR' => [
                                [
                                    'e' => 5,
                                    'OR' => [
                                        ['f' => 6],
                                        ['f' => 7],
                                    ],
                                ],
                                ['e' => 6, 'f' => 6],
                            ],
                        ],
                        ['c' => 3, 'd' => 5],
                    ],
                ],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(4);
    });

    it('handles deep nesting level 7 with alternating AND/OR', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 8],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 7, 'l7' => 7],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 6, 'l6' => 6, 'l7' => 7],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 5, 'l5' => 5, 'l6' => 6, 'l7' => 7],
            ['l1' => 1, 'l2' => 2, 'l3' => 4, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7],
            ['l1' => 1, 'l2' => 3, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7],
            ['l1' => 2, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7],
        ];
        $config = [
            'l1' => 1,
            'OR' => [
                [
                    'l2' => 2,
                    'OR' => [
                        [
                            'l3' => 3,
                            'OR' => [
                                [
                                    'l4' => 4,
                                    'OR' => [
                                        [
                                            'l5' => 5,
                                            'OR' => [
                                                [
                                                    'l6' => 6,
                                                    'OR' => [
                                                        ['l7' => 7],
                                                        ['l7' => 8],
                                                    ],
                                                ],
                                                ['l6' => 7],
                                            ],
                                        ],
                                        ['l5' => 6],
                                    ],
                                ],
                                ['l4' => 5],
                            ],
                        ],
                        ['l3' => 4],
                    ],
                ],
                ['l2' => 3],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(7);
    });

    it('handles deep nesting level 8 with complex conditions', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7, 'l8' => 8],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7, 'l8' => 9],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 8, 'l8' => 8],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 7, 'l7' => 7, 'l8' => 8],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 6, 'l6' => 6, 'l7' => 7, 'l8' => 8],
        ];
        $config = [
            'l1' => 1,
            'l2' => 2,
            'OR' => [
                [
                    'l3' => 3,
                    'l4' => 4,
                    'OR' => [
                        [
                            'l5' => 5,
                            'OR' => [
                                [
                                    'l6' => 6,
                                    'OR' => [
                                        [
                                            'l7' => 7,
                                            'OR' => [
                                                ['l8' => 8],
                                                ['l8' => 9],
                                            ],
                                        ],
                                        ['l7' => 8],
                                    ],
                                ],
                                ['l6' => 7],
                            ],
                        ],
                        ['l5' => 6],
                    ],
                ],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(5);
    });

    it('handles deep nesting level 9 with mixed operators', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7, 'l8' => 8, 'l9' => 9],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7, 'l8' => 8, 'l9' => 10],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7, 'l8' => 9, 'l9' => 9],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 8, 'l8' => 8, 'l9' => 9],
        ];
        $config = [
            'l1' => 1,
            'l2' => 2,
            'l3' => 3,
            'OR' => [
                [
                    'l4' => 4,
                    'l5' => 5,
                    'OR' => [
                        [
                            'l6' => 6,
                            'OR' => [
                                [
                                    'l7' => 7,
                                    'OR' => [
                                        [
                                            'l8' => 8,
                                            'OR' => [
                                                ['l9' => 9],
                                                ['l9' => 10],
                                            ],
                                        ],
                                        ['l8' => 9],
                                    ],
                                ],
                                ['l7' => 8],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(4);
    });

    it('handles deep nesting level 10 with maximum complexity', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7, 'l8' => 8, 'l9' => 9, 'l10' => 10],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7, 'l8' => 8, 'l9' => 9, 'l10' => 11],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7, 'l8' => 8, 'l9' => 10, 'l10' => 10],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 7, 'l8' => 9, 'l9' => 9, 'l10' => 10],
            ['l1' => 1, 'l2' => 2, 'l3' => 3, 'l4' => 4, 'l5' => 5, 'l6' => 6, 'l7' => 8, 'l8' => 8, 'l9' => 9, 'l10' => 10],
        ];
        $config = [
            'l1' => 1,
            'l2' => 2,
            'l3' => 3,
            'l4' => 4,
            'OR' => [
                [
                    'l5' => 5,
                    'OR' => [
                        [
                            'l6' => 6,
                            'OR' => [
                                [
                                    'l7' => 7,
                                    'OR' => [
                                        [
                                            'l8' => 8,
                                            'OR' => [
                                                [
                                                    'l9' => 9,
                                                    'OR' => [
                                                        ['l10' => 10],
                                                        ['l10' => 11],
                                                    ],
                                                ],
                                                ['l9' => 10],
                                            ],
                                        ],
                                        ['l8' => 9],
                                    ],
                                ],
                                ['l7' => 8],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(5);
    });

    it('handles deep nesting with multiple AND branches in OR', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['type' => 'A', 'status' => 'active', 'priority' => 'high', 'score' => 100],
            ['type' => 'A', 'status' => 'active', 'priority' => 'low', 'score' => 50],
            ['type' => 'A', 'status' => 'inactive', 'priority' => 'high', 'score' => 100],
            ['type' => 'B', 'status' => 'active', 'priority' => 'high', 'score' => 100],
            ['type' => 'B', 'status' => 'pending', 'priority' => 'medium', 'score' => 75],
            ['type' => 'C', 'status' => 'active', 'priority' => 'high', 'score' => 100],
        ];
        $config = [
            'OR' => [
                [
                    'type' => 'A',
                    'status' => 'active',
                    'OR' => [
                        ['priority' => 'high', 'score' => 100],
                        ['priority' => 'low', 'score' => 50],
                    ],
                ],
                [
                    'type' => 'B',
                    'OR' => [
                        ['status' => 'active', 'priority' => 'high'],
                        ['status' => 'pending', 'priority' => 'medium'],
                    ],
                ],
                ['type' => 'C', 'status' => 'active'],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(5);
    });

    it('handles deep nesting with comparison operators', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['a' => 10, 'b' => 20, 'c' => 30, 'd' => 40],
            ['a' => 10, 'b' => 20, 'c' => 30, 'd' => 50],
            ['a' => 10, 'b' => 20, 'c' => 35, 'd' => 40],
            ['a' => 10, 'b' => 25, 'c' => 30, 'd' => 40],
            ['a' => 15, 'b' => 20, 'c' => 30, 'd' => 40],
        ];
        $config = [
            'a' => ['>=', 10],
            'OR' => [
                [
                    'b' => ['<', 25],
                    'OR' => [
                        [
                            'c' => ['<=', 30],
                            'OR' => [
                                ['d' => ['=', 40]],
                                ['d' => ['>', 45]],
                            ],
                        ],
                        ['c' => ['>', 30]],
                    ],
                ],
                ['b' => ['>=', 25]],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(5);
    });

    it('handles deep nesting with IN and NOT IN operators', function(): void {
        $operator = new WhereOperator();
        $items = [
            ['category' => 'A', 'status' => 'active', 'priority' => 1, 'tag' => 'x'],
            ['category' => 'A', 'status' => 'active', 'priority' => 2, 'tag' => 'y'],
            ['category' => 'A', 'status' => 'inactive', 'priority' => 1, 'tag' => 'x'],
            ['category' => 'B', 'status' => 'active', 'priority' => 1, 'tag' => 'x'],
            ['category' => 'C', 'status' => 'pending', 'priority' => 3, 'tag' => 'z'],
        ];
        $config = [
            'category' => ['IN', ['A', 'B']],
            'OR' => [
                [
                    'status' => ['IN', ['active', 'pending']],
                    'OR' => [
                        [
                            'priority' => ['IN', [1, 2]],
                            'tag' => ['NOT IN', ['z']],
                        ],
                        ['priority' => ['NOT IN', [1, 2, 3]]],
                    ],
                ],
                ['status' => ['NOT IN', ['active', 'inactive']]],
            ],
        ];
        $context = new OperatorContext(null, null, false, [], []);

        $result = $operator->apply($items, $config, $context);

        expect($result)->toHaveCount(3);
    });
});

