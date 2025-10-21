<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO\Normalizers\CamelCaseNormalizer;
use event4u\DataHelpers\SimpleDTO\Normalizers\DefaultValuesNormalizer;
use event4u\DataHelpers\SimpleDTO\Normalizers\NormalizerInterface;
use event4u\DataHelpers\SimpleDTO\Normalizers\SnakeCaseNormalizer;
use event4u\DataHelpers\SimpleDTO\Normalizers\TypeNormalizer;
use Tests\Unit\SimpleDTO\Fixtures\UserDTO;

describe('Normalizers', function(): void {
    describe('TypeNormalizer', function(): void {
        it('coerces string to int', function(): void {
            $normalizer = new TypeNormalizer(['age' => 'int']);
            $data = ['name' => 'John', 'age' => '30'];

            $result = $normalizer->normalize($data);

            expect($result['age'])->toBe(30)
                ->and($result['age'])->toBeInt();
        });

        it('coerces string to bool', function(): void {
            $normalizer = new TypeNormalizer(['active' => 'bool']);

            $result1 = $normalizer->normalize(['active' => 'true']);
            $result2 = $normalizer->normalize(['active' => 'false']);
            $result3 = $normalizer->normalize(['active' => '1']);
            $result4 = $normalizer->normalize(['active' => '0']);

            expect($result1['active'])->toBeTrue()
                ->and($result2['active'])->toBeFalse()
                ->and($result3['active'])->toBeTrue()
                ->and($result4['active'])->toBeFalse();
        });

        it('coerces int to bool', function(): void {
            $normalizer = new TypeNormalizer(['active' => 'bool']);

            $result1 = $normalizer->normalize(['active' => 1]);
            $result2 = $normalizer->normalize(['active' => 0]);

            expect($result1['active'])->toBeTrue()
                ->and($result2['active'])->toBeFalse();
        });

        it('coerces bool to string', function(): void {
            $normalizer = new TypeNormalizer(['status' => 'string']);

            $result1 = $normalizer->normalize(['status' => true]);
            $result2 = $normalizer->normalize(['status' => false]);

            expect($result1['status'])->toBe('true')
                ->and($result2['status'])->toBe('false');
        });

        it('coerces string to float', function(): void {
            $normalizer = new TypeNormalizer(['price' => 'float']);
            $data = ['price' => '19.99'];

            $result = $normalizer->normalize($data);

            expect($result['price'])->toBe(19.99)
                ->and($result['price'])->toBeFloat();
        });

        it('coerces string to array', function(): void {
            $normalizer = new TypeNormalizer(['tags' => 'array']);
            $data = ['tags' => '["php","laravel"]'];

            $result = $normalizer->normalize($data);

            expect($result['tags'])->toBe(['php', 'laravel'])
                ->and($result['tags'])->toBeArray();
        });

        it('handles multiple fields', function(): void {
            $normalizer = new TypeNormalizer([
                'age' => 'int',
                'active' => 'bool',
                'price' => 'float',
            ]);

            $data = [
                'name' => 'John',
                'age' => '30',
                'active' => '1',
                'price' => '19.99',
            ];

            $result = $normalizer->normalize($data);

            expect($result['age'])->toBe(30)
                ->and($result['active'])->toBeTrue()
                ->and($result['price'])->toBe(19.99);
        });

        it('skips missing fields', function(): void {
            $normalizer = new TypeNormalizer(['age' => 'int']);
            $data = ['name' => 'John'];

            $result = $normalizer->normalize($data);

            expect($result)->toBe(['name' => 'John']);
        });
    });

    describe('DefaultValuesNormalizer', function(): void {
        it('applies default values', function(): void {
            $normalizer = new DefaultValuesNormalizer([
                'age' => 0,
                'active' => true,
            ]);

            $data = ['name' => 'John'];

            $result = $normalizer->normalize($data);

            expect($result['name'])->toBe('John')
                ->and($result['age'])->toBe(0)
                ->and($result['active'])->toBeTrue();
        });

        it('does not override existing values', function(): void {
            $normalizer = new DefaultValuesNormalizer([
                'age' => 0,
                'active' => true,
            ]);

            $data = ['name' => 'John', 'age' => 30];

            $result = $normalizer->normalize($data);

            expect($result['age'])->toBe(30);
        });

        it('does not apply default for null values', function(): void {
            $normalizer = new DefaultValuesNormalizer(['age' => 0]);
            $data = ['name' => 'John', 'age' => null];

            $result = $normalizer->normalize($data);

            // isset() returns false for null, so default is applied
            expect($result['age'])->toBe(0);
        });
    });

    describe('SnakeCaseNormalizer', function(): void {
        it('converts camelCase to snake_case', function(): void {
            $normalizer = new SnakeCaseNormalizer();
            $data = [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'emailAddress' => 'john@example.com',
            ];

            $result = $normalizer->normalize($data);

            expect($result)->toHaveKey('first_name')
                ->and($result)->toHaveKey('last_name')
                ->and($result)->toHaveKey('email_address')
                ->and($result['first_name'])->toBe('John');
        });

        it('converts nested keys', function(): void {
            $normalizer = new SnakeCaseNormalizer();
            $data = [
                'userInfo' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                ],
            ];

            $result = $normalizer->normalize($data);

            expect($result)->toHaveKey('user_info')
                ->and($result['user_info'])->toHaveKey('first_name')
                ->and($result['user_info'])->toHaveKey('last_name');
        });

        it('preserves already snake_case keys', function(): void {
            $normalizer = new SnakeCaseNormalizer();
            $data = ['first_name' => 'John', 'last_name' => 'Doe'];

            $result = $normalizer->normalize($data);

            expect($result)->toHaveKey('first_name')
                ->and($result)->toHaveKey('last_name');
        });

        it('preserves numeric keys', function(): void {
            $normalizer = new SnakeCaseNormalizer();
            $data = [0 => 'first', 1 => 'second', 'firstName' => 'John'];

            /** @phpstan-ignore-next-line unknown */
            $result = $normalizer->normalize($data);

            /** @phpstan-ignore-next-line unknown */
            expect($result[0])->toBe('first');
            /** @phpstan-ignore-next-line unknown */
            expect($result[1])->toBe('second');
            expect($result['first_name'])->toBe('John');
        });
    });

    describe('CamelCaseNormalizer', function(): void {
        it('converts snake_case to camelCase', function(): void {
            $normalizer = new CamelCaseNormalizer();
            $data = [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email_address' => 'john@example.com',
            ];

            $result = $normalizer->normalize($data);

            expect($result)->toHaveKey('firstName')
                ->and($result)->toHaveKey('lastName')
                ->and($result)->toHaveKey('emailAddress')
                ->and($result['firstName'])->toBe('John');
        });

        it('converts nested keys', function(): void {
            $normalizer = new CamelCaseNormalizer();
            $data = [
                'user_info' => [
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                ],
            ];

            $result = $normalizer->normalize($data);

            expect($result)->toHaveKey('userInfo')
                ->and($result['userInfo'])->toHaveKey('firstName')
                ->and($result['userInfo'])->toHaveKey('lastName');
        });

        it('preserves already camelCase keys', function(): void {
            $normalizer = new CamelCaseNormalizer();
            $data = ['firstName' => 'John', 'lastName' => 'Doe'];

            $result = $normalizer->normalize($data);

            expect($result)->toHaveKey('firstName')
                ->and($result)->toHaveKey('lastName');
        });
    });

    describe('DTO Normalizer Integration', function(): void {
        it('normalizes with fromArrayWithNormalizer', function(): void {
            $data = ['name' => 'John', 'age' => '30'];

            $user = UserDTO::fromArrayWithNormalizer($data, new TypeNormalizer(['age' => 'int']));

            expect($user->age)->toBe(30)
                ->and($user->age)->toBeInt();
        });

        it('normalizes with multiple normalizers', function(): void {
            $data = ['name' => 'John'];

            $user = UserDTO::fromArrayWithNormalizers($data, [
                new DefaultValuesNormalizer(['age' => 25]),
                new TypeNormalizer(['age' => 'int']),
            ]);

            expect($user->age)->toBe(25);
        });

        it('normalizes existing DTO', function(): void {
            $user = UserDTO::fromArray(['name' => 'John', 'age' => 30]);

            $normalized = $user->normalizeWith(new TypeNormalizer(['name' => 'string']));

            expect($normalized->name)->toBe('John')
                ->and($normalized->name)->toBeString();
        });

        it('creates new instance when normalizing', function(): void {
            $user1 = UserDTO::fromArray(['name' => 'John', 'age' => 30]);
            $user2 = $user1->normalizeWith(new DefaultValuesNormalizer(['email' => 'default@example.com']));

            expect($user1->age)->toBe(30)
                ->and($user2->age)->toBe(30)
                ->and($user1)->not->toBe($user2);
        });
    });

    describe('Custom Normalizers', function(): void {
        it('uses custom normalizer', function(): void {
            $normalizer = new class implements NormalizerInterface {
                public function normalize(array $data): array
                {
                    if (isset($data['name'])) {
                        $data['name'] = ucwords((string)$data['name']);
                    }

                    return $data;
                }
            };

            $user = UserDTO::fromArrayWithNormalizer(['name' => 'john doe', 'age' => 30], $normalizer);

            expect($user->name)->toBe('John Doe');
        });
    });
});

