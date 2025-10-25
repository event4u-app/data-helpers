<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDTO;

describe('SimpleDTOAutoCastingTest', function () {
    beforeEach(function () {
        // Define test DTOs
        $this->userDtoClass = new class ('', '', 0) extends SimpleDTO {
            public function __construct(
                public string $name = '',
                public string $email = '',
                public int $age = 0,
            ) {
            }
        };

        $this->typedDtoClass = new class ('', 0, 0.0, false, []) extends SimpleDTO {
            public function __construct(
                public string $stringProp = '',
                public int $intProp = 0,
                public float $floatProp = 0.0,
                public bool $boolProp = false,
                public array $arrayProp = [],
            ) {
            }
        };
    });

    describe('fromJson() with auto-casting', function () {
        it('casts string numbers to int', function () {
            $json = '{"name":"John","email":"john@example.com","age":"30"}';
            $dto = $this->userDtoClass::fromJson($json);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });

        it('handles nested JSON', function () {
            $json = '{"name":"John","email":"john@example.com","age":"30"}';
            $dto = $this->userDtoClass::fromJson($json);

            expect($dto->name)->toBe('John');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });
    });

    describe('fromXml() with auto-casting', function () {
        it('casts XML string values to proper types', function () {
            $xml = '<?xml version="1.0"?><root><name>John</name><email>john@example.com</email><age>30</age></root>';
            $dto = $this->userDtoClass::fromXml($xml);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });
    });

    describe('fromYaml() with auto-casting', function () {
        it('casts YAML string values to proper types', function () {
            if (!function_exists('yaml_parse') && !class_exists(\Symfony\Component\Yaml\Yaml::class)) {
                $this->markTestSkipped('YAML support not available (neither ext-yaml nor symfony/yaml)');
            }

            $yaml = "name: John\nemail: john@example.com\nage: 30";
            $dto = $this->userDtoClass::fromYaml($yaml);

            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });
    });

    describe('fromCsv() with auto-casting', function () {
        it('casts CSV string values to proper types', function () {
            $csv = "\"name\",\"email\",\"age\"\n\"John\",\"john@example.com\",30";
            $dto = $this->userDtoClass::fromCsv($csv);

            expect($dto->name)->toBe('John');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
            expect($dto->age)->toBeInt();
        });

        it('handles CSV with quoted values', function () {
            $csv = "\"name\",\"email\",\"age\"\n\"John Doe\",\"john@example.com\",\"25\"";
            $dto = $this->userDtoClass::fromCsv($csv);

            expect($dto->name)->toBe('John Doe');
            expect($dto->age)->toBe(25);
            expect($dto->age)->toBeInt();
        });
    });

    describe('castToInt()', function () {
        it('casts numeric strings to int', function () {
            $dto = $this->typedDtoClass::fromArray(['intProp' => '42']);
            expect($dto->intProp)->toBe(42);
            expect($dto->intProp)->toBeInt();
        });

        it('casts float strings to int', function () {
            $dto = $this->typedDtoClass::fromArray(['intProp' => '42.7']);
            expect($dto->intProp)->toBe(42);
            expect($dto->intProp)->toBeInt();
        });

        it('casts boolean true to 1', function () {
            $dto = $this->typedDtoClass::fromArray(['intProp' => true]);
            expect($dto->intProp)->toBe(1);
        });

        it('casts boolean false to 0', function () {
            $dto = $this->typedDtoClass::fromArray(['intProp' => false]);
            expect($dto->intProp)->toBe(0);
        });

        it('casts float to int', function () {
            $dto = $this->typedDtoClass::fromArray(['intProp' => 42.9]);
            expect($dto->intProp)->toBe(42);
        });

        it('does NOT cast non-numeric strings', function () {
            expect(fn() => $this->typedDtoClass::fromArray(['intProp' => 'hello']))
                ->toThrow(TypeError::class);
        });

        it('does NOT cast empty string', function () {
            expect(fn() => $this->typedDtoClass::fromArray(['intProp' => '']))
                ->toThrow(TypeError::class);
        });

        it('handles negative numbers', function () {
            $dto = $this->typedDtoClass::fromArray(['intProp' => '-42']);
            expect($dto->intProp)->toBe(-42);
        });

        it('handles zero', function () {
            $dto = $this->typedDtoClass::fromArray(['intProp' => '0']);
            expect($dto->intProp)->toBe(0);
        });
    });

    describe('castToFloat()', function () {
        it('casts numeric strings to float', function () {
            $dto = $this->typedDtoClass::fromArray(['floatProp' => '42.5']);
            expect($dto->floatProp)->toBe(42.5);
            expect($dto->floatProp)->toBeFloat();
        });

        it('casts int strings to float', function () {
            $dto = $this->typedDtoClass::fromArray(['floatProp' => '42']);
            expect($dto->floatProp)->toBe(42.0);
            expect($dto->floatProp)->toBeFloat();
        });

        it('casts boolean true to 1.0', function () {
            $dto = $this->typedDtoClass::fromArray(['floatProp' => true]);
            expect($dto->floatProp)->toBe(1.0);
        });

        it('casts boolean false to 0.0', function () {
            $dto = $this->typedDtoClass::fromArray(['floatProp' => false]);
            expect($dto->floatProp)->toBe(0.0);
        });

        it('casts int to float', function () {
            $dto = $this->typedDtoClass::fromArray(['floatProp' => 42]);
            expect($dto->floatProp)->toBe(42.0);
        });

        it('does NOT cast non-numeric strings', function () {
            expect(fn() => $this->typedDtoClass::fromArray(['floatProp' => 'hello']))
                ->toThrow(TypeError::class);
        });

        it('handles negative floats', function () {
            $dto = $this->typedDtoClass::fromArray(['floatProp' => '-42.5']);
            expect($dto->floatProp)->toBe(-42.5);
        });

        it('handles scientific notation', function () {
            $dto = $this->typedDtoClass::fromArray(['floatProp' => '1.5e3']);
            expect($dto->floatProp)->toBe(1500.0);
        });
    });

    describe('castToBool()', function () {
        it('casts string "true" to true', function () {
            $dto = $this->typedDtoClass::fromArray(['boolProp' => 'true']);
            expect($dto->boolProp)->toBeTrue();
        });

        it('casts string "1" to true', function () {
            $dto = $this->typedDtoClass::fromArray(['boolProp' => '1']);
            expect($dto->boolProp)->toBeTrue();
        });

        it('casts string "yes" to true', function () {
            $dto = $this->typedDtoClass::fromArray(['boolProp' => 'yes']);
            expect($dto->boolProp)->toBeTrue();
        });

        it('casts string "on" to true', function () {
            $dto = $this->typedDtoClass::fromArray(['boolProp' => 'on']);
            expect($dto->boolProp)->toBeTrue();
        });

        it('casts string "false" to false', function () {
            $dto = $this->typedDtoClass::fromArray(['boolProp' => 'false']);
            expect($dto->boolProp)->toBeFalse();
        });

        it('casts string "0" to false', function () {
            $dto = $this->typedDtoClass::fromArray(['boolProp' => '0']);
            expect($dto->boolProp)->toBeFalse();
        });

        it('casts string "no" to false', function () {
            $dto = $this->typedDtoClass::fromArray(['boolProp' => 'no']);
            expect($dto->boolProp)->toBeFalse();
        });

        it('casts string "off" to false', function () {
            $dto = $this->typedDtoClass::fromArray(['boolProp' => 'off']);
            expect($dto->boolProp)->toBeFalse();
        });

        it('casts empty string to false', function () {
            $dto = $this->typedDtoClass::fromArray(['boolProp' => '']);
            expect($dto->boolProp)->toBeFalse();
        });

        it('casts int 0 to false', function () {
            $dto = $this->typedDtoClass::fromArray(['boolProp' => 0]);
            expect($dto->boolProp)->toBeFalse();
        });

        it('casts int 1 to true', function () {
            $dto = $this->typedDtoClass::fromArray(['boolProp' => 1]);
            expect($dto->boolProp)->toBeTrue();
        });

        it('casts any non-zero int to true', function () {
            $dto = $this->typedDtoClass::fromArray(['boolProp' => 42]);
            expect($dto->boolProp)->toBeTrue();
        });

        it('is case-insensitive', function () {
            $dto1 = $this->typedDtoClass::fromArray(['boolProp' => 'TRUE']);
            expect($dto1->boolProp)->toBeTrue();

            $dto2 = $this->typedDtoClass::fromArray(['boolProp' => 'False']);
            expect($dto2->boolProp)->toBeFalse();
        });

        it('does NOT cast other strings', function () {
            expect(fn() => $this->typedDtoClass::fromArray(['boolProp' => 'hello']))
                ->toThrow(TypeError::class);
        });
    });

    describe('castToString()', function () {
        it('keeps strings as strings', function () {
            $dto = $this->typedDtoClass::fromArray(['stringProp' => 'hello']);
            expect($dto->stringProp)->toBe('hello');
            expect($dto->stringProp)->toBeString();
        });

        it('casts int to string', function () {
            $dto = $this->typedDtoClass::fromArray(['stringProp' => 42]);
            expect($dto->stringProp)->toBe('42');
            expect($dto->stringProp)->toBeString();
        });

        it('casts float to string', function () {
            $dto = $this->typedDtoClass::fromArray(['stringProp' => 42.5]);
            expect($dto->stringProp)->toBe('42.5');
            expect($dto->stringProp)->toBeString();
        });

        it('casts boolean true to string', function () {
            $dto = $this->typedDtoClass::fromArray(['stringProp' => true]);
            expect($dto->stringProp)->toBe('1');
            expect($dto->stringProp)->toBeString();
        });

        it('casts boolean false to string', function () {
            $dto = $this->typedDtoClass::fromArray(['stringProp' => false]);
            expect($dto->stringProp)->toBe('');
            expect($dto->stringProp)->toBeString();
        });

        it('does NOT cast arrays', function () {
            expect(fn() => $this->typedDtoClass::fromArray(['stringProp' => ['hello']]))
                ->toThrow(TypeError::class);
        });

        it('does NOT cast objects', function () {
            expect(fn() => $this->typedDtoClass::fromArray(['stringProp' => new stdClass()]))
                ->toThrow(TypeError::class);
        });
    });

    describe('castToArray()', function () {
        it('keeps arrays as arrays', function () {
            $dto = $this->typedDtoClass::fromArray(['arrayProp' => ['a', 'b', 'c']]);
            expect($dto->arrayProp)->toBe(['a', 'b', 'c']);
            expect($dto->arrayProp)->toBeArray();
        });

        it('decodes JSON strings to arrays', function () {
            $dto = $this->typedDtoClass::fromArray(['arrayProp' => '["a","b","c"]']);
            expect($dto->arrayProp)->toBe(['a', 'b', 'c']);
            expect($dto->arrayProp)->toBeArray();
        });

        it('decodes JSON objects to associative arrays', function () {
            $dto = $this->typedDtoClass::fromArray(['arrayProp' => '{"key":"value"}']);
            expect($dto->arrayProp)->toBe(['key' => 'value']);
            expect($dto->arrayProp)->toBeArray();
        });

        it('does NOT cast invalid JSON strings', function () {
            expect(fn() => $this->typedDtoClass::fromArray(['arrayProp' => 'not json']))
                ->toThrow(TypeError::class);
        });

        it('does NOT cast non-array types', function () {
            expect(fn() => $this->typedDtoClass::fromArray(['arrayProp' => 42]))
                ->toThrow(TypeError::class);
        });
    });

    describe('Edge cases', function () {
        it('handles null values correctly', function () {
            $nullableDtoClass = new class ('', null) extends SimpleDTO {
                public function __construct(
                    public string $name = '',
                    public ?int $age = null,
                ) {
                }
            };

            $dto = $nullableDtoClass::fromArray(['name' => 'John', 'age' => null]);
            expect($dto->age)->toBeNull();
        });

        it('handles mixed type properties', function () {
            $mixedDtoClass = new class ('', null) extends SimpleDTO {
                public function __construct(
                    public string $name = '',
                    public mixed $data = null,
                ) {
                }
            };

            $dto = $mixedDtoClass::fromArray(['name' => 'John', 'data' => '42']);
            expect($dto->data)->toBe('42'); // mixed type, no casting
        });

        it('handles multiple type casts in one DTO', function () {
            $dto = $this->typedDtoClass::fromArray([
                'stringProp' => 42,
                'intProp' => '30',
                'floatProp' => '3.14',
                'boolProp' => 'true',
                'arrayProp' => '["a","b"]',
            ]);

            expect($dto->stringProp)->toBe('42');
            expect($dto->intProp)->toBe(30);
            expect($dto->floatProp)->toBe(3.14);
            expect($dto->boolProp)->toBeTrue();
            expect($dto->arrayProp)->toBe(['a', 'b']);
        });

        it('preserves already correct types', function () {
            $dto = $this->typedDtoClass::fromArray([
                'stringProp' => 'hello',
                'intProp' => 42,
                'floatProp' => 3.14,
                'boolProp' => true,
                'arrayProp' => ['a', 'b'],
            ]);

            expect($dto->stringProp)->toBe('hello');
            expect($dto->intProp)->toBe(42);
            expect($dto->floatProp)->toBe(3.14);
            expect($dto->boolProp)->toBeTrue();
            expect($dto->arrayProp)->toBe(['a', 'b']);
        });
    });
});

