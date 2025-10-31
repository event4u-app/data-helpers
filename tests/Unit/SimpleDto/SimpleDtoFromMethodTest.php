<?php

declare(strict_types=1);

use event4u\DataHelpers\SimpleDto;
use event4u\DataHelpers\SimpleDto\Attributes\AutoCast;

#[AutoCast]
class TestFromMethodDto extends SimpleDto
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public int $age = 0,
    ) {
    }
}

describe('SimpleDtoFromMethodTest', function(): void {
    describe('from() method', function(): void {
        it('creates Dto from array without template', function(): void {
            $data = ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30];
            $dto = TestFromMethodDto::from($data);

            expect($dto)->toBeInstanceOf(TestFromMethodDto::class);
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('creates Dto from array with template', function(): void {
            $data = ['user_name' => 'John Doe', 'user_email' => 'john@example.com', 'user_age' => 30];
            $template = [
                'name' => '{{ user_name }}',
                'email' => '{{ user_email }}',
                'age' => '{{ user_age }}',
            ];

            $dto = TestFromMethodDto::from($data, $template);

            expect($dto)->toBeInstanceOf(TestFromMethodDto::class);
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });

        it('accepts filters parameter', function(): void {
            $data = ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30];

            // Just test that the parameter is accepted (no actual filters to test with)
            $dto = TestFromMethodDto::from($data, null, null);

            expect($dto)->toBeInstanceOf(TestFromMethodDto::class);
            expect($dto->name)->toBe('John Doe');
        });

        it('accepts pipeline parameter', function(): void {
            $data = ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30];

            // Just test that the parameter is accepted (no actual pipeline to test with)
            $dto = TestFromMethodDto::from($data, null, null, null);

            expect($dto)->toBeInstanceOf(TestFromMethodDto::class);
            expect($dto->name)->toBe('John Doe');
        });

        it('creates Dto from array with template and all parameters', function(): void {
            $data = ['user_name' => 'John Doe', 'user_email' => 'john@example.com', 'user_age' => 30];
            $template = [
                'name' => '{{ user_name }}',
                'email' => '{{ user_email }}',
                'age' => '{{ user_age }}',
            ];

            // Test that all parameters are accepted together
            $dto = TestFromMethodDto::from($data, $template, null, null);

            expect($dto)->toBeInstanceOf(TestFromMethodDto::class);
            expect($dto->name)->toBe('John Doe');
            expect($dto->email)->toBe('john@example.com');
            expect($dto->age)->toBe(30);
        });
    });
});
