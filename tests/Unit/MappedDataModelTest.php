<?php

declare(strict_types=1);

namespace Tests\Unit;

use event4u\DataHelpers\DataMapper\Pipeline\Filters\LowercaseEmails;
use event4u\DataHelpers\DataMapper\Pipeline\Filters\TrimStrings;
use event4u\DataHelpers\MappedDataModel;
use JsonSerializable;

/**
 * Test implementation
 *
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property int $age
 * @property bool $is_active
 * @property array{value: string} $nested
 */
class TestDataModel extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'email' => '{{ request.email }}',
            'first_name' => '{{ request.first_name }}',
            'last_name' => '{{ request.last_name }}',
            'age' => '{{ request.age }}',
            'is_active' => true,
            'nested' => [
                'value' => '{{ request.nested_value }}',
            ],
        ];
    }
}

/**
 * Test model with pipeline transformers
 *
 * @property string $email
 * @property string $name
 */
class TestDataModelWithPipeline extends MappedDataModel
{
    protected function template(): array
    {
        return [
            'email' => '{{ request.email }}',
            'name' => '{{ request.name }}',
        ];
    }

    protected function pipes(): array
    {
        return [
            new TrimStrings(),
            new LowercaseEmails(),
        ];
    }
}

describe('MappedDataModel', function(): void {
//    beforeEach(function (): void {
//        //MapperExceptions::reset();
//    });
//    afterEach(function (): void {
//        //MapperExceptions::reset();
//    });

    describe('Basic functionality', function(): void {
//        beforeEach(function (): void {
//            //MapperExceptions::reset();
//        });
//        afterEach(function (): void {
//            //MapperExceptions::reset();
//        });

        test('it creates instance with data', function(): void {
            $data = [
                'email' => 'alice@example.com',
                'first_name' => 'Alice',
                'last_name' => 'Smith',
                'age' => 30,
                'nested_value' => 'test',
            ];

            $model = new TestDataModel($data);

            expect($model->isMapped())->toBeTrue();
            expect($model->email)->toBe('alice@example.com');
            expect($model->first_name)->toBe('Alice');
            expect($model->last_name)->toBe('Smith');
            expect($model->age)->toBe(30);
            expect($model->is_active)->toBeTrue();
        });

        test('it creates empty instance', function(): void {
            $model = new TestDataModel();

            expect($model->isMapped())->toBeFalse();
            expect($model->toArray())->toBe([]);
        });

        test('it fills data after creation', function(): void {
            $model = new TestDataModel();
            $model->fill([
                'email' => 'test@example.com',
                'first_name' => 'Test',
                'last_name' => 'User',
                'age' => 25,
            ]);

            expect($model->isMapped())->toBeTrue();
            expect($model->email)->toBe('test@example.com');
            expect($model->first_name)->toBe('Test');
        });
    });

    describe('Data access', function(): void {
        test('it gets mapped values', function(): void {
            $model = new TestDataModel([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 30,
            ]);

            expect($model->get('email'))->toBe('test@example.com');
            expect($model->get('first_name'))->toBe('John');
            expect($model->get('nonexistent', 'default'))->toBe('default');
        });

        test('it gets original values', function(): void {
            $model = new TestDataModel([
                'email' => 'original@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 30,
            ]);

            expect($model->getOriginal('email'))->toBe('original@example.com');
            expect($model->getOriginal('age'))->toBe(30);
            expect($model->getOriginal('nonexistent', 'default'))->toBe('default');
        });

        test('it checks if mapped field exists', function(): void {
            $model = new TestDataModel([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 30,
            ]);

            expect($model->has('email'))->toBeTrue();
            expect($model->has('first_name'))->toBeTrue();
            expect($model->has('nonexistent'))->toBeFalse();
        });

        test('it checks if original field exists', function(): void {
            $model = new TestDataModel([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 30,
            ]);

            expect($model->hasOriginal('email'))->toBeTrue();
            expect($model->hasOriginal('first_name'))->toBeTrue();
            expect($model->hasOriginal('nonexistent'))->toBeFalse();
        });

        test('it uses magic getter', function(): void {
            $model = new TestDataModel([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 30,
            ]);

            expect($model->email)->toBe('test@example.com');
            expect($model->first_name)->toBe('John');
            expect($model->age)->toBe(30);
        });

        test('it uses magic isset', function(): void {
            $model = new TestDataModel([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 30,
            ]);

            expect(isset($model->email))->toBeTrue();
            expect(isset($model->first_name))->toBeTrue();
            expect(isset($model->nonexistent))->toBeFalse();
        });
    });

    describe('Serialization', function(): void {
        test('it converts to array with only mapped values', function(): void {
            $model = new TestDataModel([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 30,
                'extra_field' => 'should not appear',
            ]);

            $array = $model->toArray();

            expect($array)->toHaveKey('email');
            expect($array)->toHaveKey('first_name');
            expect($array)->toHaveKey('age');
            expect($array)->toHaveKey('is_active');
            expect($array)->not->toHaveKey('extra_field');
        });

        test('it serializes to JSON with only mapped values', function(): void {
            $model = new TestDataModel([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 30,
            ]);

            $json = json_encode($model);
            expect($json)->toBeString();
            $decoded = json_decode($json ?: '{}', true);

            expect($decoded)->toHaveKey('email');
            expect($decoded)->toHaveKey('first_name');
            expect($decoded)->not->toHaveKey('extra_field');
        });

        test('it converts to string as JSON', function(): void {
            $model = new TestDataModel([
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 30,
            ]);

            $string = (string)$model;
            $decoded = json_decode($string, true);

            expect($decoded)->toBeArray();
            expect($decoded)->toHaveKey('email');
        });
    });

    describe('Original data access', function(): void {
        test('it returns all original data', function(): void {
            $originalData = [
                'email' => 'original@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 30,
                'extra' => 'value',
            ];

            $model = new TestDataModel($originalData);

            expect($model->getOriginalData())->toBe($originalData);
        });

        test('it preserves original data', function(): void {
            $model = new TestDataModel([
                'age' => 30,
            ]);

            expect($model->getOriginal('age'))->toBe(30);
            expect($model->get('age'))->toBe(30);
        });
    });

    describe('Template access', function(): void {
        test('it returns template definition', function(): void {
            $model = new TestDataModel();
            $template = $model->getTemplate();

            expect($template)->toBeArray();
            expect($template)->toHaveKey('email');
            expect($template)->toHaveKey('first_name');
            expect($template)->toHaveKey('age');
            expect($template)->toHaveKey('is_active');
        });
    });

    describe('Object input', function(): void {
        test('it accepts object with toArray method', function(): void {
            $object = new class () {
                /** @return array<string, mixed> */
                public function toArray(): array
                {
                    return [
                        'email' => 'test@example.com',
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'age' => 30,
                    ];
                }
            };

            $model = new TestDataModel($object);

            expect($model->email)->toBe('test@example.com');
            expect($model->first_name)->toBe('John');
        });

        test('it accepts JsonSerializable object', function(): void {
            $object = new class () implements JsonSerializable {
                /** @return array<string, mixed> */
                public function jsonSerialize(): array
                {
                    return [
                        'email' => 'test@example.com',
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'age' => 30,
                    ];
                }
            };

            $model = new TestDataModel($object);

            expect($model->email)->toBe('test@example.com');
        });
    });

    describe('Static factory', function(): void {
        test('it creates from request using static method', function(): void {
            $data = [
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 30,
            ];

            $model = TestDataModel::fromRequest($data);

            expect($model)->toBeInstanceOf(TestDataModel::class);
            expect($model->email)->toBe('test@example.com');
        });
    });

    describe('Nested values', function(): void {
        test('it handles nested template values', function(): void {
            $model = new TestDataModel([
                'nested_value' => 'test123',
            ]);

            $array = $model->toArray();
            expect($array)->toHaveKey('nested');
            expect($array['nested'])->toHaveKey('value');
            expect($array['nested']['value'])->toBe('test123');
        });
    });

    describe('Pipeline transformers', function(): void {
        test('it applies pipeline transformers to data', function(): void {
            $data = [
                'email' => '  ALICE@EXAMPLE.COM  ',
                'name' => '  Alice Smith  ',
            ];

            $model = new TestDataModelWithPipeline($data);

            expect($model->isMapped())->toBeTrue();
            expect($model->email)->toBe('alice@example.com'); // Trimmed and lowercased
            expect($model->name)->toBe('Alice Smith'); // Only trimmed
        });

        test('it applies transformers when filling data', function(): void {
            $model = new TestDataModelWithPipeline();

            $model->fill([
                'email' => '  BOB@TEST.COM  ',
                'name' => '  Bob Jones  ',
            ]);

            expect($model->isMapped())->toBeTrue();
            expect($model->email)->toBe('bob@test.com');
            expect($model->name)->toBe('Bob Jones');
        });

        test('it preserves original untransformed data', function(): void {
            $data = [
                'email' => '  TEST@EXAMPLE.COM  ',
                'name' => '  Test User  ',
            ];

            $model = new TestDataModelWithPipeline($data);

            // Mapped data should be transformed
            expect($model->email)->toBe('test@example.com');
            expect($model->name)->toBe('Test User');

            // Original data should be untouched
            expect($model->getOriginal('email'))->toBe('  TEST@EXAMPLE.COM  ');
            expect($model->getOriginal('name'))->toBe('  Test User  ');
        });

        test('it works with fromRequest static method', function(): void {
            $data = [
                'email' => '  CONTACT@COMPANY.COM  ',
                'name' => '  John Doe  ',
            ];

            $model = TestDataModelWithPipeline::fromRequest($data);

            expect($model)->toBeInstanceOf(TestDataModelWithPipeline::class);
            expect($model->email)->toBe('contact@company.com');
            expect($model->name)->toBe('John Doe');
        });
    });
});
