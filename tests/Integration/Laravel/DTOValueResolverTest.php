<?php

declare(strict_types=1);

namespace Tests\Integration\Laravel;

use event4u\DataHelpers\Laravel\DTOValueResolver;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\ValidateRequest;
use event4u\DataHelpers\Exceptions\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use ReflectionParameter;
use ReflectionClass;
use PHPUnit\Framework\TestCase;

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

/**
 * @group laravel
 */
class DTOValueResolverTest extends TestCase
{
    private ValidationFactory $validationFactory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create validation factory
        $translator = new Translator(new ArrayLoader(), 'en');
        $this->validationFactory = new ValidationFactory($translator);
    }

    private function createResolver(Request $request): DTOValueResolver
    {
        return new DTOValueResolver($request, $this->validationFactory);
    }

    private function createParameter(string $className, string $paramName = 'dto'): ReflectionParameter
    {
        // Create a unique dummy class name using uniqid to avoid redeclaration errors
        $dummyClassName = 'DummyController_' . uniqid();

        // Create a dummy class with a method that has the parameter
        $code = "class {$dummyClassName} { public function action({$className} \${$paramName}) {} }";
        eval($code);

        $reflection = new ReflectionClass($dummyClassName);
        $method = $reflection->getMethod('action');
        $parameters = $method->getParameters();

        return $parameters[0];
    }

    public function test_it_resolves_dto_from_request(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $resolver = $this->createResolver($request);
        $parameter = $this->createParameter(TestUserDTO::class);

        $result = $resolver->resolve($parameter);

        $this->assertInstanceOf(TestUserDTO::class, $result);
        $this->assertSame('John Doe', $result->name);
        $this->assertSame('john@example.com', $result->email);
    }

    public function test_it_validates_dto_with_validate_request_attribute(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John',
            'email' => 'invalid-email',
        ]);

        $resolver = $this->createResolver($request);
        $parameter = $this->createParameter(TestUserDTO::class);

        $this->expectException(ValidationException::class);

        $resolver->resolve($parameter);
    }

    public function test_it_resolves_dto_without_validation(): void
    {
        $request = Request::create('/test', 'POST', [
            'title' => 'Product Title',
            'description' => 'Product Description',
        ]);

        $resolver = $this->createResolver($request);
        $parameter = $this->createParameter(TestProductDTO::class);

        $result = $resolver->resolve($parameter);

        $this->assertInstanceOf(TestProductDTO::class, $result);
        $this->assertSame('Product Title', $result->title);
        $this->assertSame('Product Description', $result->description);
    }

    public function test_it_handles_missing_optional_fields(): void
    {
        $request = Request::create('/test', 'POST', [
            'title' => 'Product Title',
        ]);

        $resolver = $this->createResolver($request);
        $parameter = $this->createParameter(TestProductDTO::class);

        $result = $resolver->resolve($parameter);

        $this->assertInstanceOf(TestProductDTO::class, $result);
        $this->assertSame('Product Title', $result->title);
        $this->assertNull($result->description);
    }

    public function test_it_handles_json_request(): void
    {
        $request = Request::create('/test', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]));

        $resolver = $this->createResolver($request);
        $parameter = $this->createParameter(TestUserDTO::class);

        $result = $resolver->resolve($parameter);

        $this->assertInstanceOf(TestUserDTO::class, $result);
        $this->assertSame('Jane Doe', $result->name);
        $this->assertSame('jane@example.com', $result->email);
    }

    public function test_it_handles_empty_request(): void
    {
        $request = Request::create('/test', 'POST', []);

        $resolver = $this->createResolver($request);
        $parameter = $this->createParameter(TestUserDTO::class);

        $this->expectException(ValidationException::class);

        $resolver->resolve($parameter);
    }

    public function test_it_returns_validation_errors_with_correct_structure(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '',
            'email' => 'invalid',
        ]);

        $resolver = $this->createResolver($request);
        $parameter = $this->createParameter(TestUserDTO::class);

        try {
            $resolver->resolve($parameter);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('email', $errors);
            $this->assertIsArray($errors['name']);
            $this->assertIsArray($errors['email']);
        }
    }

    public function test_it_returns_null_for_non_dto_parameters(): void
    {
        $request = Request::create('/test', 'POST', []);

        $resolver = $this->createResolver($request);

        // Create parameter for built-in type
        $code = "class DummyController2 { public function action(string \$name) {} }";
        eval($code);
        $reflection = new ReflectionClass('DummyController2');
        $method = $reflection->getMethod('action');
        $parameter = $method->getParameters()[0];

        $result = $resolver->resolve($parameter);

        $this->assertNull($result);
    }

    public function test_it_preserves_request_data_types(): void
    {
        $request = Request::create('/test', 'POST', [
            'title' => 'Product',
            'description' => null,
        ]);

        $resolver = $this->createResolver($request);
        $parameter = $this->createParameter(TestProductDTO::class);

        $result = $resolver->resolve($parameter);

        $this->assertInstanceOf(TestProductDTO::class, $result);
        $this->assertNull($result->description);
    }
}

