<?php

declare(strict_types=1);

use event4u\DataHelpers\DataMapper;
use event4u\DataHelpers\DataMapper\DataMapperResult;

describe('FluentDataMapper Edge Cases', function(): void {
    describe('Empty data handling', function(): void {
        it('handles empty source array', function(): void {
            $result = DataMapper::source([])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget())->toBe([]);
        });

        it('handles empty template', function(): void {
            $result = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template([])
                ->map();

            expect($result->getTarget())->toBe([]);
        });

        it('handles empty source and template', function(): void {
            $result = DataMapper::source([])
                ->target([])
                ->template([])
                ->map();

            expect($result->getTarget())->toBe([]);
        });

        it('handles empty nested arrays', function(): void {
            $result = DataMapper::source(['users' => []])
                ->target([])
                ->template(['users' => '{{ users }}'])
                ->map();

            expect($result->getTarget())->toHaveKey('users');
        });
    });

    describe('Null value handling', function(): void {
        it('handles null source', function(): void {
            $result = DataMapper::source(null)
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result)->toBeInstanceOf(DataMapperResult::class);
        });

        it('handles null in source data', function(): void {
            $result = DataMapper::source(['name' => null])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->skipNull(false)
                ->map();

            expect($result->getTarget())->toHaveKey('name');
            expect($result->getTarget()['name'])->toBeNull();
        });

        it('skips null values when skipNull is true', function(): void {
            $result = DataMapper::source(['name' => null, 'age' => 30])
                ->target([])
                ->template(['name' => '{{ name }}', 'age' => '{{ age }}'])
                ->map();

            expect($result->getTarget())->not->toHaveKey('name');
            expect($result->getTarget())->toHaveKey('age');
        });

        it('handles all null values', function(): void {
            $result = DataMapper::source(['a' => null, 'b' => null, 'c' => null])
                ->target([])
                ->template(['a' => '{{ a }}', 'b' => '{{ b }}', 'c' => '{{ c }}'])
                ->map();

            expect($result->getTarget())->toBe([]);
        });
    });

    describe('Whitespace handling', function(): void {
        it('trims values when trimValues is true', function(): void {
            $result = DataMapper::source(['name' => '  John  ', 'city' => "\tBerlin\n"])
                ->target([])
                ->template(['name' => '{{ name }}', 'city' => '{{ city }}'])
                ->trimValues(true)
                ->map();

            expect($result->getTarget()['name'])->toBe('John');
            expect($result->getTarget()['city'])->toBe('Berlin');
        });

        it('preserves whitespace when trimValues is false', function(): void {
            $result = DataMapper::source(['name' => '  John  '])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->trimValues(false)
                ->map();

            expect($result->getTarget()['name'])->toBe('  John  ');
        });

        it('handles empty strings', function(): void {
            $result = DataMapper::source(['name' => ''])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->map();

            expect($result->getTarget())->toHaveKey('name');
            expect($result->getTarget()['name'])->toBe('');
        });

        it('handles whitespace-only strings', function(): void {
            $result = DataMapper::source(['name' => '   '])
                ->target([])
                ->template(['name' => '{{ name }}'])
                ->trimValues(true)
                ->map();

            expect($result->getTarget()['name'])->toBe('');
        });
    });

    describe('Special characters', function(): void {
        it('handles special characters in values', function(): void {
            $result = DataMapper::source(['name' => 'John & Jane', 'email' => 'test@example.com'])
                ->target([])
                ->template(['name' => '{{ name }}', 'email' => '{{ email }}'])
                ->map();

            expect($result->getTarget()['name'])->toBe('John & Jane');
            expect($result->getTarget()['email'])->toBe('test@example.com');
        });

        it('handles unicode characters', function(): void {
            $result = DataMapper::source(['name' => 'Jöhn Döe', 'city' => '北京'])
                ->target([])
                ->template(['name' => '{{ name }}', 'city' => '{{ city }}'])
                ->map();

            expect($result->getTarget()['name'])->toBe('Jöhn Döe');
            expect($result->getTarget()['city'])->toBe('北京');
        });

        it('handles emoji', function(): void {
            $result = DataMapper::source(['message' => 'Hello 👋 World 🌍'])
                ->target([])
                ->template(['message' => '{{ message }}'])
                ->map();

            expect($result->getTarget()['message'])->toBe('Hello 👋 World 🌍');
        });

        it('handles newlines and tabs', function(): void {
            $result = DataMapper::source(['text' => "Line 1\nLine 2\tTabbed"])
                ->target([])
                ->template(['text' => '{{ text }}'])
                ->trimValues(false)
                ->map();

            expect($result->getTarget()['text'])->toBe("Line 1\nLine 2\tTabbed");
        });
    });

    describe('Data type handling', function(): void {
        it('handles boolean values', function(): void {
            $result = DataMapper::source(['active' => true, 'deleted' => false])
                ->target([])
                ->template(['active' => '{{ active }}', 'deleted' => '{{ deleted }}'])
                ->map();

            expect($result->getTarget())->toHaveKey('active');
            expect($result->getTarget())->toHaveKey('deleted');
        });

        it('handles numeric values', function(): void {
            $result = DataMapper::source(['int' => 42, 'float' => 3.14, 'zero' => 0])
                ->target([])
                ->template(['int' => '{{ int }}', 'float' => '{{ float }}', 'zero' => '{{ zero }}'])
                ->map();

            expect($result->getTarget()['int'])->toBe(42);
            expect($result->getTarget()['float'])->toBe(3.14);
            expect($result->getTarget()['zero'])->toBe(0);
        });

        it('handles negative numbers', function(): void {
            $result = DataMapper::source(['negative' => -42, 'negativeFloat' => -3.14])
                ->target([])
                ->template(['negative' => '{{ negative }}', 'negativeFloat' => '{{ negativeFloat }}'])
                ->map();

            expect($result->getTarget()['negative'])->toBe(-42);
            expect($result->getTarget()['negativeFloat'])->toBe(-3.14);
        });

        it('handles very large numbers', function(): void {
            $result = DataMapper::source(['large' => 9999999999999])
                ->target([])
                ->template(['large' => '{{ large }}'])
                ->map();

            expect($result->getTarget()['large'])->toBe(9999999999999);
        });
    });

    describe('Deeply nested structures', function(): void {
        it('handles 10 levels of nesting', function(): void {
            $source = [
                'l1' => [
                    'l2' => [
                        'l3' => [
                            'l4' => [
                                'l5' => [
                                    'l6' => [
                                        'l7' => [
                                            'l8' => [
                                                'l9' => [
                                                    'l10' => 'deep value',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->template(['value' => '{{ l1.l2.l3.l4.l5.l6.l7.l8.l9.l10 }}'])
                ->map();

            expect($result->getTarget()['value'])->toBe('deep value');
        });

        it('handles mixed nesting with arrays and objects', function(): void {
            $source = [
                'company' => [
                    'departments' => [
                        [
                            'name' => 'IT',
                            'teams' => [
                                ['name' => 'Backend', 'members' => [['name' => 'John']]],
                            ],
                        ],
                    ],
                ],
            ];

            $result = DataMapper::source($source)
                ->target([])
                ->template(['teams' => '{{ company.departments.*.teams }}'])
                ->map();

            expect($result->getTarget())->toHaveKey('teams');
        });
    });

    describe('Large data sets', function(): void {
        it('handles 1000 items', function(): void {
            $items = array_map(fn($i): array => ['id' => $i, 'name' => 'Item ' . $i], range(1, 1000));

            $result = DataMapper::source(['items' => $items])
                ->target([])
                ->template(['items' => '{{ items }}'])
                ->map();

            expect($result->getTarget()['items'])->toHaveCount(1000);
        });

        it('handles 100 fields in template', function(): void {
            $source = array_combine(
                array_map(fn($i): string => 'field' . $i, range(1, 100)),
                array_map(fn($i): string => 'value' . $i, range(1, 100))
            );

            $template = array_combine(
                array_map(fn($i): string => 'field' . $i, range(1, 100)),
                array_map(fn($i): string => sprintf('{{ field%s }}', $i), range(1, 100))
            );

            $result = DataMapper::source($source)
                ->target([])
                ->template($template)
                ->map();

            expect($result->getTarget())->toHaveCount(100);
        });
    });

    describe('Wildcard edge cases', function(): void {
        it('handles empty wildcard array', function(): void {
            $result = DataMapper::source(['items' => []])
                ->target([])
                ->template(['names' => '{{ items.*.name }}'])
                ->map();

            expect($result->getTarget())->toHaveKey('names');
        });

        it('handles wildcard with missing keys', function(): void {
            $result = DataMapper::source([
                'items' => [
                    ['name' => 'John'],
                    ['age' => 30],  // missing 'name'
                    ['name' => 'Jane'],
                ],
            ])
                ->target([])
                ->template(['names' => '{{ items.*.name }}'])
                ->map();

            expect($result->getTarget())->toHaveKey('names');
        });

        it('handles nested wildcards', function(): void {
            $result = DataMapper::source([
                'departments' => [
                    ['users' => [['name' => 'John'], ['name' => 'Jane']]],
                    ['users' => [['name' => 'Bob']]],
                ],
            ])
                ->target([])
                ->template(['users' => '{{ departments.*.users }}'])
                ->map();

            expect($result->getTarget())->toHaveKey('users');
        });
    });

    describe('Copy edge cases', function(): void {
        it('copy is truly independent', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}']);

            $copy = $mapper->copy();

            // Modify copy
            $copy->template(['different' => '{{ name }}']);

            // Original should be unchanged
            $result1 = $mapper->map();
            $result2 = $copy->map();

            expect($result1->getTarget())->toHaveKey('name');
            expect($result1->getTarget())->not->toHaveKey('different');
            expect($result2->getTarget())->toHaveKey('different');
            expect($result2->getTarget())->not->toHaveKey('name');
        });

        it('can copy multiple times', function(): void {
            $mapper = DataMapper::source(['name' => 'John']);

            $copy1 = $mapper->copy();
            $copy2 = $mapper->copy();
            $copy3 = $copy1->copy();

            expect($copy1)->not->toBe($mapper);
            expect($copy2)->not->toBe($mapper);
            expect($copy3)->not->toBe($copy1);
            expect($copy1)->not->toBe($copy2);
        });
    });

    describe('Multiple mapping calls', function(): void {
        it('can call map() multiple times', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['name' => '{{ name }}']);

            $result1 = $mapper->map();
            $result2 = $mapper->map();
            $result3 = $mapper->map();

            expect($result1->getTarget())->toEqual($result2->getTarget());
            expect($result2->getTarget())->toEqual($result3->getTarget());
        });

        it('can alternate between map() and reverseMap()', function(): void {
            $mapper = DataMapper::source(['name' => 'John'])
                ->target([])
                ->template(['userName' => '{{ name }}']);

            $result1 = $mapper->map();
            $result2 = $mapper->reverseMap();
            $result3 = $mapper->map();

            expect($result1)->toBeInstanceOf(DataMapperResult::class);
            expect($result2)->toBeInstanceOf(DataMapperResult::class);
            expect($result3)->toBeInstanceOf(DataMapperResult::class);
        });
    });

    describe('Configuration changes after creation', function(): void {
        it('can change configuration between mappings', function(): void {
            $mapper = DataMapper::source(['name' => 'John', 'age' => null])
                ->target([])
                ->template(['name' => '{{ name }}', 'age' => '{{ age }}']);

            $result1 = $mapper->skipNull(true)->map();
            $result2 = $mapper->skipNull(false)->map();

            expect($result1->getTarget())->not->toHaveKey('age');
            expect($result2->getTarget())->toHaveKey('age');
        });

        it('can change template between mappings', function(): void {
            $mapper = DataMapper::source(['name' => 'John', 'age' => 30])
                ->target([]);

            $result1 = $mapper->template(['name' => '{{ name }}'])->map();
            $result2 = $mapper->template(['age' => '{{ age }}'])->map();

            expect($result1->getTarget())->toHaveKey('name');
            expect($result1->getTarget())->not->toHaveKey('age');
            expect($result2->getTarget())->toHaveKey('age');
            expect($result2->getTarget())->not->toHaveKey('name');
        });
    });
});
