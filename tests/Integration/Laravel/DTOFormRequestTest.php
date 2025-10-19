<?php

declare(strict_types=1);

namespace Tests\Integration\Laravel;

// Skip this file entirely if Laravel is not installed
if (!class_exists('Illuminate\Foundation\Http\FormRequest')) {
    return;
}

use event4u\DataHelpers\Laravel\DTOFormRequest;
use event4u\DataHelpers\SimpleDTO;
use event4u\DataHelpers\SimpleDTO\Attributes\Required;
use event4u\DataHelpers\SimpleDTO\Attributes\Email;
use event4u\DataHelpers\SimpleDTO\Attributes\Min;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

// Test DTO
class UserFormDTO extends SimpleDTO
{
    public function __construct(
        #[Required]
        #[Min(2)]
        public readonly string $name,
        #[Required]
        #[Email]
        public readonly string $email,
        public readonly ?int $age = null,
    ) {}
}

// Test FormRequest
class UserFormRequest extends DTOFormRequest
{
    protected string $dtoClass = UserFormDTO::class;

    public function authorize(): bool
    {
        return true;
    }
}

/**
 * @group laravel
 */
class DTOFormRequestTest extends TestCase
{
    private ValidationFactory $validationFactory;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        // Create validation factory
        $translator = new Translator(new ArrayLoader(), 'en');
        $this->validationFactory = new ValidationFactory($translator);

        // Create container
        $this->container = new Container();
        $this->container->instance('validator', $this->validationFactory);
        Container::setInstance($this->container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_it_creates_dto_from_valid_data(): void
    {
        $request = UserFormRequest::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        // Manually set validator
        $validator = $this->validationFactory->make(
            $request->all(),
            $request->rules()
        );
        $request->setValidator($validator);

        $dto = $request->toDTO();

        $this->assertInstanceOf(UserFormDTO::class, $dto);
        $this->assertSame('John Doe', $dto->name);
        $this->assertSame('john@example.com', $dto->email);
        $this->assertSame(30, $dto->age);
    }

    public function test_it_generates_rules_from_dto(): void
    {
        $request = new UserFormRequest();

        $rules = $request->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);

        // Check that rules contain expected validation
        $this->assertContains('required', $rules['name']);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
    }

    public function test_it_handles_optional_fields(): void
    {
        $request = UserFormRequest::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Manually set validator
        $validator = $this->validationFactory->make(
            $request->all(),
            $request->rules()
        );
        $request->setValidator($validator);

        $dto = $request->toDTO();

        $this->assertInstanceOf(UserFormDTO::class, $dto);
        $this->assertNull($dto->age);
    }

    public function test_it_validates_data_automatically(): void
    {
        $request = UserFormRequest::create('/test', 'POST', [
            'name' => 'J',  // Too short
            'email' => 'invalid-email',
        ]);

        // Set validator
        $request->setContainer($this->container);
        $validator = $this->validationFactory->make(
            $request->all(),
            $request->rules()
        );
        $request->setValidator($validator);

        $this->assertFalse($validator->passes());
    }

    public function test_it_provides_custom_messages(): void
    {
        $request = new class extends DTOFormRequest {
            protected string $dtoClass = UserFormDTO::class;

            public function authorize(): bool
            {
                return true;
            }

            public function messages(): array
            {
                return [
                    'name.required' => 'Please provide your name',
                    'email.email' => 'Please provide a valid email address',
                ];
            }
        };

        $messages = $request->messages();

        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('email.email', $messages);
        $this->assertSame('Please provide your name', $messages['name.required']);
    }

    public function test_it_provides_custom_attributes(): void
    {
        $request = new class extends DTOFormRequest {
            protected string $dtoClass = UserFormDTO::class;

            public function authorize(): bool
            {
                return true;
            }

            public function attributes(): array
            {
                return [
                    'name' => 'full name',
                    'email' => 'email address',
                ];
            }
        };

        $attributes = $request->attributes();

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertSame('full name', $attributes['name']);
    }

    public function test_it_handles_json_request(): void
    {
        $request = UserFormRequest::create(
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

        // Manually set validator
        $validator = $this->validationFactory->make(
            $request->all(),
            $request->rules()
        );
        $request->setValidator($validator);

        $dto = $request->toDTO();

        $this->assertInstanceOf(UserFormDTO::class, $dto);
        $this->assertSame('Jane Doe', $dto->name);
        $this->assertSame('jane@example.com', $dto->email);
    }

    public function test_it_can_be_extended_with_additional_rules(): void
    {
        $request = new class extends DTOFormRequest {
            protected string $dtoClass = UserFormDTO::class;

            public function authorize(): bool
            {
                return true;
            }

            public function rules(): array
            {
                return array_merge(parent::rules(), [
                    'age' => ['integer', 'min:18', 'max:120'],
                ]);
            }
        };

        $rules = $request->rules();

        $this->assertArrayHasKey('age', $rules);
        $this->assertContains('integer', $rules['age']);
        $this->assertContains('min:18', $rules['age']);
    }

    public function test_it_handles_authorization(): void
    {
        $request = new class extends DTOFormRequest {
            protected string $dtoClass = UserFormDTO::class;

            public function authorize(): bool
            {
                return false;
            }
        };

        $this->assertFalse($request->authorize());
    }

    public function test_it_converts_dto_back_to_array(): void
    {
        $request = UserFormRequest::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        // Manually set validator
        $validator = $this->validationFactory->make(
            $request->all(),
            $request->rules()
        );
        $request->setValidator($validator);

        $dto = $request->toDTO();
        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('age', $array);
        $this->assertSame('John Doe', $array['name']);
    }
}

