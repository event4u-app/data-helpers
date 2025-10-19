<?php

declare(strict_types=1);

namespace Tests\Integration\Symfony;

// Skip this file entirely if Symfony is not installed
if (!interface_exists('Symfony\Component\HttpKernel\Controller\ValueResolverInterface')) {
    return;
}

use event4u\DataHelpers\Symfony\DTOValueResolver;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;
use event4u\DataHelpers\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use PHPUnit\Framework\TestCase;

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

class DTOValueResolverTest extends TestCase
{
    private DTOValueResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new DTOValueResolver();
    }

    private function createArgumentMetadata(string $className, string $name = 'dto'): ArgumentMetadata
    {
        return new ArgumentMetadata($name, $className, false, false, null);
    }

    public function test_it_resolves_dto_from_request(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $argument = $this->createArgumentMetadata(SymfonyTestUserDTO::class);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertInstanceOf(SymfonyTestUserDTO::class, $result[0]);
        $this->assertSame('John Doe', $result[0]->name);
        $this->assertSame('john@example.com', $result[0]->email);
    }

    public function test_it_validates_dto_with_validate_request_attribute(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John',
            'email' => 'invalid-email',
        ]);

        $argument = $this->createArgumentMetadata(SymfonyTestUserDTO::class);

        $this->expectException(ValidationException::class);

        iterator_to_array($this->resolver->resolve($request, $argument));
    }

    public function test_it_resolves_dto_without_validation(): void
    {
        $request = Request::create('/test', 'POST', [
            'title' => 'Product Title',
            'description' => 'Product Description',
        ]);

        $argument = $this->createArgumentMetadata(SymfonyTestProductDTO::class);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertInstanceOf(SymfonyTestProductDTO::class, $result[0]);
        $this->assertSame('Product Title', $result[0]->title);
        $this->assertSame('Product Description', $result[0]->description);
    }

    public function test_it_handles_missing_optional_fields(): void
    {
        $request = Request::create('/test', 'POST', [
            'title' => 'Product Title',
        ]);

        $argument = $this->createArgumentMetadata(SymfonyTestProductDTO::class);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertInstanceOf(SymfonyTestProductDTO::class, $result[0]);
        $this->assertSame('Product Title', $result[0]->title);
        $this->assertNull($result[0]->description);
    }

    public function test_it_handles_json_request(): void
    {
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
            ])
        );

        $argument = $this->createArgumentMetadata(SymfonyTestUserDTO::class);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertInstanceOf(SymfonyTestUserDTO::class, $result[0]);
        $this->assertSame('Jane Doe', $result[0]->name);
        $this->assertSame('jane@example.com', $result[0]->email);
    }

    public function test_it_handles_empty_request(): void
    {
        $request = Request::create('/test', 'POST', []);

        $argument = $this->createArgumentMetadata(SymfonyTestUserDTO::class);

        $this->expectException(ValidationException::class);

        iterator_to_array($this->resolver->resolve($request, $argument));
    }

    public function test_it_returns_validation_errors_with_correct_structure(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '',
            'email' => 'invalid',
        ]);

        $argument = $this->createArgumentMetadata(SymfonyTestUserDTO::class);

        try {
            iterator_to_array($this->resolver->resolve($request, $argument));
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('email', $errors);
            $this->assertIsArray($errors['name']);
            $this->assertIsArray($errors['email']);
        }
    }

    public function test_it_returns_empty_for_non_dto_parameters(): void
    {
        $request = Request::create('/test', 'POST', []);

        $argument = $this->createArgumentMetadata('string');

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(0, $result);
    }

    public function test_it_returns_empty_for_non_class_types(): void
    {
        $request = Request::create('/test', 'POST', []);

        $argument = new ArgumentMetadata('param', null, false, false, null);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(0, $result);
    }

    public function test_it_preserves_request_data_types(): void
    {
        $request = Request::create('/test', 'POST', [
            'title' => 'Product',
            'description' => null,
        ]);

        $argument = $this->createArgumentMetadata(SymfonyTestProductDTO::class);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertInstanceOf(SymfonyTestProductDTO::class, $result[0]);
        $this->assertNull($result[0]->description);
    }

    public function test_it_handles_put_request(): void
    {
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
            ])
        );

        $argument = $this->createArgumentMetadata(SymfonyTestUserDTO::class);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertInstanceOf(SymfonyTestUserDTO::class, $result[0]);
        $this->assertSame('Updated Name', $result[0]->name);
        $this->assertSame('updated@example.com', $result[0]->email);
    }

    public function test_it_handles_patch_request(): void
    {
        $request = Request::create(
            '/test',
            'PATCH',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Patched Title',
            ])
        );

        $argument = $this->createArgumentMetadata(SymfonyTestProductDTO::class);

        $result = iterator_to_array($this->resolver->resolve($request, $argument));

        $this->assertCount(1, $result);
        $this->assertInstanceOf(SymfonyTestProductDTO::class, $result[0]);
        $this->assertSame('Patched Title', $result[0]->title);
    }
}

