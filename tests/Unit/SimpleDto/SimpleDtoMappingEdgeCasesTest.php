<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\MapFrom;
use event4u\DataHelpers\SimpleDto\Attributes\MapInputName;
use event4u\DataHelpers\SimpleDto\Attributes\MapOutputName;
use event4u\DataHelpers\SimpleDto\Attributes\MapTo;
use event4u\DataHelpers\SimpleDto\Support\NameTransformer;

describe('SimpleDto Mapping Edge Cases', function(): void {
    describe('MapFrom Edge Cases', function(): void {
        it('handles empty input arrays', function(): void {
            $dto = new class('') extends SimpleDto {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $userName = 'default',
                ) {
                }
            };

            $instance = $dto::fromArray([]);
            expect($instance->userName)->toBe('default');
        });

        it('handles null values in nested structures', function(): void {
            $dto = new class('') extends SimpleDto {
                public function __construct(
                    #[MapFrom('user.profile.email')]
                    public readonly ?string $email = null,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'user' => [
                    'profile' => [
                        'email' => null,
                    ],
                ],
            ]);

            expect($instance->email)->toBeNull();
        });

        it('handles non-existent keys with fallback', function(): void {
            $dto = new class('') extends SimpleDto {
                public function __construct(
                    #[MapFrom(['primary_email', 'secondary_email', 'email'])]
                    public readonly string $email = 'default@example.com',
                ) {
                }
            };

            $instance = $dto::fromArray([
                'primary_email' => 'primary@example.com',
            ]);

            expect($instance->email)->toBe('primary@example.com');

            $instance = $dto::fromArray([
                'email' => 'fallback@example.com',
            ]);

            expect($instance->email)->toBe('fallback@example.com');
        });

        it('handles all fallback sources missing', function(): void {
            $dto = new class('') extends SimpleDto {
                public function __construct(
                    #[MapFrom(['source1', 'source2', 'source3'])]
                    public readonly string $value = 'default',
                ) {
                }
            };

            $instance = $dto::fromArray([
                'other_key' => 'other_value',
            ]);

            expect($instance->value)->toBe('default');
        });

        it('handles very deep nesting (5+ levels)', function(): void {
            $dto = new class('') extends SimpleDto {
                public function __construct(
                    #[MapFrom('level1.level2.level3.level4.level5.value')]
                    public readonly string $deepValue,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'level4' => [
                                'level5' => [
                                    'value' => 'deep',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            expect($instance->deepValue)->toBe('deep');
        });

        it('handles special characters in keys', function(): void {
            $dto = new class('') extends SimpleDto {
                public function __construct(
                    #[MapFrom('user-name')]
                    public readonly string $userName,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'user-name' => 'John Doe',
            ]);

            expect($instance->userName)->toBe('John Doe');
        });

        it('handles numeric keys', function(): void {
            $dto = new class('') extends SimpleDto {
                public function __construct(
                    #[MapFrom('0')]
                    public readonly string $firstValue,
                ) {
                }
            };

            /** @phpstan-ignore-next-line unknown */
            $instance = $dto::fromArray([
                '0' => 'first',
                '1' => 'second',
            ]);

            expect($instance->firstValue)->toBe('first');
        });

        it('handles missing intermediate levels in nested path', function(): void {
            $dto = new class('') extends SimpleDto {
                public function __construct(
                    #[MapFrom('user.profile.email')]
                    public readonly ?string $email = null,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'user' => [
                    'name' => 'John',
                ],
            ]);

            expect($instance->email)->toBeNull();
        });

        it('handles array values in nested structures', function(): void {
            $dto = new class([]) extends SimpleDto {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    #[MapFrom('user.tags')]
                    public readonly array $tags,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'user' => [
                    'tags' => ['php', 'laravel', 'symfony'],
                ],
            ]);

            expect($instance->tags)->toBe(['php', 'laravel', 'symfony']);
        });

        it('handles empty strings as values', function(): void {
            $dto = new class('') extends SimpleDto {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $userName,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'user_name' => '',
            ]);

            expect($instance->userName)->toBe('');
        });
    });

    describe('MapTo Edge Cases', function(): void {
        it('handles null values in output', function(): void {
            $dto = new class(null) extends SimpleDto {
                public function __construct(
                    #[MapTo('user_email')]
                    public readonly ?string $email,
                ) {
                }
            };

            $output = $dto->toArray();
            expect($output)->toHaveKey('user_email')
                ->and($output['user_email'])->toBeNull();
        });

        it('handles very deep nested output (5+ levels)', function(): void {
            $dto = new class('deep') extends SimpleDto {
                public function __construct(
                    #[MapTo('level1.level2.level3.level4.level5.value')]
                    public readonly string $deepValue,
                ) {
                }
            };

            $output = $dto->toArray();
            expect($output)->toHaveKey('level1')
                ->and($output['level1']['level2']['level3']['level4']['level5']['value'])->toBe('deep');
        });

        it('handles multiple properties mapping to same parent path', function(): void {
            $dto = new class('john@example.com', 'John Doe') extends SimpleDto {
                public function __construct(
                    #[MapTo('user.email')]
                    public readonly string $email,
                    #[MapTo('user.name')]
                    public readonly string $name,
                ) {
                }
            };

            $output = $dto->toArray();
            expect($output)->toHaveKey('user')
                ->and($output['user'])->toHaveKey('email')
                ->and($output['user'])->toHaveKey('name')
                ->and($output['user']['email'])->toBe('john@example.com')
                ->and($output['user']['name'])->toBe('John Doe');
        });

        it('handles empty strings in output', function(): void {
            $dto = new class('') extends SimpleDto {
                public function __construct(
                    #[MapTo('user_name')]
                    public readonly string $userName,
                ) {
                }
            };

            $output = $dto->toArray();
            expect($output)->toHaveKey('user_name')
                ->and($output['user_name'])->toBe('');
        });

        it('handles boolean false vs null in output', function(): void {
            $dto = new class(false, null) extends SimpleDto {
                public function __construct(
                    #[MapTo('is_active')]
                    public readonly bool $isActive,
                    #[MapTo('deleted_at')]
                    public readonly ?string $deletedAt,
                ) {
                }
            };

            $output = $dto->toArray();
            expect($output['is_active'])->toBeFalse()
                ->and($output['deleted_at'])->toBeNull();
        });

        it('handles arrays as values in output', function(): void {
            $dto = new class(['tag1', 'tag2']) extends SimpleDto {
                /** @phpstan-ignore-next-line unknown */
                public function __construct(
                    #[MapTo('user_tags')]
                    public readonly array $tags,
                ) {
                }
            };

            $output = $dto->toArray();
            expect($output)->toHaveKey('user_tags')
                ->and($output['user_tags'])->toBe(['tag1', 'tag2']);
        });

        it('handles zero and empty values correctly', function(): void {
            $dto = new class(0, '', false) extends SimpleDto {
                public function __construct(
                    #[MapTo('count')]
                    public readonly int $count,
                    #[MapTo('name')]
                    public readonly string $name,
                    #[MapTo('active')]
                    public readonly bool $active,
                ) {
                }
            };

            $output = $dto->toArray();
            expect($output['count'])->toBe(0)
                ->and($output['name'])->toBe('')
                ->and($output['active'])->toBeFalse();
        });

        it('handles nested output with conflicting paths', function(): void {
            $dto = new class('value1', 'value2') extends SimpleDto {
                public function __construct(
                    #[MapTo('data.value')]
                    public readonly string $value1,
                    #[MapTo('data.nested.value')]
                    public readonly string $value2,
                ) {
                }
            };

            $output = $dto->toArray();
            expect($output['data']['value'])->toBe('value1')
                ->and($output['data']['nested']['value'])->toBe('value2');
        });
    });

    describe('NameTransformer Edge Cases', function(): void {
        it('handles empty strings', function(): void {
            expect(NameTransformer::toSnakeCase(''))->toBe('')
                ->and(NameTransformer::toCamelCase(''))->toBe('')
                ->and(NameTransformer::toKebabCase(''))->toBe('')
                ->and(NameTransformer::toPascalCase(''))->toBe('');
        });

        it('handles numeric strings', function(): void {
            expect(NameTransformer::toSnakeCase('123'))->toBe('123')
                ->and(NameTransformer::toCamelCase('123'))->toBe('123')
                ->and(NameTransformer::toKebabCase('123'))->toBe('123')
                ->and(NameTransformer::toPascalCase('123'))->toBe('123');
        });

        it('handles consecutive uppercase letters (acronyms)', function(): void {
            expect(NameTransformer::toSnakeCase('HTTPResponse'))->toBe('http_response')
                ->and(NameTransformer::toSnakeCase('XMLParser'))->toBe('xml_parser')
                ->and(NameTransformer::toSnakeCase('URLPath'))->toBe('url_path')
                ->and(NameTransformer::toSnakeCase('APIKey'))->toBe('api_key');
        });

        it('handles numbers in property names', function(): void {
            expect(NameTransformer::toSnakeCase('userId1'))->toBe('user_id1')
                ->and(NameTransformer::toSnakeCase('user2Name'))->toBe('user2name')
                ->and(NameTransformer::toCamelCase('user_id_1'))->toBe('userId1')
                ->and(NameTransformer::toCamelCase('user_2_name'))->toBe('user2Name');
        });

        it('handles single character names', function(): void {
            expect(NameTransformer::toSnakeCase('x'))->toBe('x')
                ->and(NameTransformer::toCamelCase('x'))->toBe('x')
                ->and(NameTransformer::toKebabCase('x'))->toBe('x')
                ->and(NameTransformer::toPascalCase('x'))->toBe('X');
        });

        it('handles already in target format', function(): void {
            expect(NameTransformer::toSnakeCase('user_name'))->toBe('user_name')
                ->and(NameTransformer::toCamelCase('userName'))->toBe('userName')
                ->and(NameTransformer::toKebabCase('user-name'))->toBe('user-name')
                ->and(NameTransformer::toPascalCase('UserName'))->toBe('UserName');
        });

        it('handles very long strings', function(): void {
            $longName = 'thisIsAVeryLongPropertyNameThatShouldStillBeTransformedCorrectly';
            $expected = 'this_is_a_very_long_property_name_that_should_still_be_transformed_correctly';

            expect(NameTransformer::toSnakeCase($longName))->toBe($expected);
        });

        it('handles mixed case with numbers', function(): void {
            expect(NameTransformer::toSnakeCase('user1Name2Email3'))->toBe('user1name2email3')
                ->and(NameTransformer::toCamelCase('user_1_name_2_email_3'))->toBe('user1Name2Email3');
        });

        it('handles special characters gracefully', function(): void {
            expect(NameTransformer::toSnakeCase('user@name'))->toBe('user@name')
                ->and(NameTransformer::toCamelCase('user@name'))->toBe('user@name');
        });

        it('handles multiple consecutive underscores', function(): void {
            expect(NameTransformer::toCamelCase('user___name'))->toBe('userName')
                ->and(NameTransformer::toSnakeCase('user___name'))->toBe('user___name');
        });

        it('handles multiple consecutive hyphens', function(): void {
            expect(NameTransformer::toCamelCase('user---name'))->toBe('userName')
                ->and(NameTransformer::toKebabCase('user---name'))->toBe('user---name');
        });

        it('handles transform method with invalid format', function(): void {
            expect(NameTransformer::transform('userName', 'invalid_format'))->toBe('userName');
        });
    });

    describe('MapInputName/MapOutputName Edge Cases', function(): void {
        it('handles properties with numbers', function(): void {
            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('snake_case')]
            class extends SimpleDto {
                public function __construct(
                    public readonly string $userId1 = '',
                    public readonly string $user2Name = '',
                ) {
                }
            };

            $instance = $dto::fromArray([
                'user_id1' => 'value1',
                'user2name' => 'value2',
            ]);

            expect($instance->userId1)->toBe('value1')
                ->and($instance->user2Name)->toBe('value2');

            $output = $instance->toArray();
            expect($output)->toHaveKey('user_id1')
                ->and($output)->toHaveKey('user2name');
        });

        it('handles properties with acronyms', function(): void {
            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('snake_case')]
            class('', '') extends SimpleDto {
                public function __construct(
                    public readonly string $HTTPResponse,
                    public readonly string $XMLParser,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'http_response' => 'response',
                'xml_parser' => 'parser',
            ]);

            expect($instance->HTTPResponse)->toBe('response')
                ->and($instance->XMLParser)->toBe('parser');

            $output = $instance->toArray();
            expect($output)->toHaveKey('http_response')
                ->and($output)->toHaveKey('xml_parser');
        });

        it('handles single character properties', function(): void {
            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('snake_case')]
            class('', '') extends SimpleDto {
                public function __construct(
                    public readonly string $x,
                    public readonly string $y,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'x' => 'value1',
                'y' => 'value2',
            ]);

            expect($instance->x)->toBe('value1')
                ->and($instance->y)->toBe('value2');

            $output = $instance->toArray();
            expect($output)->toHaveKey('x')
                ->and($output)->toHaveKey('y');
        });

        it('handles empty Dto with transformations', function(): void {
            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('snake_case')]
            class extends SimpleDto
            {
            };

            $instance = $dto::fromArray([]);
            $output = $instance->toArray();

            expect($output)->toBe([]);
        });

        it('handles all properties already in target format', function(): void {
            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('snake_case')]
            class('', '') extends SimpleDto {
                public function __construct(
                    public readonly string $user_name,
                    public readonly string $email_address,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'user_name' => 'John',
                'email_address' => 'john@example.com',
            ]);

            expect($instance->user_name)->toBe('John')
                ->and($instance->email_address)->toBe('john@example.com');
        });

        it('handles mixed mapped and transformed properties', function(): void {
            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('snake_case')]
            class extends SimpleDto {
                public function __construct(
                    #[MapFrom('custom_email')]
                    public readonly string $email = '',
                    public readonly string $userName = '',
                    #[MapFrom('user_age')]
                    public readonly int $age = 0,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'custom_email' => 'john@example.com',
                'user_name' => 'John',
                'user_age' => 30,
            ]);

            expect($instance->email)->toBe('john@example.com')
                ->and($instance->userName)->toBe('John')
                ->and($instance->age)->toBe(30);
        });

        it('handles camelCase to camelCase (no transformation)', function(): void {
            $dto = new #[MapInputName('camelCase')]
            #[MapOutputName('camelCase')]
            class('', '') extends SimpleDto {
                public function __construct(
                    public readonly string $userName,
                    public readonly string $emailAddress,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'userName' => 'John',
                'emailAddress' => 'john@example.com',
            ]);

            expect($instance->userName)->toBe('John')
                ->and($instance->emailAddress)->toBe('john@example.com');

            $output = $instance->toArray();
            expect($output)->toHaveKey('userName')
                ->and($output)->toHaveKey('emailAddress');
        });

        it('handles PascalCase transformation', function(): void {
            $dto = new #[MapInputName('PascalCase')]
            #[MapOutputName('PascalCase')]
            class('', '') extends SimpleDto {
                public function __construct(
                    public readonly string $userName,
                    public readonly string $emailAddress,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'UserName' => 'John',
                'EmailAddress' => 'john@example.com',
            ]);

            expect($instance->userName)->toBe('John')
                ->and($instance->emailAddress)->toBe('john@example.com');

            $output = $instance->toArray();
            expect($output)->toHaveKey('UserName')
                ->and($output)->toHaveKey('EmailAddress');
        });
    });

    describe('Integration Edge Cases', function(): void {
        it('handles all mapping features combined', function(): void {
            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('kebab-case')]
            class('', '', '') extends SimpleDto {
                public function __construct(
                    #[MapFrom(['user.email', 'email'])]
                    #[MapTo('contact.email')]
                    public readonly string $email,
                    #[MapFrom('user_full_name')]
                    public readonly string $userName,
                    public readonly string $phoneNumber,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'email' => 'john@example.com',
                'user_full_name' => 'John Doe',
                'phone_number' => '+49123456789',
            ]);

            expect($instance->email)->toBe('john@example.com')
                ->and($instance->userName)->toBe('John Doe')
                ->and($instance->phoneNumber)->toBe('+49123456789');

            $output = $instance->toArray();
            expect($output['contact']['email'])->toBe('john@example.com')
                ->and($output['user-name'])->toBe('John Doe')
                ->and($output['phone-number'])->toBe('+49123456789');
        });

        it('handles mapping with casts and null values', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    #[MapFrom('is_active')]
                    public readonly ?bool $isActive = null,
                ) {
                }

                /** @return array<string, string> */
                protected function casts(): array
                {
                    return ['isActive' => 'boolean'];
                }
            };

            $instance = $dto::fromArray([
                'is_active' => null,
            ]);

            expect($instance->isActive)->toBeNull();
        });

        it('handles nested Dtos with mapping', function(): void {
            $dto = new class extends SimpleDto {
                public function __construct(
                    #[MapFrom('user_name')]
                    public readonly string $name = '',
                    #[MapFrom('user_address.street_name')]
                    public readonly string $street = '',
                    #[MapFrom('user_address.city_name')]
                    public readonly string $city = '',
                ) {
                }
            };

            $instance = $dto::fromArray([
                'user_name' => 'John Doe',
                'user_address' => [
                    'street_name' => 'Main St',
                    'city_name' => 'Berlin',
                ],
            ]);

            expect($instance->name)->toBe('John Doe')
                ->and($instance->street)->toBe('Main St')
                ->and($instance->city)->toBe('Berlin');
        });

        it('handles nullable properties with mapping and defaults', function(): void {
            $dto = new class('') extends SimpleDto {
                public function __construct(
                    #[MapFrom('user_email')]
                    public readonly ?string $email = null,
                    #[MapFrom('user_name')]
                    public readonly string $name = 'Anonymous',
                ) {
                }
            };

            $instance = $dto::fromArray([]);

            expect($instance->email)->toBeNull()
                ->and($instance->name)->toBe('Anonymous');
        });

        it('handles large Dtos with many properties', function(): void {
            $properties = [];
            for ($i = 1; 50 >= $i; $i++) {
                $properties['field' . $i] = 'value' . $i;
            }

            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('snake_case')]
            class(...array_values($properties)) extends SimpleDto {
                public function __construct(
                    public readonly string $field1,
                    public readonly string $field2,
                    public readonly string $field3,
                    public readonly string $field4,
                    public readonly string $field5,
                    public readonly string $field6,
                    public readonly string $field7,
                    public readonly string $field8,
                    public readonly string $field9,
                    public readonly string $field10,
                    public readonly string $field11,
                    public readonly string $field12,
                    public readonly string $field13,
                    public readonly string $field14,
                    public readonly string $field15,
                    public readonly string $field16,
                    public readonly string $field17,
                    public readonly string $field18,
                    public readonly string $field19,
                    public readonly string $field20,
                    public readonly string $field21,
                    public readonly string $field22,
                    public readonly string $field23,
                    public readonly string $field24,
                    public readonly string $field25,
                    public readonly string $field26,
                    public readonly string $field27,
                    public readonly string $field28,
                    public readonly string $field29,
                    public readonly string $field30,
                    public readonly string $field31,
                    public readonly string $field32,
                    public readonly string $field33,
                    public readonly string $field34,
                    public readonly string $field35,
                    public readonly string $field36,
                    public readonly string $field37,
                    public readonly string $field38,
                    public readonly string $field39,
                    public readonly string $field40,
                    public readonly string $field41,
                    public readonly string $field42,
                    public readonly string $field43,
                    public readonly string $field44,
                    public readonly string $field45,
                    public readonly string $field46,
                    public readonly string $field47,
                    public readonly string $field48,
                    public readonly string $field49,
                    public readonly string $field50,
                ) {
                }
            };

            $instance = $dto::fromArray($properties);
            $output = $instance->toArray();

            expect($instance->field1)->toBe('value1')
                ->and($instance->field50)->toBe('value50')
                ->and($output)->toHaveCount(50);
        });

        it('handles cache behavior with multiple Dto classes', function(): void {
            $dto1 = new #[MapInputName('snake_case')] class('') extends SimpleDto {
                public function __construct(public readonly string $userName) {
                }
            };

            $dto2 = new #[MapInputName('kebab-case')] class('') extends SimpleDto {
                public function __construct(public readonly string $userName) {
                }
            };

            $dto3 = new #[MapInputName('PascalCase')] class('') extends SimpleDto {
                public function __construct(public readonly string $userName) {
                }
            };

            $instance1 = $dto1::fromArray(['user_name' => 'User1']);
            $instance2 = $dto2::fromArray(['user-name' => 'User2']);
            $instance3 = $dto3::fromArray(['UserName' => 'User3']);

            expect($instance1->userName)->toBe('User1')
                ->and($instance2->userName)->toBe('User2')
                ->and($instance3->userName)->toBe('User3');
        });

        it('handles mapping with validation', function(): void {
            /** @phpstan-ignore-next-line unknown */
            $dto = new class extends SimpleDto {
                public function __construct(
                    #[MapFrom('user_email')]
                    public readonly string $email,
                ) {
                }

                protected function rules(): array
                {
                    return [
                        'email' => ['required', 'email'],
                    ];
                }
            };

            $instance = $dto::fromArray([
                'user_email' => 'john@example.com',
            ]);

            expect($instance->email)->toBe('john@example.com');
        })->skip('Laravel Validator not available in unit tests');

        it('handles zero values with mapping', function(): void {
            $dto = new class(0, 0.0, '') extends SimpleDto {
                public function __construct(
                    #[MapFrom('user_count')]
                    #[MapTo('count')]
                    public readonly int $userCount,
                    #[MapFrom('user_balance')]
                    #[MapTo('balance')]
                    public readonly float $userBalance,
                    #[MapFrom('user_name')]
                    #[MapTo('name')]
                    public readonly string $userName,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'user_count' => 0,
                'user_balance' => 0.0,
                'user_name' => '',
            ]);

            expect($instance->userCount)->toBe(0)
                ->and($instance->userBalance)->toBe(0.0)
                ->and($instance->userName)->toBe('');

            $output = $instance->toArray();
            expect($output['count'])->toBe(0)
                ->and($output['balance'])->toBe(0.0)
                ->and($output['name'])->toBe('');
        });

        it('handles boolean false with mapping', function(): void {
            $dto = new class(false) extends SimpleDto {
                public function __construct(
                    #[MapFrom('is_active')]
                    #[MapTo('active')]
                    public readonly bool $isActive,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'is_active' => false,
            ]);

            expect($instance->isActive)->toBeFalse();

            $output = $instance->toArray();
            expect($output['active'])->toBeFalse();
        });

        it('handles very long property names', function(): void {
            $dto = new #[MapInputName('snake_case')]
            #[MapOutputName('snake_case')]
            class('') extends SimpleDto {
                public function __construct(
                    public readonly string $thisIsAVeryLongPropertyNameThatShouldStillWorkCorrectly,
                ) {
                }
            };

            $instance = $dto::fromArray([
                'this_is_a_very_long_property_name_that_should_still_work_correctly' => 'test',
            ]);

            expect($instance->thisIsAVeryLongPropertyNameThatShouldStillWorkCorrectly)->toBe('test');

            $output = $instance->toArray();
            expect($output)->toHaveKey('this_is_a_very_long_property_name_that_should_still_work_correctly');
        });
    });
});
