<?php

declare(strict_types=1);

namespace Tests\Integration\Symfony;

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;
use event4u\DataHelpers\Symfony\DTOValueResolver;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

// Test DTOs
#[ValidateRequest(throw: true)]
class SymfonyTestUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        #[Required]
        #[Email]
        public readonly string $email,
    ) {}
}

class SymfonyTestProductDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $title,
        public readonly ?string $description = null,
    ) {}
}

describe('Symfony DTOValueResolver', function(): void {
    beforeEach(function(): void {
        // Skip if Symfony is not available
        if (!interface_exists('Symfony\Component\HttpKernel\Controller\ValueResolverInterface')) {
            $this->markTestSkipped('Symfony is not available');
        }

        $this->resolver = new DTOValueResolver();
    });

    test('it resolves dto from request', function(): void {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $argument = new ArgumentMetadata('dto', SymfonyTestUserDTO::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        expect($result)->toHaveCount(1)
            ->and($result[0])->toBeInstanceOf(SymfonyTestUserDTO::class)
            ->and($result[0]->name)->toBe('John Doe')
            ->and($result[0]->email)->toBe('john@example.com');
    })->group('symfony');

    test('it validates dto with validate request attribute', function(): void {
        $request = Request::create('/test', 'POST', [
            'name' => 'John',
            'email' => 'invalid-email',
        ]);

        $argument = new ArgumentMetadata('dto', SymfonyTestUserDTO::class, false, false, null);

        expect(fn(): array => iterator_to_array($this->resolver->resolve($request, $argument)))
            ->toThrow(ValidationException::class);
    })->group('symfony');

    test('it resolves dto without validation', function(): void {
        $request = Request::create('/test', 'POST', [
            'title' => 'Product Title',
            'description' => 'Product Description',
        ]);

        $argument = new ArgumentMetadata('dto', SymfonyTestProductDTO::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        expect($result)->toHaveCount(1)
            ->and($result[0])->toBeInstanceOf(SymfonyTestProductDTO::class)
            ->and($result[0]->title)->toBe('Product Title')
            ->and($result[0]->description)->toBe('Product Description');
    })->group('symfony');

    test('it handles missing optional fields', function(): void {
        $request = Request::create('/test', 'POST', [
            'title' => 'Product Title',
        ]);

        $argument = new ArgumentMetadata('dto', SymfonyTestProductDTO::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        expect($result)->toHaveCount(1)
            ->and($result[0])->toBeInstanceOf(SymfonyTestProductDTO::class)
            ->and($result[0]->title)->toBe('Product Title')
            ->and($result[0]->description)->toBeNull();
    })->group('symfony');

    test('it handles json request', function(): void {
        $request = Request::create(
            '/test',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ]) ?: ''
        );

        $argument = new ArgumentMetadata('dto', SymfonyTestUserDTO::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        expect($result)->toHaveCount(1)
            ->and($result[0])->toBeInstanceOf(SymfonyTestUserDTO::class)
            ->and($result[0]->name)->toBe('Jane Doe')
            ->and($result[0]->email)->toBe('jane@example.com');
    })->group('symfony');

    test('it handles empty request', function(): void {
        $request = Request::create('/test', 'POST', []);

        $argument = new ArgumentMetadata('dto', SymfonyTestUserDTO::class, false, false, null);

        expect(fn(): array => iterator_to_array($this->resolver->resolve($request, $argument)))
            ->toThrow(ValidationException::class);
    })->group('symfony');

    test('it returns validation errors with correct structure', function(): void {
        $request = Request::create('/test', 'POST', [
            'name' => '',
            'email' => 'invalid',
        ]);

        $argument = new ArgumentMetadata('dto', SymfonyTestUserDTO::class, false, false, null);

        try {
            iterator_to_array($this->resolver->resolve($request, $argument));
            throw new Exception('Expected ValidationException to be thrown');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            expect($errors)->toHaveKey('name')
                ->and($errors)->toHaveKey('email')
                ->and($errors['name'])->toBeArray()
                ->and($errors['email'])->toBeArray();
        }
    })->group('symfony');

    test('it returns empty for non dto parameters', function(): void {
        $request = Request::create('/test', 'POST', []);

        $argument = new ArgumentMetadata('dto', 'string', false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        expect($result)->toHaveCount(0);
    })->group('symfony');

    test('it returns empty for non class types', function(): void {
        $request = Request::create('/test', 'POST', []);

        $argument = new ArgumentMetadata('param', null, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        expect($result)->toHaveCount(0);
    })->group('symfony');

    test('it preserves request data types', function(): void {
        $request = Request::create('/test', 'POST', [
            'title' => 'Product',
            'description' => null,
        ]);

        $argument = new ArgumentMetadata('dto', SymfonyTestProductDTO::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        expect($result)->toHaveCount(1)
            ->and($result[0])->toBeInstanceOf(SymfonyTestProductDTO::class)
            ->and($result[0]->description)->toBeNull();
    })->group('symfony');

    test('it handles put request', function(): void {
        $request = Request::create(
            '/test',
            'PUT',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]) ?: ''
        );

        $argument = new ArgumentMetadata('dto', SymfonyTestUserDTO::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        expect($result)->toHaveCount(1)
            ->and($result[0])->toBeInstanceOf(SymfonyTestUserDTO::class)
            ->and($result[0]->name)->toBe('Updated Name')
            ->and($result[0]->email)->toBe('updated@example.com');
    })->group('symfony');

    test('it handles patch request', function(): void {
        $request = Request::create(
            '/test',
            'PATCH',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Patched Title',
            ]) ?: ''
        );

        $argument = new ArgumentMetadata('dto', SymfonyTestProductDTO::class, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        expect($result)->toHaveCount(1)
            ->and($result[0])->toBeInstanceOf(SymfonyTestProductDTO::class)
            ->and($result[0]->title)->toBe('Patched Title');
    })->group('symfony');
});

