<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO\Transformers\LowercaseKeysTransformer;
use event4u\DataHelpers\SimpleDTO\Transformers\RemoveNullValuesTransformer;
use event4u\DataHelpers\SimpleDTO\Transformers\TransformerInterface;
use event4u\DataHelpers\SimpleDTO\Transformers\TransformerPipeline;
use event4u\DataHelpers\SimpleDTO\Transformers\TrimStringsTransformer;
use Tests\Unit\SimpleDTO\Fixtures\UserDTO;

describe('Transformers', function(): void {
    describe('TrimStringsTransformer', function(): void {
        it('trims string values', function(): void {
            $transformer = new TrimStringsTransformer();
            $data = [
                'name' => '  John Doe  ',
                'email' => '  john@example.com  ',
            ];

            $result = $transformer->transform($data);

            expect($result['name'])->toBe('John Doe')
                ->and($result['email'])->toBe('john@example.com');
        });

        it('trims nested string values', function(): void {
            $transformer = new TrimStringsTransformer();
            $data = [
                'user' => [
                    'name' => '  Jane  ',
                    'email' => '  jane@example.com  ',
                ],
            ];

            $result = $transformer->transform($data);

            expect($result['user']['name'])->toBe('Jane')
                ->and($result['user']['email'])->toBe('jane@example.com');
        });

        it('preserves non-string values', function(): void {
            $transformer = new TrimStringsTransformer();
            $data = [
                'name' => '  John  ',
                'age' => 30,
                'active' => true,
            ];

            $result = $transformer->transform($data);

            expect($result['name'])->toBe('John')
                ->and($result['age'])->toBe(30)
                ->and($result['active'])->toBeTrue();
        });
    });

    describe('LowercaseKeysTransformer', function(): void {
        it('converts keys to lowercase', function(): void {
            $transformer = new LowercaseKeysTransformer();
            $data = [
                'Name' => 'John',
                'EMAIL' => 'john@example.com',
                'Age' => 30,
            ];

            $result = $transformer->transform($data);

            expect($result)->toHaveKey('name')
                ->and($result)->toHaveKey('email')
                ->and($result)->toHaveKey('age')
                ->and($result['name'])->toBe('John');
        });

        it('converts nested keys to lowercase', function(): void {
            $transformer = new LowercaseKeysTransformer();
            $data = [
                'User' => [
                    'Name' => 'Jane',
                    'EMAIL' => 'jane@example.com',
                ],
            ];

            $result = $transformer->transform($data);

            expect($result)->toHaveKey('user')
                ->and($result['user'])->toHaveKey('name')
                ->and($result['user'])->toHaveKey('email');
        });

        it('preserves numeric keys', function(): void {
            $transformer = new LowercaseKeysTransformer();
            $data = [
                0 => 'first',
                1 => 'second',
                'Name' => 'John',
            ];

            /** @phpstan-ignore-next-line unknown */
            $result = $transformer->transform($data);

            /** @phpstan-ignore-next-line unknown */
            expect($result[0])->toBe('first');
            /** @phpstan-ignore-next-line unknown */
            expect($result[1])->toBe('second');
            expect($result['name'])->toBe('John');
        });
    });

    describe('RemoveNullValuesTransformer', function(): void {
        it('removes null values', function(): void {
            $transformer = new RemoveNullValuesTransformer();
            $data = [
                'name' => 'John',
                'email' => null,
                'age' => 30,
            ];

            $result = $transformer->transform($data);

            expect($result)->toHaveKey('name')
                ->and($result)->toHaveKey('age')
                ->and($result)->not->toHaveKey('email');
        });

        it('removes nested null values', function(): void {
            $transformer = new RemoveNullValuesTransformer();
            $data = [
                'user' => [
                    'name' => 'Jane',
                    'email' => null,
                    'age' => 25,
                ],
            ];

            $result = $transformer->transform($data);

            expect($result['user'])->toHaveKey('name')
                ->and($result['user'])->toHaveKey('age')
                ->and($result['user'])->not->toHaveKey('email');
        });

        it('preserves zero and false values', function(): void {
            $transformer = new RemoveNullValuesTransformer();
            $data = [
                'count' => 0,
                'active' => false,
                'name' => null,
            ];

            $result = $transformer->transform($data);

            expect($result)->toHaveKey('count')
                ->and($result)->toHaveKey('active')
                ->and($result)->not->toHaveKey('name')
                ->and($result['count'])->toBe(0)
                ->and($result['active'])->toBeFalse();
        });
    });

    describe('TransformerPipeline', function(): void {
        it('applies multiple transformers in sequence', function(): void {
            $pipeline = new TransformerPipeline();
            $pipeline->pipe(new TrimStringsTransformer());
            $pipeline->pipe(new LowercaseKeysTransformer());

            $data = [
                'Name' => '  John  ',
                'EMAIL' => '  john@example.com  ',
            ];

            $result = $pipeline->process($data);

            expect($result)->toHaveKey('name')
                ->and($result)->toHaveKey('email')
                ->and($result['name'])->toBe('John')
                ->and($result['email'])->toBe('john@example.com');
        });

        it('applies transformers in correct order', function(): void {
            $pipeline = new TransformerPipeline();
            $pipeline->pipe(new LowercaseKeysTransformer());
            $pipeline->pipe(new RemoveNullValuesTransformer());

            $data = [
                'Name' => 'John',
                'EMAIL' => null,
            ];

            $result = $pipeline->process($data);

            expect($result)->toHaveKey('name')
                ->and($result)->not->toHaveKey('email');
        });

        it('can clear transformers', function(): void {
            $pipeline = new TransformerPipeline();
            $pipeline->pipe(new TrimStringsTransformer());
            $pipeline->clear();

            expect($pipeline->getTransformers())->toBeEmpty();
        });

        it('returns transformers', function(): void {
            $pipeline = new TransformerPipeline();
            $transformer1 = new TrimStringsTransformer();
            $transformer2 = new LowercaseKeysTransformer();

            $pipeline->pipe($transformer1);
            $pipeline->pipe($transformer2);

            $transformers = $pipeline->getTransformers();

            expect($transformers)->toHaveCount(2)
                ->and($transformers[0])->toBe($transformer1)
                ->and($transformers[1])->toBe($transformer2);
        });
    });

    describe('DTO Transformer Integration', function(): void {
        it('transforms DTO with transformWith', function(): void {
            $user = UserDTO::fromArray([
                'name' => '  John Doe  ',
                'age' => 30,
            ]);

            $transformed = $user->transformWith(new TrimStringsTransformer());

            expect($transformed->name)->toBe('John Doe')
                ->and($transformed->age)->toBe(30);
        });

        it('transforms with pipeline', function(): void {
            $user = UserDTO::fromArray([
                'name' => '  John  ',
                'age' => 30,
            ]);

            $pipeline = new TransformerPipeline();
            $pipeline->pipe(new TrimStringsTransformer());

            $transformed = $user->transformWith($pipeline);

            expect($transformed->name)->toBe('John');
        });

        it('transforms before DTO creation', function(): void {
            $data = [
                'name' => '  Jane Doe  ',
                'age' => 25,
            ];

            $user = UserDTO::fromArrayWithTransformer($data, new TrimStringsTransformer());

            expect($user->name)->toBe('Jane Doe')
                ->and($user->age)->toBe(25);
        });

        it('creates new instance when transforming', function(): void {
            $user1 = UserDTO::fromArray(['name' => '  John  ', 'age' => 30]);
            $user2 = $user1->transformWith(new TrimStringsTransformer());

            expect($user1->name)->toBe('  John  ')
                ->and($user2->name)->toBe('John')
                ->and($user1)->not->toBe($user2);
        });
    });

    describe('Custom Transformers', function(): void {
        it('uses custom transformer', function(): void {
            $transformer = new class implements TransformerInterface {
                public function transform(array $data): array
                {
                    if (isset($data['name'])) {
                        $data['name'] = strtoupper((string)$data['name']);
                    }

                    return $data;
                }
            };

            $user = UserDTO::fromArray(['name' => 'john', 'age' => 30]);
            $transformed = $user->transformWith($transformer);

            expect($transformed->name)->toBe('JOHN');
        });

        it('chains multiple custom transformers', function(): void {
            $uppercase = new class implements TransformerInterface {
                public function transform(array $data): array
                {
                    if (isset($data['name'])) {
                        $data['name'] = strtoupper((string)$data['name']);
                    }

                    return $data;
                }
            };

            $addPrefix = new class implements TransformerInterface {
                public function transform(array $data): array
                {
                    if (isset($data['name'])) {
                        /** @phpstan-ignore-next-line unknown */
                        $data['name'] = 'Mr. ' . $data['name'];
                    }

                    return $data;
                }
            };

            $pipeline = new TransformerPipeline();
            $pipeline->pipe($uppercase);
            $pipeline->pipe($addPrefix);

            $user = UserDTO::fromArray(['name' => 'john', 'age' => 30]);
            $transformed = $user->transformWith($pipeline);

            expect($transformed->name)->toBe('Mr. JOHN');
        });
    });
});
