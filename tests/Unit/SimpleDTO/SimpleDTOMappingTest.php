<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDTO\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDTO\Attributes\MapOutputName;
use event4u\DataHelpers\SimpleDTO\Attributes\MapTo;
use event4u\DataHelpers\SimpleDTO\Support\NameTransformer;

describe('SimpleDTO Mapping', function(): void {
    describe('MapFrom Attribute', function(): void {
        it('maps simple property names', function(): void {
            $dto = new class('') extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $userName,
                ) {
                }
            };

            $result = $dto::fromArray(['user_name' => 'John Doe']);
            expect($result->userName)->toBe('John Doe');
        });

        it('maps multiple properties', function(): void {
            $dto = new class('', '') extends SimpleDTO {
                public function __construct(
                    #[MapFrom('first_name')]
                    public readonly string $firstName,
                    #[MapFrom('last_name')]
                    public readonly string $lastName,
                ) {
                }
            };

            $result = $dto::fromArray([
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]);

            expect($result->firstName)->toBe('John')
                ->and($result->lastName)->toBe('Doe');
        });

        it('handles unmapped properties', function(): void {
            $dto = new class('', '') extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $userName,
                    public readonly string $email,
                ) {
                }
            };

            $result = $dto::fromArray([
                'user_name' => 'John',
                'email' => 'john@example.com',
            ]);

            expect($result->userName)->toBe('John')
                ->and($result->email)->toBe('john@example.com');
        });
    });

    describe('Dot Notation', function(): void {
        it('maps nested properties with dot notation', function(): void {
            $dto = new class('') extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user.email')]
                    public readonly string $email,
                ) {
                }
            };

            $result = $dto::fromArray([
                'user' => [
                    'email' => 'john@example.com',
                ],
            ]);

            expect($result->email)->toBe('john@example.com');
        });

        it('maps deeply nested properties', function(): void {
            $dto = new class('', 0) extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user.profile.email')]
                    public readonly string $email,
                    #[MapFrom('user.profile.age')]
                    public readonly int $age,
                ) {
                }
            };

            $result = $dto::fromArray([
                'user' => [
                    'profile' => [
                        'email' => 'john@example.com',
                        'age' => 30,
                    ],
                ],
            ]);

            expect($result->email)->toBe('john@example.com')
                ->and($result->age)->toBe(30);
        });

        it('returns null for non-existent nested paths', function(): void {
            $dto = new class('') extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user.profile.email')]
                    public readonly ?string $email = null,
                ) {
                }
            };

            $result = $dto::fromArray([
                'user' => [
                    'name' => 'John',
                ],
            ]);

            expect($result->email)->toBeNull();
        });
    });

    describe('Multiple Sources (Fallback)', function(): void {
        it('uses first available source', function(): void {
            $dto = new class('') extends SimpleDTO {
                public function __construct(
                    #[MapFrom(['email', 'email_address', 'mail'])]
                    public readonly string $email,
                ) {
                }
            };

            $result = $dto::fromArray(['email' => 'john@example.com']);
            expect($result->email)->toBe('john@example.com');
        });

        it('falls back to second source if first not found', function(): void {
            $dto = new class('') extends SimpleDTO {
                public function __construct(
                    #[MapFrom(['email', 'email_address', 'mail'])]
                    public readonly string $email,
                ) {
                }
            };

            $result = $dto::fromArray(['email_address' => 'john@example.com']);
            expect($result->email)->toBe('john@example.com');
        });

        it('falls back to third source if first two not found', function(): void {
            $dto = new class('') extends SimpleDTO {
                public function __construct(
                    #[MapFrom(['email', 'email_address', 'mail'])]
                    public readonly string $email,
                ) {
                }
            };

            $result = $dto::fromArray(['mail' => 'john@example.com']);
            expect($result->email)->toBe('john@example.com');
        });

        it('supports dot notation in multiple sources', function(): void {
            $dto = new class('') extends SimpleDTO {
                public function __construct(
                    #[MapFrom(['user.email', 'user.mail', 'email'])]
                    public readonly string $email,
                ) {
                }
            };

            $result = $dto::fromArray([
                'user' => [
                    'mail' => 'john@example.com',
                ],
            ]);

            expect($result->email)->toBe('john@example.com');
        });

        it('handles different API response formats', function(): void {
            $dto = new class('', '') extends SimpleDTO {
                public function __construct(
                    #[MapFrom(['user.email', 'email', 'emailAddress'])]
                    public readonly string $email,
                    #[MapFrom(['user.name', 'name', 'userName'])]
                    public readonly string $name,
                ) {
                }
            };

            // API Format 1: Nested
            $result1 = $dto::fromArray([
                'user' => [
                    'email' => 'john@example.com',
                    'name' => 'John Doe',
                ],
            ]);

            expect($result1->email)->toBe('john@example.com')
                ->and($result1->name)->toBe('John Doe');

            // API Format 2: Flat
            $result2 = $dto::fromArray([
                'email' => 'jane@example.com',
                'name' => 'Jane Doe',
            ]);

            expect($result2->email)->toBe('jane@example.com')
                ->and($result2->name)->toBe('Jane Doe');

            // API Format 3: camelCase
            $result3 = $dto::fromArray([
                'emailAddress' => 'bob@example.com',
                'userName' => 'Bob Smith',
            ]);

            expect($result3->email)->toBe('bob@example.com')
                ->and($result3->name)->toBe('Bob Smith');
        });
    });

    describe('Integration with Casts', function(): void {
        it('applies mapping before casts', function(): void {
            $dto = new class(false) extends SimpleDTO {
                public function __construct(
                    #[MapFrom('is_active')]
                    public readonly bool $active,
                ) {
                }

                protected function casts(): array
                {
                    return [
                        'active' => 'boolean',
                    ];
                }
            };

            $result = $dto::fromArray(['is_active' => '1']);
            expect($result->active)->toBeTrue();
        });

        it('works with datetime casts', function(): void {
            $dto = new class(new DateTimeImmutable()) extends SimpleDTO {
                public function __construct(
                    #[MapFrom('created_at')]
                    public readonly DateTimeImmutable $createdAt,
                ) {
                }

                protected function casts(): array
                {
                    return [
                        'createdAt' => 'datetime',
                    ];
                }
            };

            $result = $dto::fromArray(['created_at' => '2024-01-15 12:00:00']);
            expect($result->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
                ->and($result->createdAt->format('Y-m-d'))->toBe('2024-01-15');
        });
    });

    describe('Mapping Cache', function(): void {
        it('caches mapping configuration', function(): void {
            $dto = new class('') extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $userName,
                ) {
                }
            };

            // First call builds cache
            $config1 = $dto::getMappingConfig();

            // Second call uses cache
            $config2 = $dto::getMappingConfig();

            expect($config1)->toBe($config2)
                ->and($config1)->toHaveKey('userName')
                ->and($config1['userName'])->toBe('user_name');
        });

        it('can clear mapping cache', function(): void {
            $dto = new class('') extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $userName,
                ) {
                }
            };

            $dto::getMappingConfig();
            $dto::clearMappingCache();

            // Cache should be rebuilt
            $config = $dto::getMappingConfig();
            expect($config)->toHaveKey('userName');
        });
    });

    describe('MapTo Attribute', function(): void {
        it('maps simple property names in output', function(): void {
            $dto = new class('John Doe') extends SimpleDTO {
                public function __construct(
                    #[MapTo('user_name')]
                    public readonly string $userName,
                ) {
                }
            };

            $result = $dto->toArray();
            expect($result)->toHaveKey('user_name')
                ->and($result['user_name'])->toBe('John Doe')
                ->and($result)->not->toHaveKey('userName');
        });

        it('maps multiple properties in output', function(): void {
            $dto = new class('John', 'Doe') extends SimpleDTO {
                public function __construct(
                    #[MapTo('first_name')]
                    public readonly string $firstName,
                    #[MapTo('last_name')]
                    public readonly string $lastName,
                ) {
                }
            };

            $result = $dto->toArray();
            expect($result)->toHaveKey('first_name')
                ->and($result)->toHaveKey('last_name')
                ->and($result['first_name'])->toBe('John')
                ->and($result['last_name'])->toBe('Doe');
        });

        it('handles unmapped properties in output', function(): void {
            $dto = new class('John', 'john@example.com') extends SimpleDTO {
                public function __construct(
                    #[MapTo('user_name')]
                    public readonly string $userName,
                    public readonly string $email,
                ) {
                }
            };

            $result = $dto->toArray();
            expect($result)->toHaveKey('user_name')
                ->and($result)->toHaveKey('email')
                ->and($result['user_name'])->toBe('John')
                ->and($result['email'])->toBe('john@example.com');
        });

        it('creates nested output with dot notation', function(): void {
            $dto = new class('john@example.com') extends SimpleDTO {
                public function __construct(
                    #[MapTo('user.email')]
                    public readonly string $email,
                ) {
                }
            };

            $result = $dto->toArray();
            expect($result)->toHaveKey('user')
                ->and($result['user'])->toBeArray()
                ->and($result['user'])->toHaveKey('email')
                ->and($result['user']['email'])->toBe('john@example.com');
        });

        it('creates deeply nested output', function(): void {
            $dto = new class('john@example.com', 30) extends SimpleDTO {
                public function __construct(
                    #[MapTo('user.profile.email')]
                    public readonly string $email,
                    #[MapTo('user.profile.age')]
                    public readonly int $age,
                ) {
                }
            };

            $result = $dto->toArray();
            expect($result)->toHaveKey('user')
                ->and($result['user'])->toHaveKey('profile')
                ->and($result['user']['profile'])->toHaveKey('email')
                ->and($result['user']['profile'])->toHaveKey('age')
                ->and($result['user']['profile']['email'])->toBe('john@example.com')
                ->and($result['user']['profile']['age'])->toBe(30);
        });

        it('works with jsonSerialize', function(): void {
            $dto = new class('John Doe') extends SimpleDTO {
                public function __construct(
                    #[MapTo('user_name')]
                    public readonly string $userName,
                ) {
                }
            };

            $json = json_encode($dto);
            $decoded = json_decode($json, true);

            expect($decoded)->toHaveKey('user_name')
                ->and($decoded['user_name'])->toBe('John Doe')
                ->and($decoded)->not->toHaveKey('userName');
        });
    });

    describe('Bidirectional Mapping', function(): void {
        it('supports both MapFrom and MapTo on same property', function(): void {
            $dto = new class('') extends SimpleDTO {
                public function __construct(
                    #[MapFrom('user_name')]
                    #[MapTo('user_name')]
                    public readonly string $userName,
                ) {
                }
            };

            // Input mapping
            $instance = $dto::fromArray(['user_name' => 'John Doe']);
            expect($instance->userName)->toBe('John Doe');

            // Output mapping
            $output = $instance->toArray();
            expect($output)->toHaveKey('user_name')
                ->and($output['user_name'])->toBe('John Doe')
                ->and($output)->not->toHaveKey('userName');
        });

        it('handles different input and output mappings', function(): void {
            $dto = new class('') extends SimpleDTO {
                public function __construct(
                    #[MapFrom('input_name')]
                    #[MapTo('output_name')]
                    public readonly string $userName,
                ) {
                }
            };

            // Input mapping
            $instance = $dto::fromArray(['input_name' => 'John Doe']);
            expect($instance->userName)->toBe('John Doe');

            // Output mapping
            $output = $instance->toArray();
            expect($output)->toHaveKey('output_name')
                ->and($output['output_name'])->toBe('John Doe')
                ->and($output)->not->toHaveKey('userName')
                ->and($output)->not->toHaveKey('input_name');
        });

        it('supports complex bidirectional mapping', function(): void {
            $dto = new class('', '') extends SimpleDTO {
                public function __construct(
                    #[MapFrom(['user.email', 'email'])]
                    #[MapTo('contact.email')]
                    public readonly string $email,
                    #[MapFrom('user_name')]
                    #[MapTo('contact.name')]
                    public readonly string $name,
                ) {
                }
            };

            // Input: flat structure
            $instance = $dto::fromArray([
                'email' => 'john@example.com',
                'user_name' => 'John Doe',
            ]);

            expect($instance->email)->toBe('john@example.com')
                ->and($instance->name)->toBe('John Doe');

            // Output: nested structure
            $output = $instance->toArray();
            expect($output)->toHaveKey('contact')
                ->and($output['contact'])->toHaveKey('email')
                ->and($output['contact'])->toHaveKey('name')
                ->and($output['contact']['email'])->toBe('john@example.com')
                ->and($output['contact']['name'])->toBe('John Doe');
        });
    });

    describe('NameTransformer', function(): void {
        it('transforms to snake_case', function(): void {
            expect(NameTransformer::toSnakeCase('userName'))->toBe('user_name')
                ->and(NameTransformer::toSnakeCase('emailAddress'))->toBe('email_address')
                ->and(NameTransformer::toSnakeCase('isActive'))->toBe('is_active')
                ->and(NameTransformer::toSnakeCase('HTTPResponse'))->toBe('http_response');
        });

        it('transforms to camelCase', function(): void {
            expect(NameTransformer::toCamelCase('user_name'))->toBe('userName')
                ->and(NameTransformer::toCamelCase('email_address'))->toBe('emailAddress')
                ->and(NameTransformer::toCamelCase('is_active'))->toBe('isActive')
                ->and(NameTransformer::toCamelCase('user-name'))->toBe('userName');
        });

        it('transforms to kebab-case', function(): void {
            expect(NameTransformer::toKebabCase('userName'))->toBe('user-name')
                ->and(NameTransformer::toKebabCase('emailAddress'))->toBe('email-address')
                ->and(NameTransformer::toKebabCase('isActive'))->toBe('is-active')
                ->and(NameTransformer::toKebabCase('user_name'))->toBe('user-name');
        });

        it('transforms to PascalCase', function(): void {
            expect(NameTransformer::toPascalCase('userName'))->toBe('UserName')
                ->and(NameTransformer::toPascalCase('user_name'))->toBe('UserName')
                ->and(NameTransformer::toPascalCase('user-name'))->toBe('UserName')
                ->and(NameTransformer::toPascalCase('isActive'))->toBe('IsActive');
        });

        it('uses transform method with format', function(): void {
            expect(NameTransformer::transform('userName', 'snake_case'))->toBe('user_name')
                ->and(NameTransformer::transform('user_name', 'camelCase'))->toBe('userName')
                ->and(NameTransformer::transform('userName', 'kebab-case'))->toBe('user-name')
                ->and(NameTransformer::transform('userName', 'PascalCase'))->toBe('UserName');
        });
    });

    describe('MapInputName Attribute', function(): void {
        it('transforms all input keys with snake_case', function(): void {
            $dto = new #[MapInputName('snake_case')] class('', '') extends SimpleDTO {
                public function __construct(
                    public readonly string $userName,
                    public readonly string $emailAddress,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'user_name' => 'John Doe',
                'email_address' => 'john@example.com',
            ]);

            expect($instance->userName)->toBe('John Doe')
                ->and($instance->emailAddress)->toBe('john@example.com');
        });

        it('transforms all input keys with kebab-case', function(): void {
            $dto = new #[MapInputName('kebab-case')] class('', '') extends SimpleDTO {
                public function __construct(
                    public readonly string $userName,
                    public readonly string $emailAddress,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'user-name' => 'John Doe',
                'email-address' => 'john@example.com',
            ]);

            expect($instance->userName)->toBe('John Doe')
                ->and($instance->emailAddress)->toBe('john@example.com');
        });

        it('transforms all input keys with PascalCase', function(): void {
            $dto = new #[MapInputName('PascalCase')] class('', '') extends SimpleDTO {
                public function __construct(
                    public readonly string $userName,
                    public readonly string $emailAddress,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'UserName' => 'John Doe',
                'EmailAddress' => 'john@example.com',
            ]);

            expect($instance->userName)->toBe('John Doe')
                ->and($instance->emailAddress)->toBe('john@example.com');
        });

        it('allows MapFrom to override MapInputName', function(): void {
            $dto = new #[MapInputName('snake_case')] class('', '') extends SimpleDTO {
                public function __construct(
                    #[MapFrom('custom_email')]
                    public readonly string $email,
                    public readonly string $userName,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'custom_email' => 'john@example.com',
                'user_name' => 'John Doe',
            ]);

            expect($instance->email)->toBe('john@example.com')
                ->and($instance->userName)->toBe('John Doe');
        });
    });

    describe('MapOutputName Attribute', function(): void {
        it('transforms all output keys with snake_case', function(): void {
            $dto = new #[MapOutputName('snake_case')] class('John Doe', 'john@example.com') extends SimpleDTO {
                public function __construct(
                    public readonly string $userName,
                    public readonly string $emailAddress,
                ) {
                }
            };

            $output = $dto->toArray();
            expect($output)->toHaveKey('user_name')
                ->and($output)->toHaveKey('email_address')
                ->and($output['user_name'])->toBe('John Doe')
                ->and($output['email_address'])->toBe('john@example.com');
        });

        it('transforms all output keys with kebab-case', function(): void {
            $dto = new #[MapOutputName('kebab-case')] class('John Doe', 'john@example.com') extends SimpleDTO {
                public function __construct(
                    public readonly string $userName,
                    public readonly string $emailAddress,
                ) {
                }
            };

            $output = $dto->toArray();
            expect($output)->toHaveKey('user-name')
                ->and($output)->toHaveKey('email-address')
                ->and($output['user-name'])->toBe('John Doe')
                ->and($output['email-address'])->toBe('john@example.com');
        });

        it('transforms all output keys with PascalCase', function(): void {
            $dto = new #[MapOutputName('PascalCase')] class('John Doe', 'john@example.com') extends SimpleDTO {
                public function __construct(
                    public readonly string $userName,
                    public readonly string $emailAddress,
                ) {
                }
            };

            $output = $dto->toArray();
            expect($output)->toHaveKey('UserName')
                ->and($output)->toHaveKey('EmailAddress')
                ->and($output['UserName'])->toBe('John Doe')
                ->and($output['EmailAddress'])->toBe('john@example.com');
        });

        it('allows MapTo to override MapOutputName', function(): void {
            $dto = new #[MapOutputName('snake_case')] class('john@example.com', 'John Doe') extends SimpleDTO {
                public function __construct(
                    #[MapTo('contact_email')]
                    public readonly string $email,
                    public readonly string $userName,
                ) {
                }
            };

            $output = $dto->toArray();
            expect($output)->toHaveKey('contact_email')
                ->and($output)->toHaveKey('user_name')
                ->and($output['contact_email'])->toBe('john@example.com')
                ->and($output['user_name'])->toBe('John Doe');
        });
    });

    describe('Combined MapInputName and MapOutputName', function(): void {
        it('supports different input and output transformations', function(): void {
            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('kebab-case')]
            class('', '') extends SimpleDTO {
                public function __construct(
                    public readonly string $userName,
                    public readonly string $emailAddress,
                ) {
                }
            };

            // Input: snake_case
            $instance = $dto::fromArray([
                'user_name' => 'John Doe',
                'email_address' => 'john@example.com',
            ]);

            expect($instance->userName)->toBe('John Doe')
                ->and($instance->emailAddress)->toBe('john@example.com');

            // Output: kebab-case
            $output = $instance->toArray();
            expect($output)->toHaveKey('user-name')
                ->and($output)->toHaveKey('email-address')
                ->and($output['user-name'])->toBe('John Doe')
                ->and($output['email-address'])->toBe('john@example.com');
        });

        it('combines with MapFrom and MapTo', function(): void {
            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('snake_case')]
            class('', '') extends SimpleDTO {
                public function __construct(
                    #[MapFrom('custom_email')]
                    #[MapTo('output_email')]
                    public readonly string $email,
                    public readonly string $userName,
                ) {
                }
            };

            // Input: MapFrom overrides MapInputName
            $instance = $dto::fromArray([
                'custom_email' => 'john@example.com',
                'user_name' => 'John Doe',
            ]);

            expect($instance->email)->toBe('john@example.com')
                ->and($instance->userName)->toBe('John Doe');

            // Output: MapTo overrides MapOutputName
            $output = $instance->toArray();
            expect($output)->toHaveKey('output_email')
                ->and($output)->toHaveKey('user_name')
                ->and($output['output_email'])->toBe('john@example.com')
                ->and($output['user_name'])->toBe('John Doe');
        });
    });
});

