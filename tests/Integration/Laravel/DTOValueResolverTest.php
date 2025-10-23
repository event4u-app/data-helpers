<?php

declare(strict_types=1);

namespace Tests\Integration\Laravel;

use event4u\DataHelpers\Exceptions\ValidationException;
use event4u\DataHelpers\Frameworks\Laravel\DTOValueResolver;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use ReflectionClass;

// Test DTOs
#[ValidateRequest(throw: true)]
class TestUserDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $name,
        #[Required]
        #[Email]
        public readonly string $email,
    ) {}
}

class TestProductDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        public readonly string $title,
        public readonly ?string $description = null,
    ) {}
}

describe('Laravel DTOValueResolver', function(): void {
    beforeEach(function(): void {
        // Skip if Laravel is not available
        if (!class_exists('Illuminate\Http\Request')) {
            $this->markTestSkipped('Laravel is not available');
        }

        // Create validation factory
        $translator = new Translator(new ArrayLoader(), 'en');
        $this->validationFactory = new ValidationFactory($translator);
    });

    test('it resolves dto from request', function(): void {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $resolver = new DTOValueResolver($request, $this->validationFactory);

        // Create parameter
        /** @phpstan-ignore-next-line unknown */
        $dummyClassName = 'DummyController_' . uniqid();
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        eval(sprintf(
            'class %s { public function action(Tests\Integration\Laravel\TestUserDTO $dto) {} }',
            $dummyClassName
        ));
        $reflection = new ReflectionClass($dummyClassName);
        $parameter = $reflection->getMethod('action')->getParameters()[0];

        $result = $resolver->resolve($parameter);

        expect($result)->toBeInstanceOf(TestUserDTO::class);
        /** @phpstan-ignore-next-line unknown */
        expect($result->name)->toBe('John Doe');
        /** @phpstan-ignore-next-line unknown */
        expect($result->email)->toBe('john@example.com');
    });

    test('it validates dto with validate request attribute', function(): void {
        $request = Request::create('/test', 'POST', [
            'name' => 'John',
            'email' => 'invalid-email',
        ]);

        $resolver = new DTOValueResolver($request, $this->validationFactory);

        /** @phpstan-ignore-next-line unknown */
        $dummyClassName = 'DummyController_' . uniqid();
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        eval(sprintf(
            'class %s { public function action(Tests\Integration\Laravel\TestUserDTO $dto) {} }',
            $dummyClassName
        ));
        $reflection = new ReflectionClass($dummyClassName);
        $parameter = $reflection->getMethod('action')->getParameters()[0];

        expect(fn(): mixed => $resolver->resolve($parameter))
            ->toThrow(ValidationException::class);
    });

    test('it resolves dto without validation', function(): void {
        $request = Request::create('/test', 'POST', [
            'title' => 'Product Title',
            'description' => 'Product Description',
        ]);

        $resolver = new DTOValueResolver($request, $this->validationFactory);

        /** @phpstan-ignore-next-line unknown */
        $dummyClassName = 'DummyController_' . uniqid();
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        eval(sprintf(
            'class %s { public function action(Tests\Integration\Laravel\TestProductDTO $dto) {} }',
            $dummyClassName
        ));
        $reflection = new ReflectionClass($dummyClassName);
        $parameter = $reflection->getMethod('action')->getParameters()[0];

        $result = $resolver->resolve($parameter);

        expect($result)->toBeInstanceOf(TestProductDTO::class);
        /** @phpstan-ignore-next-line unknown */
        expect($result->title)->toBe('Product Title');
        /** @phpstan-ignore-next-line unknown */
        expect($result->description)->toBe('Product Description');
    });

    test('it handles missing optional fields', function(): void {
        $request = Request::create('/test', 'POST', [
            'title' => 'Product Title',
        ]);

        $resolver = new DTOValueResolver($request, $this->validationFactory);

        /** @phpstan-ignore-next-line unknown */
        $dummyClassName = 'DummyController_' . uniqid();
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        eval(sprintf(
            'class %s { public function action(Tests\Integration\Laravel\TestProductDTO $dto) {} }',
            $dummyClassName
        ));
        $reflection = new ReflectionClass($dummyClassName);
        $parameter = $reflection->getMethod('action')->getParameters()[0];

        $result = $resolver->resolve($parameter);

        expect($result)->toBeInstanceOf(TestProductDTO::class);
        /** @phpstan-ignore-next-line unknown */
        expect($result->title)->toBe('Product Title');
        /** @phpstan-ignore-next-line unknown */
        expect($result->description)->toBeNull();
    });

    test('it handles json request', function(): void {
        $request = Request::create('/test', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]) ?: '');

        $resolver = new DTOValueResolver($request, $this->validationFactory);

        /** @phpstan-ignore-next-line unknown */
        $dummyClassName = 'DummyController_' . uniqid();
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        eval(sprintf(
            'class %s { public function action(Tests\Integration\Laravel\TestUserDTO $dto) {} }',
            $dummyClassName
        ));
        $reflection = new ReflectionClass($dummyClassName);
        $parameter = $reflection->getMethod('action')->getParameters()[0];

        $result = $resolver->resolve($parameter);

        expect($result)->toBeInstanceOf(TestUserDTO::class);
        /** @phpstan-ignore-next-line unknown */
        expect($result->name)->toBe('Jane Doe');
        /** @phpstan-ignore-next-line unknown */
        expect($result->email)->toBe('jane@example.com');
    });

    test('it handles empty request', function(): void {
        $request = Request::create('/test', 'POST', []);

        $resolver = new DTOValueResolver($request, $this->validationFactory);

        /** @phpstan-ignore-next-line unknown */
        $dummyClassName = 'DummyController_' . uniqid();
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        eval(sprintf(
            'class %s { public function action(Tests\Integration\Laravel\TestUserDTO $dto) {} }',
            $dummyClassName
        ));
        $reflection = new ReflectionClass($dummyClassName);
        $parameter = $reflection->getMethod('action')->getParameters()[0];

        expect(fn(): mixed => $resolver->resolve($parameter))
            ->toThrow(ValidationException::class);
    });

    test('it returns validation errors with correct structure', function(): void {
        $request = Request::create('/test', 'POST', [
            'name' => '',
            'email' => 'invalid',
        ]);

        $resolver = new DTOValueResolver($request, $this->validationFactory);

        /** @phpstan-ignore-next-line unknown */
        $dummyClassName = 'DummyController_' . uniqid();
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        eval(sprintf(
            'class %s { public function action(Tests\Integration\Laravel\TestUserDTO $dto) {} }',
            $dummyClassName
        ));
        $reflection = new ReflectionClass($dummyClassName);
        $parameter = $reflection->getMethod('action')->getParameters()[0];

        try {
            $resolver->resolve($parameter);
            throw new Exception('Expected ValidationException to be thrown');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            expect($errors)->toHaveKey('name')
                ->and($errors)->toHaveKey('email')
                ->and($errors['name'])->toBeArray()
                ->and($errors['email'])->toBeArray();
        }
    });

    test('it returns null for non dto parameters', function(): void {
        $request = Request::create('/test', 'POST', []);

        $resolver = new DTOValueResolver($request, $this->validationFactory);

        // Create parameter for built-in type
        /** @phpstan-ignore-next-line unknown */
        $dummyClassName = 'DummyController_' . uniqid();
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        eval(sprintf('class %s { public function action(string $name) {} }', $dummyClassName));
        $reflection = new ReflectionClass($dummyClassName);
        $parameter = $reflection->getMethod('action')->getParameters()[0];

        $result = $resolver->resolve($parameter);

        expect($result)->toBeNull();
    });

    test('it preserves request data types', function(): void {
        $request = Request::create('/test', 'POST', [
            'title' => 'Product',
            'description' => null,
        ]);

        $resolver = new DTOValueResolver($request, $this->validationFactory);

        /** @phpstan-ignore-next-line unknown */
        $dummyClassName = 'DummyController_' . uniqid();
        /** @phpstan-ignore-next-line unknown */
        /** @phpstan-ignore-next-line unknown */
        eval(sprintf(
            'class %s { public function action(Tests\Integration\Laravel\TestProductDTO $dto) {} }',
            $dummyClassName
        ));
        $reflection = new ReflectionClass($dummyClassName);
        $parameter = $reflection->getMethod('action')->getParameters()[0];

        $result = $resolver->resolve($parameter);

        expect($result)->toBeInstanceOf(TestProductDTO::class);
        /** @phpstan-ignore-next-line unknown */
        expect($result->description)->toBeNull();
    });
})->group('laravel');
